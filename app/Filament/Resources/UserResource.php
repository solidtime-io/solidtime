<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Weekday;
use App\Exceptions\Api\ApiException;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Filament\Resources\UserResource\RelationManagers\OrganizationsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OwnedOrganizationsRelationManager;
use App\Models\User;
use App\Service\DeletionService;
use App\Service\TimezoneService;
use App\Service\UserService;
use Brick\Money\IsoCurrencyProvider;
use Exception;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;
use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static string|\UnitEnum|null $navigationGroup = 'Users';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        /** @var User|null $record */
        $record = $schema->getRecord();

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
                TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->rules($record?->is_placeholder ? [] : [
                        UniqueEloquent::make(User::class, 'email')
                            ->ignore($record?->getKey()),
                    ])
                    ->rule([
                        'email:rfc,strict',
                    ])
                    ->maxLength(255),
                Toggle::make('is_placeholder')
                    ->label('Is Placeholder?')
                    ->hiddenOn(['create'])
                    ->disabledOn(['edit']),
                DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At')
                    ->hiddenOn(['create'])
                    ->nullable(),
                Toggle::make('is_email_verified')
                    ->label('Email Verified?')
                    ->visibleOn(['create']),
                Select::make('timezone')
                    ->label('Timezone')
                    ->options(fn (): array => app(TimezoneService::class)->getSelectOptions())
                    ->searchable()
                    ->required(),
                Select::make('week_start')
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
                Select::make('currency')
                    ->label('Currency (Personal Organization)')
                    ->options(function (): array {
                        $currencies = IsoCurrencyProvider::getInstance()->getAvailableCurrencies();
                        $select = [];
                        foreach ($currencies as $currency) {
                            $select[$currency->getCurrencyCode()] = $currency->getName().' ('.$currency->getCurrencyCode().')';
                        }

                        return $select;
                    })
                    ->required()
                    ->visibleOn(['create'])
                    ->searchable(),
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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_real_user')
                    ->getStateUsing(fn (User $record): bool => ! $record->is_placeholder)
                    ->label('Real user?')
                    ->boolean(),
                IconColumn::make('email_verified')
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null)
                    ->label('Email verified?')
                    ->boolean(),
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
            ->recordActions([
                Impersonate::make()->before(function (User $record): void {
                    if ($record->currentOrganization === null) {
                        $organization = $record->organizations()->where('personal_team', '=', true)->first();
                        if ($organization === null) {
                            $organization = $record->organizations()->first();
                        }
                        if ($organization === null) {
                            throw new Exception('User has no organization');
                        }
                        app(UserService::class)->switchCurrentOrganization($record, $organization);
                    }
                }),
                EditAction::make(),
                DeleteAction::make()
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
            ->toolbarActions([
                BulkAction::make('Resend verification email')
                    ->icon('heroicon-o-paper-airplane')
                    ->action(function (Collection $records): void {
                        foreach ($records as $user) {
                            /** @var User $user */
                            $user->sendEmailVerificationNotification();
                        }
                    }),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
            'view' => ViewUser::route('/{record}'),
        ];
    }
}
