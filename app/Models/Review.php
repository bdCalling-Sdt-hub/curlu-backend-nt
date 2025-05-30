<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo(SalonService::class, 'service_id', 'id');
    }

    public function salon()
    {
        return $this->belongsTo(Salon::class, 'salon_id', 'id');
    }
}
