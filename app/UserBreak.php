<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserBreak extends Model
{
    protected $table = 'user_breaks';

    protected $fillable = [
        'user_id',
        'break_start',
        'break_end'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function isUserOnBreak($userId)
    {
        return self::where('user_id', $userId)
            ->whereNull('break_end') // A break is active if it has no end time
            ->exists();
    }
}
