<?php

namespace Xplodman\FilamentApproval\Concerns;

use Xplodman\FilamentApproval\Models\ApprovalRequest;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;

/**
 * Trait to intercept Filament CreateRecord flow
 * and create an ApprovalRequest instead of saving directly.
 */
trait InterceptsCreateForApproval
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->interceptCreateForApproval($data);
    }

    protected function interceptCreateForApproval(array $data): array
    {
        $data = array_merge($this->data, $data);

        // Create approval request
        ApprovalRequest::create([
            'request_type' => ApprovalRequest::TYPE_CREATE,
            'requester_id' => auth()->id(),
            'approvable_type' => $this->getModel(),
            'attributes' => $data,
            'relationships' => [],
            'original_data' => [],
            'resource_class' => $this->getResource(),
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);

        // Stop actual record creation
        return [];
    }

    /**
     * Instead of saving, just notify.
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $modelName = class_basename($this->getModel());

        Notification::make()
            ->title('Approval Requested')
            ->body("Your {$modelName} creation request has been submitted for approval.")
            ->success()
            ->send();

        return new ($this->getModel())();
    }

    /**
     * Redirect back to index.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Disable Filament's default success notification.
     */
    protected function getCreatedNotification(): ?\Filament\Notifications\Notification
    {
        return null;
    }
}
