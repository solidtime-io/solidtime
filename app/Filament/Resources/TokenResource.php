<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TokenResource\Pages\ListTokens;
use App\Filament\Resources\TokenResource\Pages\ViewToken;
use App\Models\Passport\Token;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TokenResource extends Resource
{
    protected static ?string $model = Token::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Auth';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('id')
                    ->label('ID')
                    ->disabled()
                    ->visibleOn(['update', 'show'])
                    ->readOnly()
                    ->maxLength(255),
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Select::make('owner_id')
                    ->label('User')
                    ->relationship(name: 'user', titleAttribute: 'name')
                    ->searchable(['name'])
                    ->disabled()
                    ->required(),
                Select::make('client_id')
                    ->label('Client')
                    ->relationship(name: 'client', titleAttribute: 'name')
                    ->searchable(['name'])
                    ->required(),
                Toggle::make('revoked')
                    ->label('Revoked')
                    ->required(),
                DateTimePicker::make('expires_at')
                    ->label('Expires At')
                    ->disabled(),
                DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->disabled(),
                DateTimePicker::make('updated_at')
                    ->label('Updated At')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.name')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('personal_access_client')
                    ->state(function (Token $token): bool {
                        return in_array('personal_access', $token->client->grant_types ?? [], true);
                    })
                    ->boolean()
                    ->label('API token?'),
                IconColumn::make('revoked')
                    ->boolean()
                    ->label('Revoked?')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_personal_access_client')
                    ->queries(
                        true: function (Builder $query) {
                            /** @var Builder<Token> $query */
                            return $query->isApiToken();
                        },
                        false: function (Builder $query) {
                            /** @var Builder<Token> $query */
                            return $query->isApiToken(false);
                        },
                        blank: function (Builder $query) {
                            /** @var Builder<Token> $query */
                            return $query;
                        },
                    )
                    ->label('API token?'),
                TernaryFilter::make('revoked')
                    ->label('Revoked?'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
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
            'index' => ListTokens::route('/'),
            'view' => ViewToken::route('/{record}'),
        ];
    }
}
