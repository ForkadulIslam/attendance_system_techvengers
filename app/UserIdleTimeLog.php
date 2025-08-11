<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserIdleTimeLog extends Model
{
    protected $table = 'user_idle_time_logs';
    protected $fillable = ['user_id', 'log_date', 'time_start', 'time_end', 'time_count_in_second'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper to format seconds to HH:MM:SS
    public function getFormattedIdleTimeAttribute()
    {
        $hours = floor($this->time_count_in_second / 3600);
        $minutes = floor(($this->time_count_in_second % 3600) / 60);
        $seconds = $this->time_count_in_second % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
