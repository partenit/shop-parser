<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'code',
        'url',
        'name',
        'description',
        'is_available',
    ];

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function features()
    {
        return $this->hasMany(Feature::class);
    }
}
