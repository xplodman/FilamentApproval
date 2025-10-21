<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Xplodman\FilamentApproval\Enums\ApprovalStatusEnum;
use Xplodman\FilamentApproval\Enums\ApprovalTypeEnum;

class ApprovalRequest extends Model
{
    protected $casts = [
        'attributes' => 'array',
        'relationships' => 'array',
        'original_data' => 'array',
        'decided_at' => 'datetime',
        'status' => ApprovalStatusEnum::class,
    ];

    protected $attributes = [
        'request_type' => 'edit',
        'status' => 'pending',
    ];

    protected $fillable = [
        'request_type',
        'requester_id',
        'approvable_type',
        'approvable_id',
        'attributes',
        'relationships',
        'original_data',
        'resource_class',
        'status',
        'decided_by_id',
        'decided_reason',
        'decided_at',
    ];

    public function approvable()
    {
        return $this->morphTo();
    }

    public function requester()
    {
        return $this->belongsTo(config('filamentapproval.user_model', config('auth.providers.users.model', 'App\Models\User')), 'requester_id');
    }

    public function decidedBy()
    {
        return $this->belongsTo(config('filamentapproval.user_model', config('auth.providers.users.model', 'App\Models\User')), 'decided_by_id');
    }

    public function isCreateRequest(): bool
    {
        return $this->request_type === ApprovalTypeEnum::CREATE;
    }

    public function isEditRequest(): bool
    {
        return $this->request_type === ApprovalTypeEnum::EDIT;
    }

    public function isDeleteRequest(): bool
    {
        return $this->request_type === ApprovalTypeEnum::DELETE;
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatusEnum::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === ApprovalStatusEnum::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === ApprovalStatusEnum::REJECTED;
    }
}
