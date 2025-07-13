<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    /** @use HasFactory<\Database\Factories\MenuFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'image',
        'category',
        'restaurant_id',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Decrement stock when item is ordered
     */
    public function decrementStock($quantity)
    {
        if ($this->stock < $quantity) {
            throw new \Exception("Insufficient stock for menu item: {$this->name}. Available: {$this->stock}, Requested: {$quantity}");
        }

        $this->decrement('stock', $quantity);
        return $this;
    }

    /**
     * Increment stock when order is cancelled/failed
     */
    public function incrementStock($quantity)
    {
        $this->increment('stock', $quantity);
        return $this;
    }

    /**
     * Check if item is in stock
     */
    public function isInStock($quantity = 1)
    {
        return $this->stock >= $quantity;
    }

    /**
     * Check if item is out of stock
     */
    public function isOutOfStock()
    {
        return $this->stock <= 0;
    }

    /**
     * Scope for available items (in stock)
     */
    public function scopeAvailable($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope for out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }
}
