<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_approval::request');
    }

    public function view(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('view_approval::request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_approval::request');
    }

    public function update(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('update_approval::request');
    }

    public function delete(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('delete_approval::request');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_approval::request');
    }

    public function forceDelete(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('force_delete_approval::request');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_approval::request');
    }

    public function restore(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('restore_approval::request');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_approval::request');
    }

    public function replicate(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('replicate_approval::request');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_approval::request');
    }

    public function approve(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can(config('filamentapproval.permissions.approve', 'approve_approval::request'));
    }

    public function reject(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can(config('filamentapproval.permissions.reject', 'reject_approval::request'));
    }
}
