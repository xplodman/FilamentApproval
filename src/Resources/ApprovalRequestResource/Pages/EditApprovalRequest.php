<?php

namespace Xplodman\FilamentApproval\Resources\ApprovalRequestResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Xplodman\FilamentApproval\Enums\ApprovalTypeEnum;
use Xplodman\FilamentApproval\Enums\RelationTypeEnum;
use Xplodman\FilamentApproval\Models\ApprovalRequest;
use Xplodman\FilamentApproval\Resources\ApprovalRequestResource;

class EditApprovalRequest extends EditRecord
{
    protected static string $resource = ApprovalRequestResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();

        $typeEnum = \Xplodman\FilamentApproval\Enums\ApprovalTypeEnum::tryFrom($record->request_type ?? \Xplodman\FilamentApproval\Enums\ApprovalTypeEnum::EDIT->value)
            ?? \Xplodman\FilamentApproval\Enums\ApprovalTypeEnum::EDIT->value;

        $approvableType = class_basename($record->approvable_type);

        return "{$typeEnum->getLabel()} ({$approvableType}) Request";
    }

    protected function getHeaderActions(): array
    {
        $actions = parent::getHeaderActions();

        // 🔹 Existing "View Diff" button
        $actions[] = \Filament\Actions\Action::make('viewDiff')
            ->label('View Diff')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->modalHeading('Changes Diff')
            ->modalWidth('7xl')
            ->modalSubmitAction(false)
            ->modalContent(function () {
                /** @var ApprovalRequest $record */
                $record = $this->getRecord();

                $original = is_array($record->original_data) ? $record->original_data : [];
                $proposed = is_array($record->attributes) ? $record->attributes : [];

                $view = view('filamentapproval::approval-requests.diff', [
                    'record' => $record,
                    'original' => $original,
                    'proposed' => $proposed,
                ]);

                return new \Illuminate\Support\HtmlString($view->render());
            });

        return $actions;
    }

    public function form(Schema $schema): Schema
    {
        $record = $this->getRecord();
        $requestType = $record->request_type ?? ApprovalTypeEnum::EDIT->value;

        $approvableType = $this->resolveApprovableType($record);
        $resourceClass = $this->resolveResourceClass($record);

        if (! $resourceClass) {
            throw new \Exception('No valid resource class found for approvable type: ' . $record->approvable_type);
        }

        $schema->record($approvableType);
        $isDisabled = $this->shouldDisableForm($record);

        return $resourceClass::form($schema)->disabled($isDisabled);
    }

    /**
     * Resolve the approvable type instance based on request type
     */
    private function resolveApprovableType($record)
    {
        $requestType = $record->request_type ?? ApprovalTypeEnum::EDIT->value;

        if ($requestType === ApprovalTypeEnum::DELETE->value) {
            return $this->resolveDeleteApprovableType($record);
        }

        return $this->resolveCreateEditApprovableType($record);
    }

    /**
     * Resolve approvable type for delete requests using original data
     */
    private function resolveDeleteApprovableType($record)
    {
        // Try to get the actual record first
        if ($record->approvable_id) {
            $actualRecord = $record->approvable_type::find($record->approvable_id);
            if ($actualRecord) {
                return $actualRecord;
            }
        }

        // Fallback to original_data if record not found or no approvable_id
        $approvableType = new ($record->approvable_type);
        $approvableType->fill($record->original_data ?? []);

        return $approvableType;
    }

    /**
     * Resolve approvable type for create/edit requests using attributes
     */
    private function resolveCreateEditApprovableType($record)
    {
        $requestType = $record->request_type ?? ApprovalTypeEnum::EDIT->value;

        $approvableType = new ($record->approvable_type);
        $approvableType->fill($record->attributes ?? []);

        // For edit requests, use the actual record if available
        if ($requestType === ApprovalTypeEnum::EDIT->value && $record->approvable_id) {
            $actualRecord = $record->approvable_type::find($record->approvable_id);
            if ($actualRecord) {
                return $actualRecord;
            }
        }

        return $approvableType;
    }

    /**
     * Determine if the form should be disabled
     */
    private function shouldDisableForm($record): bool
    {
        return ! $record->isPending();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $requestType = $record->request_type ?? ApprovalTypeEnum::EDIT->value;

        // For delete requests, use original_data instead of attributes
        if ($requestType === ApprovalTypeEnum::DELETE->value) {
            if (isset($data['original_data']) && is_array($data['original_data'])) {
                return array_merge($data, $data['original_data']);
            }
        } else {
            // For create/edit requests, use attributes
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                return array_merge($data, $data['attributes']);
            }
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $requestType = $record->request_type ?? \Xplodman\FilamentApproval\Enums\ApprovalTypeEnum::EDIT->value;

        // 🔹 Determine target model class (needed for all request types)
        $modelClass = $record->approvable_type;
        $attributes = [];

        // For delete requests, we don't need to process attributes since no changes are requested
        if ($requestType !== \Xplodman\FilamentApproval\Enums\ApprovalTypeEnum::DELETE->value) {
            // 🔹 Merge attributes with form data for create/edit requests
            $attributes = is_array($record->attributes) ? $record->attributes : [];
            $data = array_merge($attributes, $data);

            /** @var Model $modelInstance */
            $modelInstance = new $modelClass;

            // 🔹 Filter fillable
            $fillable = method_exists($modelInstance, 'getFillable')
                ? $modelInstance->getFillable()
                : array_keys($data);

            $attributes = array_intersect_key($data, array_flip($fillable));
            $attributes = $this->processAttributesBeforeSave($attributes, $record, $modelInstance);
        }

        $modelResource = $this->resolveResourceClass($record);

        // 🔹 Handle by request type
        switch ($requestType) {
            case \Xplodman\FilamentApproval\Enums\ApprovalTypeEnum::CREATE->value:
                if (method_exists($modelResource, 'beforeApprovalCreate')) {
                    $modelResource->beforeApprovalCreate($record);
                }

                /** @var Model $createdModel */
                $createdModel = $modelClass::query()->create($attributes);
                $this->handleModelRelations(createdModel: $createdModel, approvalModel: $record, data: $data);
                $record->approvable_id = $createdModel->getKey();

                if (method_exists($modelResource, 'afterApprovalCreate')) {
                    $modelResource->afterApprovalCreate($record);
                }

                break;

            case \Xplodman\FilamentApproval\Enums\ApprovalTypeEnum::EDIT->value:
                $existingModel = $modelClass::query()->find($record->approvable_id);
                if ($existingModel) {
                    if (method_exists($modelResource, 'beforeApprovalUpdate')) {
                        $modelResource->beforeApprovalUpdate($record);
                    }

                    $existingModel->update($attributes);
                    $this->handleModelRelations(createdModel: $existingModel, approvalModel: $record, data: $data);

                    if (method_exists($modelResource, 'afterApprovalUpdate')) {
                        $modelResource->afterApprovalUpdate($record);
                    }
                }

                break;

            case \Xplodman\FilamentApproval\Enums\ApprovalTypeEnum::DELETE->value:
                $existingModel = $modelClass::query()->find($record->approvable_id);
                if ($existingModel) {
                    if (method_exists($modelResource, 'beforeApprovalDelete')) {
                        $modelResource->beforeApprovalDelete($record);
                    }

                    $existingModel->delete();

                    if (method_exists($modelResource, 'afterApprovalDelete')) {
                        $modelResource->afterApprovalDelete($record);
                    }
                }

                break;
        }

        // 🔹 Mark approval as approved
        $record->status = \Xplodman\FilamentApproval\Enums\ApprovalStatusEnum::APPROVED->value;
        $record->decided_by_id = auth()->id();
        $record->decided_at = now();
        $record->save();

        return $record;
    }

    /**
     * Process attributes before saving to allow model customization.
     *
     * This method provides a hook for models to modify the attributes
     * before they are used for create/update operations.
     *
     * @param  array  $attributes  The prepared attributes
     * @param  \Xplodman\FilamentApproval\Models\ApprovalRequest  $record  The approval request record
     * @param  \Illuminate\Database\Eloquent\Model  $modelInstance  The target model instance
     * @return array The modified attributes
     */
    protected function processAttributesBeforeSave(array $attributes, $record, $modelInstance): array
    {
        // Check if the model has a method to process attributes before approval save
        if (method_exists($modelInstance, 'processAttributesBeforeApprovalSave')) {
            return $modelInstance->processAttributesBeforeApprovalSave($attributes, $record);
        }

        // Return attributes unchanged if no processing method exists
        return $attributes;
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Approve')
            ->visible(fn () => $this->getRecord()->isPending())
            ->authorize(function () {
                $permission = config('filamentapproval.permissions.approve');
                if (empty($permission)) {
                    return true; // no permission configured -> allow
                }

                return auth()->user()?->can($permission) ?? false;
            });
    }

    private function handleModelRelations($createdModel, $approvalModel, $data): void
    {
        $map = $this->resolveRelationsMapForApproval($createdModel, $approvalModel);
        if (empty($map)) {
            return;
        }

        foreach ($map as $relation => $definition) {
            if (! method_exists($createdModel, $relation)) {
                continue;
            }

            $type = $definition['type'] ?? null;
            $field = $definition['field'] ?? ($type === RelationTypeEnum::BELONGS_TO->value ? ($relation . '_id') : ($relation . '_ids'));

            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            $rel = $createdModel->{$relation}();

            switch ($type) {
                case RelationTypeEnum::BELONGS_TO->value:
                    if ($value) {
                        $rel->associate($value);
                    } else {
                        $rel->dissociate();
                    }
                    // ensure FK is persisted
                    $createdModel->save();

                    break;

                case RelationTypeEnum::BELONGS_TO_MANY->value:
                case RelationTypeEnum::MORPH_TO_MANY->value:
                    $ids = is_array($value) ? $value : [];
                    $sync = $definition['sync'] ?? true;
                    $sync ? $rel->sync($ids) : $rel->attach($ids);

                    break;

                case RelationTypeEnum::HAS_MANY->value:
                case RelationTypeEnum::MORPH_MANY->value:
                    $items = is_array($value) ? $value : [];
                    $mode = $definition['mode'] ?? 'replace'; // replace|merge

                    if ($mode === 'replace') {
                        // drop existing and recreate
                        $rel->delete();
                        if (! empty($items)) {
                            $rel->createMany($items);
                        }
                    } else {
                        // merge/update by id if present; otherwise create
                        foreach ($items as $attrs) {
                            if (is_array($attrs) && isset($attrs['id'])) {
                                $rel->getRelated()->newQuery()->whereKey($attrs['id'])->update($attrs);
                            } elseif (is_array($attrs)) {
                                $rel->create($attrs);
                            }
                        }
                    }

                    break;
            }
        }
    }

    private function resolveRelationsMapForApproval($domainModel, $approvalRequest): array
    {
        $modelResource = $this->resolveResourceClass($approvalRequest);
        if (method_exists($modelResource, 'approvalRelations')) {
            $map = $modelResource::approvalRelations();
            if (is_array($map)) {
                return $map;
            }
        }

        $cfg = config('filamentapproval.approvable_relations', []);
        $byModel = $cfg[$domainModel::class] ?? [];

        return is_array($byModel) ? $byModel : [];
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return Actions\Action::make('reject')
            ->label('Reject')
            ->color('danger')
            ->visible(fn () => $this->getRecord()->isPending())
            ->modalHeading('Reject Approval Request')
            ->modalSubmitActionLabel('Reject')
            ->requiresConfirmation()
            ->schema([
                Textarea::make('decided_reason')
                    ->label('Reason')
                    ->rows(5)
                    ->required()
                    ->maxLength(1000),
            ])
            ->authorize(function () {
                $permission = config('filamentapproval.permissions.reject');
                if (empty($permission)) {
                    return true; // no permission configured -> allow
                }

                return auth()->user()?->can($permission) ?? false;
            })
            ->action(function (array $data) {
                /** @var ApprovalRequest $record */
                $record = $this->getRecord();

                $user = auth()->user();
                $permission = config('filamentapproval.permissions.reject');

                // ✅ Check permission; fallback to true if no config or policy defined
                $authorized = ! empty($permission)
                    ? ($user?->can($permission) ?? false)
                    : true;

                if (! $authorized) {
                    abort(403, 'You are not authorized to reject this approval request.');
                }

                // ✅ Only allow rejection if still pending
                if ($record->status !== \Xplodman\FilamentApproval\Enums\ApprovalStatusEnum::PENDING) {
                    throw new \Exception('Only pending requests can be rejected.');
                }

                // ✅ Update the approval request
                $record->update([
                    'status' => \Xplodman\FilamentApproval\Enums\ApprovalStatusEnum::REJECTED,
                    'decided_by_id' => $user->id,
                    'decided_reason' => $data['decided_reason'] ?? null,
                    'decided_at' => now(),
                ]);

                $this->redirect(static::getResource()::getUrl('index'));
            });
    }

    private function resolveResourceClass(Model $record)
    {
        // Retrieve resource class from stored resource_class field
        $resourceClass = $record->resource_class;

        if ($resourceClass) {
            return $resourceClass;
        }

        // Fallback to resource auto-discovery via approvable type
        $approvableType = $record->approvable_type;
        $resourceClass = $approvableType::getResource() ?? null;

        if ($resourceClass) {
            return $resourceClass;
        }

        return null;
    }
}
