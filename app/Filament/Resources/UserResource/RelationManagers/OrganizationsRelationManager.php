<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\Role;
use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationsRelationManager extends RelationManager
{
    protected static string $relationship = 'organizations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('role')
                    ->options(Role::class),
                TextInput::make('billable_rate')
                    ->label('Billable rate (in Cents)')
                    ->nullable()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('role'),
                TextColumn::make('billable_rate')
                    ->money(fn (Organization $resource) => $resource->currency ?? 'EUR', divideBy: 100),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()->form(fn (AttachAction $action): array => [
                    $action->getRecordSelect(),
                    Select::make('role')
                        ->options(Role::class),
                ]),
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Organization $record): string => OrganizationResource::getUrl('view', [
                        'record' => $record->getKey(),
                    ])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
