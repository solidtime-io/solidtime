<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\Role;
use App\Exceptions\Api\ApiException;
use App\Filament\Resources\OrganizationResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use App\Service\MemberService;
use Filament\Actions\Action;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationsRelationManager extends RelationManager
{
    protected static string $relationship = 'organizations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('role')
                    ->options(Role::class),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('role'),
                TextColumn::make('membership.billable_rate')
                    ->label('Billable rate')
                    ->money(fn (Organization $resource) => $resource->currency, divideBy: 100),
            ])
            ->headerActions([
            ])
            ->recordActions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Organization $record): string => OrganizationResource::getUrl('view', [
                        'record' => $record->getKey(),
                    ])),
                EditAction::make()
                    ->using(function (Organization $record, array $data): Organization {
                        /** @var Member $member */
                        $member = $record->getRelation('membership');

                        if ($data['role'] !== $member->role) {
                            try {
                                app(MemberService::class)->changeRole($member, $record, ($data['role'] instanceof Role ? $data['role'] : Role::from($data['role'])), true);
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
                DetachAction::make()
                    ->using(function (Organization $record): void {
                        /** @var User $user */
                        $user = $this->getOwnerRecord();
                        $member = Member::query()
                            ->whereBelongsTo($user, 'user')
                            ->whereBelongsTo($record, 'organization')
                            ->firstOrFail();
                        try {
                            app(MemberService::class)->removeMember($member, $record);
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
            ->toolbarActions([
            ]);
    }
}
