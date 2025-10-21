<?php

namespace Xplodman\FilamentApproval\Policies;

use App\Models\ApprovalRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny($user): bool
    {
        return $user->can('view_any_approval::request');
    }

    public function view($user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('view_approval::request');
    }

    public function create($user): bool
    {
        return $user->can('create_approval::request');
    }

    public function update($user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('update_approval::request');
    }

    public function delete($user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('delete_approval::request');
    }

    public function deleteAny($user): bool
    {
        return $user->can('delete_any_approval::request');
    }

    public function forceDelete($user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('force_delete_approval::request');
    }

    public function forceDeleteAny($user): bool
    {
        return $user->can('force_delete_any_approval::request');
    }

    public function restore($user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('restore_approval::request');
    }

    public function restoreAny($user): bool
    {
        return $user->can('restore_any_approval::request');
    }

    public function replicate($user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can('replicate_approval::request');
    }

    public function reorder($user): bool
    {
        return $user->can('reorder_approval::request');
    }

    public function approve($user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can(config('filamentapproval.permissions.approve', 'approve_approval::request'));
    }

    public function reject($user, ApprovalRequest $approvalRequest): bool
    {
        return $user->can(config('filamentapproval.permissions.reject', 'reject_approval::request'));
    }
}
