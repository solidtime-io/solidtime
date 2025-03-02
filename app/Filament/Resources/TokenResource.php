<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TokenResource\Pages;
use App\Models\Passport\Client;
use App\Models\Passport\Token;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TokenResource extends Resource
{
    protected static ?string $model = Token::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Auth';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->label('ID')
                    ->disabled()
                    ->visibleOn(['update', 'show'])
                    ->readOnly()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship(name: 'user', titleAttribute: 'name')
                    ->searchable(['name'])
                    ->disabled()
                    ->required(),
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->relationship(name: 'client', titleAttribute: 'name')
                    ->searchable(['name'])
                    ->required(),
                Forms\Components\Toggle::make('revoked')
                    ->label('Revoked')
                    ->required(),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires At')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('updated_at')
                    ->label('Updated At')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('client.personal_access_client')
                    ->boolean()
                    ->label('API token?')
                    ->sortable(),
                Tables\Columns\IconColumn::make('revoked')
                    ->boolean()
                    ->label('Revoked?')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
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
                            return $query->whereHas('client', function (Builder $query) {
                                /** @var Builder<Client> $query */
                                return $query->where('personal_access_client', true);
                            });
                        },
                        false: function (Builder $query) {
                            /** @var Builder<Token> $query */
                            return $query->whereHas('client', function (Builder $query) {
                                /** @var Builder<Client> $query */
                                return $query->where('personal_access_client', false);
                            });
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
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListTokens::route('/'),
            'view' => Pages\ViewToken::route('/{record}'),
        ];
    }
}
