<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use App\Enums\Role;
use App\Filament\Resources\OrganizationInvitationResource;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Service\InvitationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class InvitationsRelationManager extends RelationManager
{
    protected static string $relationship = 'organizationInvitations';

    protected static ?string $title = 'Invitations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextColumn::make('email'),
                TextColumn::make('role'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-s-plus')
                    ->using(function (array $data, string $model): Model {
                        /** @var Organization $ownerRecord */
                        $ownerRecord = $this->getOwnerRecord();

                        return app(InvitationService::class)
                            ->inviteUser($ownerRecord, $data['email'], ($data['role'] instanceof Role ? $data['role'] : Role::from($data['role'])), auth()->user());
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (OrganizationInvitation $record): string => OrganizationInvitationResource::getUrl('view', [
                        'record' => $record->getKey(),
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
