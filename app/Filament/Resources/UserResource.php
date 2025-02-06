<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Weekday;
use App\Exceptions\Api\ApiException;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\OrganizationsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OwnedOrganizationsRelationManager;
use App\Models\User;
use App\Service\DeletionService;
use App\Service\TimezoneService;
use Brick\Money\ISOCurrencyProvider;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Users';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        /** @var User|null $record */
        $record = $form->getRecord();
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
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->rules($record?->is_placeholder ? [] : [
                        UniqueEloquent::make(User::class, 'email')
                            ->ignore($record?->getKey()),
                    ])
                    ->rule([
                        'email',
                    ])
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_placeholder')
                    ->label('Is Placeholder?')
                    ->hiddenOn(['create'])
                    ->disabledOn(['edit']),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At')
                    ->hiddenOn(['create'])
                    ->nullable(),
                Forms\Components\Toggle::make('is_email_verified')
                    ->label('Email Verified?')
                    ->visibleOn(['create']),
                Forms\Components\Select::make('timezone')
                    ->label('Timezone')
                    ->options(fn (): array => app(TimezoneService::class)->getSelectOptions())
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('week_start')
                    ->label('Week Start')
                    ->options(Weekday::class)
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->label('Password')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->hiddenOn(['create'])
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
                TextInput::make('password_create')
                    ->password()
                    ->label('Password')
                    ->visibleOn(['create'])
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
                Forms\Components\Select::make('currency')
                    ->label('Currency (Personal Organization)')
                    ->options(function (): array {
                        $currencies = ISOCurrencyProvider::getInstance()->getAvailableCurrencies();
                        $select = [];
                        foreach ($currencies as $currency) {
                            $select[$currency->getCurrencyCode()] = $currency->getName().' ('.$currency->getCurrencyCode().')';
                        }

                        return $select;
                    })
                    ->required()
                    ->visibleOn(['create'])
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_real_user')
                    ->getStateUsing(fn (User $record): bool => ! $record->is_placeholder)
                    ->label('Real user?')
                    ->boolean(),
                Tables\Columns\IconColumn::make('email_verified')
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null)
                    ->label('Email verified?')
                    ->boolean(),
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
                TernaryFilter::make('real_user')
                    ->queries(
                        true: function (Builder $query): Builder {
                            /** @var Builder<User> $query */
                            return $query->where('is_placeholder', '=', false);
                        },
                        false: function (Builder $query): Builder {
                            /** @var Builder<User> $query */
                            return $query->where('is_placeholder', '=', true);
                        },
                        blank: function (Builder $query): Builder {
                            /** @var Builder<User> $query */
                            return $query;
                        },
                    )
                    ->label('Real User?'),
                TernaryFilter::make('email_verified')
                    ->label('Email Verified?')
                    ->attribute('email_verified_at')
                    ->nullable(),
            ])
            ->actions([
                Impersonate::make()->before(function (User $record): void {
                    if ($record->currentTeam === null) {
                        $organization = $record->organizations()->where('personal_team', '=', true)->first();
                        if ($organization === null) {
                            $organization = $record->organizations()->first();
                        }
                        if ($organization === null) {
                            throw new Exception('User has no organization');
                        }
                        $record->currentTeam()->associate($organization);
                        $record->save();
                    }
                }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (User $record) => $record->is(Auth::user()))
                    ->using(function (User $record): void {
                        try {
                            app(DeletionService::class)->deleteUser($record);
                        } catch (ApiException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Delete failed')
                                ->body($exception->getTranslatedMessage())
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrganizationsRelationManager::class,
            OwnedOrganizationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
