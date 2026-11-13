<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'full_name',
        'preferred_name',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone',
        'avatar_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
