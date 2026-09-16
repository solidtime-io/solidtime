<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AuditResource\Pages\CreateAudit;
use App\Filament\Resources\AuditResource\Pages\ListAudits;
use App\Filament\Resources\AuditResource\Pages\ViewAudit;
use App\Models\Audit;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Novadaemon\FilamentPrettyJson\Form\PrettyJsonField;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_type')
                    ->maxLength(255),
                TextInput::make('user_id'),
                TextInput::make('event')
                    ->required()
                    ->maxLength(255),
                TextInput::make('auditable_type')
                    ->required()
                    ->maxLength(255),
                TextInput::make('auditable_id')
                    ->required(),
                PrettyJsonField::make('old_values'),
                PrettyJsonField::make('new_values'),
                Textarea::make('url'),
                TextInput::make('ip_address'),
                TextInput::make('user_agent')
                    ->maxLength(1023),
                TextInput::make('tags')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name'),
                TextColumn::make('event'),
                TextColumn::make('auditable_type'),
                TextColumn::make('auditable_id'),
                IconColumn::make('was_command')
                    ->getStateUsing(fn (Audit $record) => Str::startsWith($record->url, 'artisan '))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->sortable()
                    ->dateTime(),
                TextColumn::make('updated_at')
                    ->sortable()
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
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
            'index' => ListAudits::route('/'),
            'create' => CreateAudit::route('/create'),
            'view' => ViewAudit::route('/{record}'),
        ];
    }
}
