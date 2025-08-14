<?php

namespace App\Http\Controllers;

use App\Company;
use App\Leave;
use App\NoticeBoard;
use App\UserBreak;
use App\UserDetails;
use App\UserIdleTimeLog;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Validator;

class ApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $timeZone = 'America/New_York';
    public function __construct()
    {
        $this->timeZone = Company::first()->time_zone;
        date_default_timezone_set($this->timeZone);
    }

    public function breakStart(Request $request)
    {
        $userId = (int) $request->header('user-id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing user ID'], 400);
        }

        $isPunchedIn = UserDetails::where('user_id', $userId)
            ->whereNull('logout_time')
            ->exists();

        if (!$isPunchedIn) {
            return response()->json([
                'success' => false,
                'message' => 'Not punched in'
            ], 400);
        }

        if (UserBreak::isUserOnBreak($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Already on break'
            ], 400);
        }

        UserBreak::create([
            'user_id' => $userId,
            'break_start' => Carbon::now()
        ]);

        return response()->json(['success' => true, 'message' => 'AUTO Break started']);
    }

    public function breakEnd(Request $request)
    {
        $userId = (int) $request->header('user-id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing user ID'], 400);
        }

        $lastBreak = UserBreak::where('user_id', $userId)
            ->whereNull('break_end')
            ->latest()
            ->first();

        if (!$lastBreak) {
            return response()->json([
                'success' => false,
                'message' => 'No active break found'
            ], 400);
        }

        $lastBreak->update(['break_end' => Carbon::now()]);

        return response()->json(['success' => true, 'message' => 'AUTO Break ended']);
    }

    public function punchOut(Request $request)
    {
        $userId = (int) $request->header('user-id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing user ID'], 400);
        }

        $lastRow = UserDetails::maxRow($userId);
        if (!$lastRow) {
            return response()->json(['success' => false, 'message' => 'No active session found'], 404);
        }

        // Ensure not already punched out
        if ($lastRow->logout_date === '0000-00-00' || $lastRow->logout_date === null) {
            $punchOut = UserDetails::find($lastRow->id);
            $punchOut->logout_date = date('Y-m-d');
            $punchOut->logout_time = date('Y-m-d H:i:s');
            $punchOut->save();

            return response()->json(['success' => true, 'message' => 'Punch Out successful']);
        }

        return response()->json(['success' => false, 'message' => 'Already punched out'], 400);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function punchIn(Request $request)
    {
        $userId = (int) $request->header('user-id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Missing user ID'], 400);
        }

        $user = \App\User::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid user'], 404);
        }

        $lastRow = UserDetails::maxRow($userId);
        $currentTime = time();
        $status = 'Present';

        if ($lastRow && $user->time) {
            if (date('Y-m-d', $currentTime) === $lastRow->login_date) {
                $status = 'Present';
            } elseif (date('H:i:s', $currentTime) > $user->time) {
                $status = 'Late';
            }
        }

        $punchIn = new UserDetails();
        $punchIn->status = $status;
        $punchIn->user_id = $user->id;
        $punchIn->user_name = $user->username;
        $punchIn->login_time = date('Y-m-d H:i:s', $currentTime);
        $punchIn->login_date = date('Y-m-d', $currentTime);
        $punchIn->save();

        return response()->json([
            'success' => true,
            'message' => $status === 'Late' ? 'You are late today!' : 'Punch In successful',
            'status' => $status
        ]);
    }

    public function login(Request $request)
    {

        $credentials = [
            'username' => $request->input('username'),
            'password' => $request->input('password'),
            'status' => 1
        ];
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials or inactive account.'
            ], 401);
        }

        $user = Auth::user();

        // Disallow Admins (user_label == 1) from logging in to Tracker
        if ($user->user_label == 1) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Only members can use this tracker.'
            ], 403);
        }

        // Optional IP check for label 2 users
        if ($user->user_label == 2 && $user->ip_address && $user->ip_address !== $request->ip()) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'IP address mismatch.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'user_id' => $user->id,
            'name' => $user->username,
        ]);
    }

    public function userStatus(Request $request)
    {
        $userId = (int) $request->input('user_id');

        // Get last punch record
        $lastPunch = UserDetails::maxRow($userId);

        $status = 'Punch Out'; // default

        if (!empty($lastPunch) && $lastPunch->logout_date === '0000-00-00') {
            // User is punched in

            // Check for active break
            $activeBreak = UserBreak::where('user_id', $userId)
                ->whereNull('break_end')
                ->latest()
                ->first();

            if ($activeBreak) {
                $status = 'Break End'; // user is on break
            } else {
                $status = 'Break Start'; // user is working
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $status,
                'punchedIn' => (!empty($lastPunch) && $lastPunch->logout_date === '0000-00-00'),
                'onBreak' => isset($activeBreak),
            ]
        ]);
    }

    public function screenshotUpload(Request $request){
        $userId = $request->header('user-id');
        $filename = $request->header('screenshot-name', 'screenshot.jpg');

        if (!$userId || !$request->getContent()) {
            return response()->json(['message' => 'Invalid data'], 400);
        }

        $path = "screenshots/{$userId}/" . $filename;
        //return $path;
        Storage::put($path, $request->getContent());
//
        return response()->json(['message' => 'Screenshot saved', 'filename' => $path]);
    }

    public function idleTimeStore(Request $request)
    {
        //\Log::info($request->all());

        // Define our required timezone
        $requiredTimezone = $this->timeZone;

        // Validate request (removed timezone validation)
        $validator = Validator::make($request->all(), [
            'totalIdleTime' => 'required|integer|min:0',
            'timeStart' => 'required|date_format:Y-m-d H:i:s',
            'timeEnd' => 'required|date_format:Y-m-d H:i:s|after:timeStart',
            'localTimezone' => 'required|timezone'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->header('user-id');
        $idleSeconds = $request->input('totalIdleTime');
        $clientTimezone = $request->input('localTimezone');
        // Need to check for user is on break by userId to ensure idle time wont store during break time.

        try {
            // Convert times from client timezone to our required timezone
            $timeStart = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $request->input('timeStart'),
                $clientTimezone
            )->setTimezone($requiredTimezone);
            $timeEnd = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $request->input('timeEnd'),
                $clientTimezone
            )->setTimezone($requiredTimezone);

            $logDate = $timeStart->format('Y-m-d');



            // Create new record for each idle period
            $log = UserIdleTimeLog::create([
                'user_id' => $userId,
                'log_date' => $logDate,
                'time_start' => $timeStart,
                'time_end' => $timeEnd,
                'time_count_in_second' => $idleSeconds
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Idle time recorded successfully',
                'data' => [
                    'period' => $log->formatted_idle_time,
                    'start' => $timeStart->format('Y-m-d H:i:s'),
                    'end' => $timeEnd->format('Y-m-d H:i:s'),
                    'total_seconds' => $log->time_count_in_second,
                    'original_timezone' => $clientTimezone,
                    'stored_timezone' => $requiredTimezone
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Idle time recording failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record idle time',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUser($id)
    {
        $user = \App\User::find($id);
        $punchData = \App\UserDetails::where('user_id', $id)
            ->where('login_date', date('Y-m-d'))
            ->latest()
            ->first();

        $totalBreakSeconds = DB::table('user_breaks')
            ->where('user_id', $id)
            ->where('break_start', '>=', date('Y-m-d') . ' 00:00:00')
            ->where('break_start', '<=', date('Y-m-d') . ' 23:59:59')
            ->whereNotNull('break_end')
            ->sum(DB::raw('TIME_TO_SEC(TIMEDIFF(break_end, break_start))'));

        $totalBreakDurationFormatted = '00:00';
        if ($totalBreakSeconds) {
            $totalBreakDurationFormatted = gmdate('H:i', $totalBreakSeconds);
        }

        $loggedInAtFormatted = '';
        if ($punchData && $punchData->login_time) {
            $loggedInAtFormatted = Carbon::parse($punchData->login_time)->format('Y-m-d H:i:s');
        }

        $totalIdleSeconds = 0;
        if ($punchData) {
            $idleTimeLogQuery = UserIdleTimeLog::where('user_id', $id)
                ->where('log_date', Carbon::parse($punchData->login_time)->toDateString())
                ->where('time_start', '>=', Carbon::parse($punchData->login_time)->toTimeString());
            if ($punchData->logout_date != '0000-00-00') {
                $idleTimeLogQuery->where('log_date', Carbon::parse($punchData->logout_time)->toDateString())
                    ->where('time_end', '<=', Carbon::parse($punchData->logout_time)->toTimeString());
            }
            $totalIdleSeconds = $idleTimeLogQuery->sum('time_count_in_second');
        }

        $netIdleSeconds = $totalIdleSeconds - $totalBreakSeconds;
        if ($netIdleSeconds < 0) {
            $netIdleSeconds = 0;
        }
        $netIdleTimeFormatted = gmdate('H:i:s', $netIdleSeconds);

        return response()->json([
            'id' => $user->id,
            'name' => $user->username,
            'total_break_duration' => $totalBreakDurationFormatted,
            'logged_in_at' => $loggedInAtFormatted,
            'total_idle_time' => $netIdleTimeFormatted,
        ]);
    }

    public function getDailyIdleTime(Request $request)
    {
        $userId = $request->header('user-id');
        $date = $request->input('date', date('Y-m-d'));

        $logs = UserIdleTimeLog::where('user_id', $userId)
            ->where('log_date', $date)
            ->orderBy('time_start')
            ->get();

        $totalSeconds = $logs->sum('time_count_in_second');

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'periods' => $logs,
                'total_seconds' => $totalSeconds,
                'total_formatted' => $this->secondsToTime($totalSeconds)
            ]
        ]);
    }

    private function secondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
