<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'name',
        'nama',
    ];

    public function setNamaAttribute($value)
    {
        $this->attributes['code'] = $value;
        $this->attributes['name'] = $this->attributes['name'] ?? ucfirst($value);
    }

    public function getNamaAttribute()
    {
        return $this->attributes['code'] ?? $this->attributes['name'] ?? null;
    }

    public function profiles()
    {
        return $this->hasMany(UserProfile::class, 'role_id', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')->withTimestamps();
    }
}
