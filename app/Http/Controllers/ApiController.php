<?php

namespace App\Http\Controllers;

use App\Company;
use App\Leave;
use App\NoticeBoard;
use App\UserBreak;
use App\UserDetails;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        date_default_timezone_set(Company::first()->time_zone);
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
}
