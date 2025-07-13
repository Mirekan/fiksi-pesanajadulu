<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'table_id',
        'status',
        'amount',
        'reservation_time',
    ];

    /**
     * Boot function to generate UUID on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Order status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ADVANCE_PAID = 'advance_paid'; // 50% paid, table reserved
    const STATUS_CONFIRMED = 'confirmed'; // Customer arrived, ready to serve
    const STATUS_COMPLETED = 'completed'; // Fully paid and order completed
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get advance payment amount (50% of total order)
     */
    public function getAdvanceAmountAttribute()
    {
        return $this->amount * 0.5;
    }

    /**
     * Get remaining payment amount (50% of total order)
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
        return in_array($this->status, [self::STATUS_ADVANCE_PAID, self::STATUS_CONFIRMED, self::STATUS_COMPLETED]);
    }

    /**
     * Check if order is fully paid and completed
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
