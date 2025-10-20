<?php

namespace Xplodman\FilamentApproval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalRequest extends Model
{
    use SoftDeletes;

    public const TYPE_CREATE = 'create';
    public const TYPE_EDIT = 'edit';
    public const TYPE_DELETE = 'delete';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'rejected';

    protected $casts = [
        'attributes' => 'array',
        'relationships' => 'array',
        'original_data' => 'array',
        'decision_at' => 'datetime',
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
        'decision_by_id',
        'decision_reason',
        'decision_at',
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
        return $this->belongsTo(config('filamentapproval.user_model', config('auth.providers.users.model', 'App\Models\User')), 'decision_by_id');
    }

    public function isCreateRequest(): bool
    {
        return $this->request_type === self::TYPE_CREATE;
    }

    public function isEditRequest(): bool
    {
        return $this->request_type === self::TYPE_EDIT;
    }

    public function isDeleteRequest(): bool
    {
        return $this->request_type === self::TYPE_DELETE;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
