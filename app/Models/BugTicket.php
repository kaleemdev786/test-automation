<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugTicket extends Model
{
    // Status constants
    const STATUS_PENDING    = 'pending';
    const STATUS_APPROVED   = 'approved';
    const STATUS_PROCESSING = 'processing';
    const STATUS_FIXED      = 'fixed';
    const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'title',
        'module',
        'priority',
        'laravel_version',
        'description',
        'image_path',
        'status',
        'fix_result',
        'error_message',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'fix_result'  => 'array',
        'approved_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFixed(): bool
    {
        return $this->status === self::STATUS_FIXED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function priorityBadgeClass(): string
    {
        return match($this->priority) {
            'critical' => 'badge-error',
            'high'     => 'badge-warning',
            'medium'   => 'badge-info',
            default    => 'badge-ghost',
        };
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'fixed'      => 'badge-success',
            'failed'     => 'badge-error',
            'processing' => 'badge-warning',
            'approved'   => 'badge-info',
            default      => 'badge-ghost',
        };
    }
}
