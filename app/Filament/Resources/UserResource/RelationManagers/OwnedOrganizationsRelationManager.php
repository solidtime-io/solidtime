<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OwnedOrganizationsRelationManager extends RelationManager
{
    protected static ?string $title = 'Owned Organizations';

    protected static string $relationship = 'ownedOrganizations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->recordActions([
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
            ->toolbarActions([
            ]);
    }
}
