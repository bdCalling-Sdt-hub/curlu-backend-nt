<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $guarded=['id'];

    public function services()
    {
        return $this->hasMany(SalonService::class);
    }
    public function salon_services(): HasMany
    {
        return $this->hasMany(SalonService::class);
    }



}
