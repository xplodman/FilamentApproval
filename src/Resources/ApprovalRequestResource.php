<?php

namespace Xplodman\FilamentApproval\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Xplodman\FilamentApproval\Enums\ApprovalStatusEnum;
use Xplodman\FilamentApproval\Enums\ApprovalTypeEnum;
use Xplodman\FilamentApproval\Resources\ApprovalRequestResource\Pages\EditApprovalRequest;
use Xplodman\FilamentApproval\Resources\ApprovalRequestResource\Pages\ListApprovalRequests;

class ApprovalRequestResource extends Resource
{
    protected static ?string $model = null; // Will be set dynamically

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Management';

    public static function getModel(): string
    {
        return config('filamentapproval.approval_request_model', \Xplodman\FilamentApproval\Models\ApprovalRequest::class);
    }

    public static function getNavigationIcon(): ?string
    {
        return config('filamentapproval.navigation_icon', 'heroicon-o-clipboard-document-check');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filamentapproval.navigation_group', 'Management');
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'approve',
            'reject',
        ];
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
                    ->badge(),
                TextColumn::make('approvable_type')
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('decided_reason')
                    ->label('Reason')
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime(),
                TextColumn::make('decidedBy.name')
                    ->label('decided By')
                    ->placeholder('N/A'),
                TextColumn::make('decided_at')
                    ->label('decided Date')
                    ->dateTime()
                    ->placeholder('N/A'),
            ])
            ->filters([
                // Status filter with counts
                SelectFilter::make('status')
                    ->options(ApprovalStatusEnum::class)
                    ->default(ApprovalStatusEnum::PENDING->value),

                SelectFilter::make('request_type')
                    ->options(ApprovalTypeEnum::class),

                // Requester filter (relationship)
                SelectFilter::make('requester_id')
                    ->relationship('requester', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Requester'),

                // Model type filter
                SelectFilter::make('approvable_type')
                    ->options(function () {
                        $modelClass = config('filamentapproval.approval_request_model', \Xplodman\FilamentApproval\Models\ApprovalRequest::class);

                        return $modelClass::query()
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
