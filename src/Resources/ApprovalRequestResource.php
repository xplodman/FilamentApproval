<?php

namespace Xplodman\FilamentApproval\Resources;

use Xplodman\FilamentApproval\Resources\ApprovalRequestResource\Pages\ListApprovalRequests;
use Xplodman\FilamentApproval\Resources\ApprovalRequestResource\Pages\EditApprovalRequest;
use Xplodman\FilamentApproval\Models\ApprovalRequest;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequestResource extends Resource
{
    protected static ?string $model = ApprovalRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Management';

    public static function getNavigationIcon(): ?string
    {
        return config('filamentapproval.navigation_icon', 'heroicon-o-clipboard-document-check');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filamentapproval.navigation_group', 'Management');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasRole('administrator');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('requester.name')
                    ->label('Requester'),
                TextColumn::make('request_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'create' => 'info',
                        'edit' => 'warning',
                        'delete' => 'danger',
                    }),
                TextColumn::make('approvable_type')
                    ->label('Model')
                    ->formatStateUsing(fn($state) => class_basename($state)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),
                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime(),
                TextColumn::make('decidedBy.name')
                    ->label('Decision By')
                    ->placeholder('N/A'),
                TextColumn::make('decision_at')
                    ->label('Decision Date')
                    ->dateTime()
                    ->placeholder('N/A'),
            ])
            ->filters([
                // Status filter with counts
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->indicator(function ($state) {
                        return match ($state) {
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            default => null,
                        };
                    }),

                // Request type filter
                SelectFilter::make('request_type')
                    ->options([
                        'create' => 'Create',
                        'edit' => 'Edit',
                        'delete' => 'Delete',
                    ])
                    ->multiple()
                    ->searchable()
                    ->label('Request Type'),

                // Requester filter (relationship)
                SelectFilter::make('requester_id')
                    ->relationship('requester', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Requester'),

                // Model type filter
                SelectFilter::make('approvable_type')
                    ->options(function () {
                        return ApprovalRequest::query()
                            ->select('approvable_type')
                            ->distinct()
                            ->get()
                            ->mapWithKeys(function ($item) {
                                return [$item->approvable_type => class_basename($item->approvable_type)];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->label('Model Type'),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->recordActions([
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApprovalRequests::route('/'),
            'edit' => EditApprovalRequest::route('/{record}/edit'),
        ];
    }
}
