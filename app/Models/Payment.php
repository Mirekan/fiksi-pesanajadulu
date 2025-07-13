<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'amount',
        'payment_method',
        'status',
        'transaction_id',
        'payment_type',
        'gross_amount',
        'remaining_amount',
        'transaction_status',
        'fraud_status',
        'payment_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_data' => 'array',
        'gross_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    /**
     * Payment types
     */
    const TYPE_ADVANCE = 'advance'; // 50% online payment
    const TYPE_REMAINING = 'remaining'; // 50% manual payment at restaurant
    const TYPE_FULL = 'full'; // 100% payment (if policy changes)

    /**
     * Payment status
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ADVANCE_PAID = 'advance_paid'; // 50% paid online
    const STATUS_COMPLETED = 'completed'; // 100% paid (both advance + remaining)
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get advance payment amount (50% of total)
     */
    public function getAdvanceAmountAttribute()
    {
        return $this->amount * 0.5;
    }

    /**
     * Get remaining payment amount (50% of total)
     */
    public function getRemainingAmountAttribute()
    {
        return $this->amount * 0.5;
    }

    /**
     * Check if advance payment is completed
     */
    public function isAdvancePaid()
    {
        return in_array($this->status, [self::STATUS_ADVANCE_PAID, self::STATUS_COMPLETED]);
    }

    /**
     * Check if payment is fully completed
     */
    public function isFullyPaid()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Mark advance payment as completed
     */
    public function markAdvancePaid()
    {
        $this->update(['status' => self::STATUS_ADVANCE_PAID]);
    }

    /**
     * Mark payment as fully completed
     */
    public function markCompleted()
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
