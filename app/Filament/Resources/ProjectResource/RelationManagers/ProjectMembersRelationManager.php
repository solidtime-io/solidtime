<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Resources\ProjectMemberResource;
use App\Models\ProjectMember;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class ProjectMembersRelationManager extends RelationManager
{
    protected static ?string $title = 'Project Members';

    protected static string $relationship = 'members';

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
                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\TextColumn::make('billable_rate')
                    ->numeric()
                    ->sortable(),
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
                    ->url(fn (ProjectMember $record): string => ProjectMemberResource::getUrl('view', [
                        'record' => $record->getKey(),
                    ])),
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (ProjectMember $record): string => ProjectMemberResource::getUrl('edit', [
                        'record' => $record->getKey(),
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
            ]);
    }
}
