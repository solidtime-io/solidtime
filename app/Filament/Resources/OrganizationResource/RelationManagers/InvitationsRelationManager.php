<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use App\Enums\Role;
use App\Filament\Resources\OrganizationInvitationResource;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Service\InvitationService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class InvitationsRelationManager extends RelationManager
{
    protected static string $relationship = 'teamInvitations';

    protected static ?string $title = 'Invitations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email')
                    ->disabledOn(['edit'])
                    ->required(),
                Select::make('role')
                    ->options(Role::class)
                    ->label('Role')
                    ->rules([
                        'required',
                        'string',
                        Rule::enum(Role::class)
                            ->except([Role::Owner, Role::Placeholder]),
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->modelLabel('Invitation')
            ->pluralModelLabel('Invitations')
            ->columns([
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('role'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-s-plus')
                    ->using(function (array $data, string $model): Model {
                        /** @var Organization $ownerRecord */
                        $ownerRecord = $this->getOwnerRecord();

                        return app(InvitationService::class)
                            ->inviteUser($ownerRecord, $data['email'], Role::from($data['role']));
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (OrganizationInvitation $record): string => OrganizationInvitationResource::getUrl('view', [
                        'record' => $record->getKey(),
                    ])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
