<?php

namespace Xplodman\FilamentApproval\Concerns;

use Filament\Actions;
use Filament\Notifications\Notification;
use Xplodman\FilamentApproval\Enums\ApprovalStatusEnum;
use Xplodman\FilamentApproval\Enums\ApprovalTypeEnum;

/**
 * Trait to intercept Filament CreateRecord flow
 * and create an ApprovalRequest instead of saving directly.
 */
trait InterceptsCreateForApproval
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->isBypassUser()) {
            return $data; // proceed with normal create
        }

        return $this->interceptCreateForApproval($data);
    }

    protected function interceptCreateForApproval(array $data): array
    {
        $data = array_merge($this->data, $data);

        // Get the configurable model class
        $approvalRequestModel = config('filamentapproval.approval_request_model', \Xplodman\FilamentApproval\Models\ApprovalRequest::class);

        // Create approval request using the configurable model
        $approvalRequestModel::create([
            'request_type' => ApprovalTypeEnum::CREATE,
            'requester_id' => auth()->id(),
            'approvable_type' => $this->getModel(),
            'attributes' => $data,
            'relationships' => [],
            'original_data' => [],
            'resource_class' => $this->getResource(),
            'status' => ApprovalStatusEnum::PENDING,
        ]);

        // Stop actual record creation
        return [];
    }

    /**
     * Instead of saving, just notify.
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        if ($this->isBypassUser()) {
            if (method_exists(get_parent_class($this) ?: self::class, 'handleRecordCreation')) {
                return parent::handleRecordCreation($data);
            }

            return new ($this->getModel())();
        }

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

    /**
     * Change Create action label for non-bypass users.
     */
    protected function getCreateFormAction(): Actions\Action
    {
        $action = parent::getCreateFormAction();

        return $action->label(fn () => $this->isBypassUser() ? 'Create' : 'Submit for Approval');
    }

    /**
     * Shared bypass helper with Edit trait.
     */
    protected function isBypassUser(): bool
    {
        $permission = config('filamentapproval.permissions.bypass');
        if (empty($permission)) {
            return false;
        }

        return auth()->user()?->can($permission) ?? false;
    }
}
