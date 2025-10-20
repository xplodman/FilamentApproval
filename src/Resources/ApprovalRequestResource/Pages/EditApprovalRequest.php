<?php

namespace Xplodman\FilamentApproval\Resources\ApprovalRequestResource\Pages;

use Filament\Forms\Form;
use Filament\Actions\ViewAction;
use Xplodman\FilamentApproval\Resources\ApprovalRequestResource;
use Xplodman\FilamentApproval\Models\ApprovalRequest;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditApprovalRequest extends EditRecord
{
    protected static string $resource = ApprovalRequestResource::class;

    public function form(Form $form): Form
    {
        $record = $this->getRecord();
        $approvableType = new ($record->approvable_type);
        $approvableType->fill($record->attributes ?? []);

        // For edit requests, set the actual record being edited as the context
        if (($record->request_type ?? 'edit') === 'edit' && $record->approvable_id) {
            $actualRecord = $record->approvable_type::find($record->approvable_id);
            if ($actualRecord) {
                $approvableType = $actualRecord;
            }
        }

        // Retrieve resource class from stored resource_class field
        $resourceClass = $record->resource_class;

        if ($resourceClass && class_exists($resourceClass)) {
            // ✅ Use resource from stored field if it exists
            $form->record($approvableType);
            return $resourceClass::form($form);
        }

        // Fallback to resource auto-discovery via approvable type
        $resourceClass = $approvableType::getResource() ?? null;

        if ($resourceClass && class_exists($resourceClass)) {
            $form->record($approvableType);
            return $resourceClass::form($form);
        }

        // 🔸 If nothing found, fallback gracefully or throw error
        throw new \Exception('No valid resource class found for approvable type: ' . $record->approvable_type);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // If approval request stores attributes, extract it for editing
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            return array_merge($data, $data['attributes']);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 🔹 Merge attributes with form data
        // Form ($data) values take priority over attributes
        $attributes = is_array($record->attributes) ? $record->attributes : [];
        $data = array_merge($attributes, $data);

        // 🔹 Determine target model class
        $modelClass = $record->approvable_type;

        /** @var Model $modelInstance */
        $modelInstance = new $modelClass();

        // 🔹 Filter to fillable attributes
        $fillable = method_exists($modelInstance, 'getFillable')
            ? $modelInstance->getFillable()
            : array_keys($data);

        $attributes = array_intersect_key($data, array_flip($fillable));

        // Apply create or edit
        if (($record->request_type ?? 'edit') === 'create') {
            /** @var Model $createdModel */
            $createdModel = $modelClass::query()->create($attributes);

            // Link created model back to approval
            $record->approvable_id = $createdModel->getKey();
        } else {
            // Handle edit case - update the existing model
            $existingModel = $modelClass::query()->find($record->approvable_id);

            if ($existingModel) {
                $existingModel->update($attributes);
            }
        }

        // Mark approval as approved
        $record->status = ApprovalRequest::STATUS_APPROVED;
        $record->decision_by_id = auth()->id();
        $record->decision_at = now();
        $record->save();

        return $record;
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Approve');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel');
    }
}
