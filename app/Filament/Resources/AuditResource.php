<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AuditResource\Pages;
use App\Models\Audit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Novadaemon\FilamentPrettyJson\PrettyJson;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'System';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('user_id'),
                Forms\Components\TextInput::make('event')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('auditable_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('auditable_id')
                    ->required(),
                PrettyJson::make('old_values'),
                PrettyJson::make('new_values'),
                Forms\Components\Textarea::make('url'),
                Forms\Components\TextInput::make('ip_address'),
                Forms\Components\TextInput::make('user_agent')
                    ->maxLength(1023),
                Forms\Components\TextInput::make('tags')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\TextColumn::make('event'),
                Tables\Columns\TextColumn::make('auditable_type'),
                Tables\Columns\TextColumn::make('auditable_id'),
                IconColumn::make('was_command')
                    ->getStateUsing(fn (Audit $record) => Str::startsWith($record->url, 'artisan '))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->sortable()
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'view' => Pages\ViewAudit::route('/{record}'),
        ];
    }
}
