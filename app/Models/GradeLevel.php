<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_number',
    ];

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }
}
