<?php

namespace App\Http\Controllers;

use App\UserBreak;
use App\UserDetails;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Session;

class BreakTimeController extends Controller
{
    public function __construct()
    {
        $this->beforeFilter(function(){
            $this->counter();
        });
        date_default_timezone_set(Auth::user()->CompanyUser->time_zone);
    }
    public function counter()
    {
        $maxToday= UserDetails::maxRowToday();
        if($maxToday) {
            $timeDiff = strtotime(date('Y-m-d H:i:s')) - strtotime($maxToday->login_time);
            if($timeDiff == 0)
                $timeDiff = 1;
            Session::put('timeTrack', $timeDiff);
        }
    }
    public function breakStart()
    {
        //\Log::info(Carbon::now());
        $userId = Auth::id();

        // Ensure the user is punched in
        $isPunchedIn = UserDetails::where('user_id', $userId)
            ->whereNull('logout_time')
            ->exists();
        //return json_encode($isPunchedIn);
        if (!$isPunchedIn) {
            return redirect()->back()->with('error', 'You need to punch in first.');
        }

        // Check if already on a break
        if (UserBreak::isUserOnBreak($userId)) {
            return redirect()->back()->with('error', 'You are already on a break.');
        }


        // Start Break
        UserBreak::create([
            'user_id' => $userId,
            'break_start' => Carbon::now()
        ]);
        return redirect()->back()->with('success', 'Break started.');
    }

    public function breakEnd()
    {
        $userId = Auth::id();

        // Get the last break that hasn't ended
        $lastBreak = UserBreak::where('user_id', $userId)
            ->whereNull('break_end')
            ->latest()
            ->first();
        //return $lastBreak;
        if (!$lastBreak) {
            return redirect()->back()->with('error', 'No active break found.');
        }

        // End Break
        $lastBreak->update(['break_end' => Carbon::now()]);
        return redirect()->back()->with('success', 'Break ended.');
    }
}
