<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'price', 'category',
        'available', 'image', 'sort_order'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'available' => 'boolean',
    ];

    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}