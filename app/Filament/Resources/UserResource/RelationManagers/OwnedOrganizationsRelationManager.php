<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class OwnedOrganizationsRelationManager extends RelationManager
{
    protected static ?string $title = 'Owned Organizations';

    protected static string $relationship = 'ownedTeams';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Organization $record): string => OrganizationResource::getUrl('view', [
                        'record' => $record->getKey(),
                    ])),
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Organization $record): string => OrganizationResource::getUrl('edit', [
                        'record' => $record->getKey(),
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
            ]);
    }
}
