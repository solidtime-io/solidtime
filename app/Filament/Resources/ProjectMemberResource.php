<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectMemberResource\Pages;
use App\Models\ProjectMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectMemberResource extends Resource
{
    protected static ?string $model = ProjectMember::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('billable_rate')
                    ->label('Billable rate (in Cents)')
                    ->nullable()
                    ->rules([
                        'nullable',
                        'integer',
                        'gt:0',
                        'max:2147483647',
                    ])
                    ->numeric(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('member_id')
                    ->relationship('member', 'id')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID'),
                Tables\Columns\TextColumn::make('billable_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name'),
                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListProjectMembers::route('/'),
            'create' => Pages\CreateProjectMember::route('/create'),
            'edit' => Pages\EditProjectMember::route('/{record}/edit'),
            'view' => Pages\ViewProjectMembers::route('/{record}'),
        ];
    }
}
