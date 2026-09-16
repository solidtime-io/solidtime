<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\OrganizationInvitationResource\Pages\EditOrganizationInvitation;
use App\Filament\Resources\OrganizationInvitationResource\Pages\ListOrganizationInvitations;
use App\Filament\Resources\OrganizationInvitationResource\Pages\ViewOrganizationInvitation;
use App\Models\OrganizationInvitation;
use App\Service\OrganizationInvitationService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class OrganizationInvitationResource extends Resource
{
    protected static ?string $model = OrganizationInvitation::class;

    protected static ?string $label = 'Invitations';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'Users';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('email')
                    ->label('Email')
                    ->disabledOn(['edit'])
                    ->required(),
                Select::make('role')
                    ->options(Role::class),
                Select::make('organization_id')
                    ->label('Organization')
                    ->relationship(name: 'organization', titleAttribute: 'name')
                    ->searchable(['name'])
                    ->disabledOn(['edit'])
                    ->required(),
                DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->hiddenOn(['create'])
                    ->disabled(),
                DateTimePicker::make('updated_at')
                    ->label('Updated At')
                    ->hiddenOn(['create'])
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->sortable(),
                TextColumn::make('role'),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('resend')
                        ->label('Resend')
                        ->action(function (Collection $records): void {
                            foreach ($records as $organizationInvite) {
                                app(OrganizationInvitationService::class)->resend($organizationInvite);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizationInvitations::route('/'),
            'edit' => EditOrganizationInvitation::route('/{record}/edit'),
            'view' => ViewOrganizationInvitation::route('/{record}'),
        ];
    }
}
