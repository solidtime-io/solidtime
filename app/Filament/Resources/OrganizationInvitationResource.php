<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\OrganizationInvitationResource\Pages;
use App\Models\OrganizationInvitation;
use App\Service\OrganizationInvitationService;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class OrganizationInvitationResource extends Resource
{
    protected static ?string $model = OrganizationInvitation::class;

    protected static ?string $label = 'Invitations';

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Users';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->disabledOn(['edit'])
                    ->required(),
                Select::make('role')
                    ->options(Role::class),
                Forms\Components\Select::make('organization_id')
                    ->label('Organization')
                    ->relationship(name: 'organization', titleAttribute: 'name')
                    ->searchable(['name'])
                    ->disabledOn(['edit'])
                    ->required(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->hiddenOn(['create'])
                    ->disabled(),
                Forms\Components\DateTimePicker::make('updated_at')
                    ->label('Updated At')
                    ->hiddenOn(['create'])
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('organization.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->sortable(),
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('resend')
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
            'index' => Pages\ListOrganizationInvitations::route('/'),
            'edit' => Pages\EditOrganizationInvitation::route('/{record}/edit'),
            'view' => Pages\ViewOrganizationInvitation::route('/{record}'),
        ];
    }
}
