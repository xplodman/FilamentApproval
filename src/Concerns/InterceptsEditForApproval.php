<?php

namespace Xplodman\FilamentApproval\Concerns;

use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Xplodman\FilamentApproval\Enums\ApprovalStatusEnum;
use Xplodman\FilamentApproval\Enums\ApprovalTypeEnum;
use Xplodman\FilamentApproval\Models\ApprovalRequest;

/**
 * Trait to intercept Filament EditRecord flow
 * and create an ApprovalRequest instead of updating directly.
 */
trait InterceptsEditForApproval
{
    /**
     * Header actions: inject Delete action that requests approval for non-bypass users.
     */
    protected function getHeaderActions(): array
    {
        $actions = method_exists(get_parent_class($this) ?: self::class, 'getHeaderActions')
            ? parent::getHeaderActions()
            : [];

        $actions[] = DeleteAction::make()
            ->before(function (DeleteAction $action) {
                if ($this->isBypassUser()) {
                    return; // allow actual delete
                }

                /** @var Model $record */
                $record = $this->getRecord();

                if ($this->hasPendingApproval($record)) {
                    Notification::make()
                        ->warning()
                        ->title('Pending Approval Exists')
                        ->body(class_basename($this->getModel()) . ' already has changes awaiting approval.')
                        ->persistent()
                        ->send();

                    $action->cancel();

                    return;
                }

                $this->requestDeleteApproval($record);

                Notification::make()
                    ->title('Approval Requested')
                    ->body('Your request has been submitted for approval.')
                    ->success()
                    ->send();

                $action->cancel();
            });

        return $actions;
    }

    /**
     * Before saving, request edit approval for non-bypass users.
     */
    protected function beforeSave(): void
    {
        if ($this->isBypassUser()) {
            return;
        }

        /** @var Model $record */
        $record = $this->getRecord();

        if ($this->hasPendingApproval($record)) {
            Notification::make()
                ->warning()
                ->title('Pending Approval Exists')
                ->body(class_basename($this->getModel()) . ' already has changes awaiting approval.')
                ->persistent()
                ->send();

            $this->halt();
        }

        $formData = (property_exists($this, 'data') && is_array($this->data ?? null)) ? $this->data : [];
        $changes = $this->detectAttributeChanges($record, $formData);

        if (! empty($changes)) {
            $this->requestEditApproval($record, $formData, $record->getAttributes());

            Notification::make()
                ->title('Approval Requested')
                ->body('Your changes will be applied after approval.')
                ->success()
                ->send();

            $this->halt();
        }
    }

    /**
     * Ensure updates route through approval for non-bypass users as a safeguard.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($this->isBypassUser()) {
            return parent::handleRecordUpdate($record, $data);
        }

        $changes = $this->detectAttributeChanges($record, $data);
        if (! empty($changes)) {
            $this->requestEditApproval($record, $data, $record->getAttributes());

            return $record;
        }

        return parent::handleRecordUpdate($record, $data);
    }

    /**
     * Change Save label based on whether user can bypass approvals.
     */
    protected function getSaveFormAction(): Actions\Action
    {
        $action = parent::getSaveFormAction();

        return $action->label(fn () => $this->isBypassUser() ? 'Save' : 'Submit for Approval');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return null;
    }

    /**
     * Helpers
     */
    protected function isBypassUser(): bool
    {
        $permission = config('filamentapproval.permissions.bypass');
        if (empty($permission)) {
            return false;
        }

        return auth()->user()?->can($permission) ?? false;
    }

    protected function hasPendingApproval(Model $record): bool
    {
        $approvalModel = config('filamentapproval.approval_request_model', ApprovalRequest::class);

        return $approvalModel::query()
            ->where('approvable_type', $this->getModel())
            ->where('approvable_id', $record->getKey())
            ->where('status', ApprovalStatusEnum::PENDING->value)
            ->exists();
    }

    protected function requestEditApproval(Model $record, array $proposedData, array $originalData): void
    {
        $approvalModel = config('filamentapproval.approval_request_model', ApprovalRequest::class);

        $approvalModel::create([
            'request_type' => ApprovalTypeEnum::EDIT,
            'requester_id' => auth()->id(),
            'approvable_type' => $this->getModel(),
            'approvable_id' => $record->getKey(),
            'attributes' => $proposedData,
            'relationships' => [],
            'original_data' => $originalData,
            'resource_class' => $this->getResource(),
            'status' => ApprovalStatusEnum::PENDING,
        ]);
    }

    protected function requestDeleteApproval(Model $record): void
    {
        $approvalModel = config('filamentapproval.approval_request_model', ApprovalRequest::class);

        $approvalModel::create([
            'request_type' => ApprovalTypeEnum::DELETE,
            'requester_id' => auth()->id(),
            'approvable_type' => $this->getModel(),
            'approvable_id' => $record->getKey(),
            'attributes' => [],
            'relationships' => [],
            'original_data' => $record->getAttributes(),
            'resource_class' => $this->getResource(),
            'status' => ApprovalStatusEnum::PENDING,
        ]);
    }

    /**
     * Simple attribute diff; override in your project if needed.
     */
    protected function detectAttributeChanges(Model $record, array $incoming): array
    {
        $original = $record->getAttributes();
        $diff = [];

        foreach ($incoming as $key => $value) {
            if (! array_key_exists($key, $original)) {
                $diff[$key] = $value;

                continue;
            }

            if ($original[$key] !== $value) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }
}
