<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id', 'description', 'status'
    ];

    /**
     * Relationship with user
     * Includes soft deleted users to prevent orphaned tasks
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
