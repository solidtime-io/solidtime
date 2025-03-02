<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use App\Enums\Role;
use App\Exceptions\Api\ApiException;
use App\Filament\Resources\UserResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use App\Service\BillableRateService;
use App\Service\MemberService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('role')
                    ->options(Role::class),
                TextInput::make('billable_rate')
                    ->label('Billable rate (in Cents)')
                    ->nullable()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var Organization $organization */
        $organization = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('role'),
                TextColumn::make('billable_rate')
                    ->money($organization->currency, divideBy: 100),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->recordTitle(fn (User $record): string => "{$record->name} ({$record->email})")
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('role')
                            ->required()
                            ->options(Role::class)
                            ->rule([
                                'required',
                                'string',
                                Rule::enum(Role::class)
                                    ->except([Role::Owner, Role::Placeholder]),
                            ]),
                    ])
                    ->label('Add user')
                    ->modalHeading('Add user')
                    ->icon('heroicon-s-plus')
                    ->using(function (User $record, array $data): void {
                        /** @var Organization $organization */
                        $organization = $this->getOwnerRecord();
                        app(MemberService::class)->addMember($record, $organization, Role::from($data['role']), true);
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (User $record): string => UserResource::getUrl('view', [
                        'record' => $record->getKey(),
                    ])),
                Tables\Actions\EditAction::make()
                    ->using(function (User $record, array $data): User {
                        /** @var Organization $organization */
                        $organization = $this->getOwnerRecord();
                        /** @var Member $member */
                        $member = $record->getRelation('membership');

                        if ($data['billable_rate'] !== $member->billable_rate) {
                            $member->billable_rate = $data['billable_rate'];
                            app(BillableRateService::class)->updateTimeEntriesBillableRateForMember($member);
                        }

                        if ($data['role'] !== $member->role) {
                            try {
                                app(MemberService::class)->changeRole($member, $organization, Role::from($data['role']), true);
                            } catch (ApiException $exception) {
                                Notification::make()
                                    ->danger()
                                    ->title('Update failed')
                                    ->body($exception->getTranslatedMessage())
                                    ->persistent()
                                    ->send();
                            }
                        }
                        $member->save();

                        return $record;
                    }),
                Tables\Actions\DetachAction::make()
                    ->using(function (User $record): void {
                        /** @var Organization $organization */
                        $organization = $this->getOwnerRecord();
                        $member = Member::query()
                            ->whereBelongsTo($record, 'user')
                            ->whereBelongsTo($organization, 'organization')
                            ->firstOrFail();
                        try {
                            app(MemberService::class)->removeMember($member, $organization);
                        } catch (ApiException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Delete failed')
                                ->body($exception->getTranslatedMessage())
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
            ]);
    }
}
