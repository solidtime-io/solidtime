<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectMemberResource\Pages\CreateProjectMember;
use App\Filament\Resources\ProjectMemberResource\Pages\EditProjectMember;
use App\Filament\Resources\ProjectMemberResource\Pages\ListProjectMembers;
use App\Filament\Resources\ProjectMemberResource\Pages\ViewProjectMembers;
use App\Models\ProjectMember;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectMemberResource extends Resource
{
    protected static ?string $model = ProjectMember::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('billable_rate')
                    ->label('Billable rate (in Cents)')
                    ->nullable()
                    ->rules([
                        'nullable',
                        'integer',
                        'gt:0',
                        'max:2147483647',
                    ])
                    ->numeric(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('member_id')
                    ->relationship('member', 'id')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('billable_rate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('project.name'),
                TextColumn::make('user.name'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectMembers::route('/'),
            'create' => CreateProjectMember::route('/create'),
            'edit' => EditProjectMember::route('/{record}/edit'),
            'view' => ViewProjectMembers::route('/{record}'),
        ];
    }
}
