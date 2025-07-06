<?php namespace App\Http\Controllers;

use App\Designation;
use App\HolidayInfo;
use App\Leave;
use App\LeaveCategories;
use App\Messages;
use App\NoticeBoard;
use App\User;
use App\UserBreak;
use App\UserDetails;
use App\UserIdleTimeLog;
use App\UserRegistered;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Request;
use Response;
use Session;
use DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;

class CompanyController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set(Auth::user()->Company->time_zone);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function  getIndex()
    {
        LoginController::autoPunchOutCheck(\App\User::UserIdList());
        $data['startDate'] = date('Y-m-d');
        $data['endDate'] = date('Y-m-d');
        $data['attendanceReport'] = UserDetails::
        select(DB::raw('timediff(logout_time,login_time) as timediff'),
            'login_date', 'logout_date', 'id', 'login_time', 'logout_time', 'user_id')
            ->whereHas('User', function ($q) {
                $q->where('company_id', Auth::user()->company_id);
            })
            ->where('login_date', '>=', $data['startDate'])
            ->where('logout_date', '<=', $data['endDate'])
            ->where('logout_time', '!=', '0000-00-00 00:00:00')
            ->orderBy('id', 'ASC')
            ->get();
        $data['activeUser'] = UserDetails::where('login_date', date('Y-m-d', time()))
            ->whereHas('User', function ($q) {
                $q->where('company_id', Auth::user()->company_id);
            })
            ->groupBy('user_id')
            ->orderBy('id')
            ->get();
        $data['lateUser'] = UserDetails::where('login_date', date('Y-m-d', time()))
            ->whereHas('User', function ($q) {
                $q->where('company_id', Auth::user()->company_id);
            })
            ->where('status', 'Late')
            ->groupBy('user_id')
            ->orderBy('id')
            ->get();

        $data['totalUser'] = \App\User::where('company_id', Auth::user()->company_id)
            ->where('user_label', '>', 1)->count();
        $data['withLeaveNotification'] = Leave::whereHas('User', function ($q) {
            $q->where('company_id', Auth::user()->company_id);
        })->where('admin_noti_status', 1)->count();
        $data['allNotice'] = NoticeBoard::orderBy('id', 'DESC')->paginate(10);
        $data['activityWiseUserList'] =  $this->activityWiseUserList();
        //return $data['activityWiseUserList'];

        $allUsers = \App\User::where('user_label', 2)
            ->where('status', 1)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->username,
                    'link' => url("company/report?s_date=".date('Y-m-d')."&e_date=".date('Y-m-d')."&id=".$user->id)
                ];
            });
        $data['allUsers'] = $allUsers;
        //return $this->getOnlineUsers();
        return view('Company.home', $data);
    }


    public function getOnlineUsers()
    {
        $ablyKey = 'BEm5bw.24xxVQ:3nIhmsZUfMy_KRKWtOd5KcitYvWF-5VAUeTCieD_41k'; // e.g., BEm5bw.xxxxx
        $channel = 'tracker-presence';

        $client = new Client();

        try {
            $response = $client->request('GET', "https://rest.ably.io/channels/{$channel}/presence", [
                'auth' => [$ablyKey, ''], // HTTP Basic Auth
                'query' => [
                    'limit' => 100
                ]
            ]);

            $body = json_decode($response->getBody(), true);
            $presenceData = $body['items'] ?? [];

            $users = collect($presenceData)->map(function ($item) {
                return [
                    'clientId' => $item['clientId'],
                    'userId' => preg_match('/(\d+)$/', $item['clientId'], $m) ? $m[1] : null,
                    'data' => $item['data'] ?? null,
                ];
            });

            return $users;
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch presence', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @return Redirect|\Illuminate\View\View
     */
    public function anyCreateUser()
    {
        if (Input::all()) {
            //            return 'fsd';
            $ignoreID = Auth::user()->id;
            $rules = array(
                'username' => "unique:users,username|alpha_dash",
                'password' => 'required|min:6|max:10',
                'ip_address' => 'sometimes|ip'
            );
            /* Laravel Validator Rules Apply */
            $validator = Validator::make(Input::all(), $rules);
            if ($validator->fails()):
                $validationError = $validator->messages()->first();
                Session::flash('flashError', $validationError);
                return redirect('company/create-user');
            else:
                $userCreate = new \App\User();
                $userCreate->username = trim(Input::get('username'));
                $userCreate->password = Hash::make(trim(Input::get('password')));
                $userCreate->ip_address = trim(Input::get('ip_address'));
                $userCreate->company_id = Auth::user()->company_id;
                $userCreate->save();
            endif;
            Session::flash('flashSuccess', 'User Created Successfully');
            return redirect('company/all-user');
        }
        return view('Company.createUser');
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getAllUser()
    {
        $user = new \App\User();
        $data['allUser'] = \App\User::where('company_id', Auth::user()->company_id)
            ->where('user_label', '>', 1)->paginate(10);
        $data['userTable'] = view('Company.userTable', $data);
        return view('Company.allUser', $data);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getSearchUser()
    {
        $search = Input::get('search');
        $user = new \App\User();
        $data['allUser'] = $user->where("username", 'like', '%'. $search. '%')
            ->where('company_id', Auth::user()->company_id)
            ->where('user_label', '>', 1)->paginate(10);
        return view('Company.userTable', $data);
    }

    /**
     * @param null $id
     * @return \Illuminate\View\View|string
     */
    public function anyStatusChange($id = null)
    {
        $status = Input::get('status');
        $user = \App\User::find($id);
        if ($status == 'active')
            $user->status = 1;
        if ($status == 'inactive')
            $user->status = 0;
        $user->save();
        Session::flash('flashSuccess', 'Status Changed');
        return 'true';
        $user = new \App\User();
        $data['allUser'] = $user->allUser();
        return view('Company.allUserAjax', $data);
    }

    /**
     * @return mixed
     */
    public function anyAddIp()
    {
        $response = array();
        $rules = array(
            'ip_address' => 'required|ip'
        );
        /* Laravel Validator Rules Apply */
        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()):
            $errorMessage = $validator->messages()->first();
            $response['type'] = 'error';
            $response['info'] = $errorMessage;
            return Response::json($response);
        else:
            $userCreate = \App\User::find(Input::get('id'));
            $userCreate->ip_address = trim(Input::get('ip_address'));
            $userCreate->save();
        endif;
        Session::flash('flashSuccess', 'IP Added Successfully');
        $user = new \App\User();
        $data['allUser'] = $user->allUser();
        $response['type'] = 'success';
        $response['info'] = (String)view('Company.allUserAjax', $data);
        return Response::json($response);
    }

    /**
     * @param $id
     * @return \Illuminate\View\View|string
     */
    public function postRemoveIp($id)
    {
        $user = \App\User::find($id);
        $user->ip_address = '';
        $user->save();
        Session::flash('flashSuccess', 'IP Removed');
        return 'true';
        $user = new \App\User();
        $data['allUser'] = $user->allUser();
        return view('Company.allUserAjax', $data);
    }

    /**
     * @param $id
     * @return \Illuminate\View\View
     */
    public function anyUserUpdate($id)
    {
        $data['user'] = \App\User::find($id);
        $data['designations'] = Designation::all();
        return view('Company.userUpdate', $data);
    }

    /**
     * @param $id
     * @return string
     */
    public function postUpdateUserUsername($id)
    {
        $rules = array(
            'username' => "required|alpha_dash|unique:users,username,$id",
        );
        /* Laravel Validator Rules Apply */
        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()):
            return $validator->messages()->first();
        else:
            $userUpdate = \App\User::find($id);
            $userUpdate->username = trim(Input::get('username'));
            $userUpdate->save();
        endif;
        return 'true';
    }

    /**
     * @param $id
     * @return string
     */
    public function  postUpdateUserPassword($id)
    {
        $rules = array(
            'password' => "required|min:6|max:10",
        );
        /* Laravel Validator Rules Apply */
        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()):
            return $validator->messages()->first();
        else:
            $userUpdate = \App\User::find($id);
            $userUpdate->password = Hash::make(Input::get('password'));
            $userUpdate->save();
        endif;
        return 'true';
    }

    /**
     * @param $id
     * @return string
     */
    public function  postUpdateUserTime($id)
    {
        $rules = array(
            'time' => "required",
        );
        /* Laravel Validator Rules Apply */
        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()):
            return $validator->messages()->first();
        else:
            $userUpdate = \App\User::find($id);
            $userUpdate->time = Input::get('time');
            $userUpdate->save();
        endif;
        return 'true';
    }

    /**
     * @param $id
     * @return string
     */
    public function  postUpdateAutoPunchOutTime($id)
    {
        $rules = array(
            'time' => "required|date_format:H:i:s",
        );
        /* Laravel Validator Rules Apply */
        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()):
            return $validator->messages()->first();
        else:
            $userUpdate = \App\User::find($id);
            $userUpdate->auto_punch_out_time = Input::get('time');
            $userUpdate->save();
        endif;
        return 'true';
    }


    public function  postUpdateYearlyLeaveBalance($id)
    {
        $rules = array(
            'yearly_leave_balance' => "required",
        );
        /* Laravel Validator Rules Apply */
        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()):
            return $validator->messages()->first();
        else:
            $userUpdate = \App\User::find($id);
            $userUpdate->yearly_leave_balance = Input::get('yearly_leave_balance');
            $userUpdate->save();
        endif;
        return 'true';
    }

    /**
     * @return \Illuminate\View\View|string
     */
    public function anyUpdateMe()
    {
        if (Input::all()) {
            $companyID = Input::get('companyID');
            $id = Auth::user()->id;
            $rules = array(
                'company_name' => "required|unique:company_info,company_name,$companyID",
                'company_email' => "required|email|unique:company_info,company_email,$companyID",
                'phone' => "required",
                'username' => "required|alpha_dash|unique:users,username,$id",
                'user_first_name' => "required",
                'user_last_name' => 'required'
            );
            /* Laravel Validator Rules Apply */
            $validator = Validator::make(Input::all(), $rules);
            if ($validator->fails()):
                return $validator->messages()->first();
            else:
                $userUpdate = \App\User::find($id);
                $userUpdate->username = trim(Input::get('username'));
                $userUpdate->user_first_name = trim(Input::get('user_first_name'));
                $userUpdate->user_last_name = trim(Input::get('user_last_name'));
                $userUpdate->Company->company_name = trim(Input::get('company_name'));
                $userUpdate->Company->company_email = trim(Input::get('company_email'));
                $userUpdate->Company->phone = trim(Input::get('phone'));
                $userUpdate->push();
            endif;
            return 'true';
        } else {
            $data['myInfo'] = \App\User::find(Auth::user()->id);
            return view('Company.companyUpdate', $data);
        }
    }

    /**
     * @return \Illuminate\View\View|string
     */
    public function anyChangePassword()
    {
        if (Input::all()) {
            $rules = array(
                'new_password' => 'required|same:confirm_new_password|min:6',
                'current_pass' => 'required|password_check',
            );
            $messages = array(
                'new_password.same' => 'New Password and Confirm password are not Matched',
            );
            /* Laravel Validator Rules Apply */
            $validator = Validator::make(Input::all(), $rules, $messages);
            if ($validator->fails()):
                return $validator->messages()->first();
            else:
                $userUpdate = \App\User::find(Auth::user()->id);
                $userUpdate->password = Hash::make(Input::get('new_password'));
                $userUpdate->save();
            endif;
            return 'true';
        } else {
            return view('Company.passwordChange');
        }
    }

    /**
     * @return \Illuminate\View\View|string
     */
    public function anyCreateHoliday()
    {
        if (Input::all()) {
            $holidayList = Input::get('holiday');

            foreach ($holidayList as $holiday) {
                if ($holiday == '')
                    return 'Please Fill All the Field';
                $checkExisting = HolidayInfo::where('holiday', $holiday)
                    ->first();
                if ($checkExisting)
                    return "$holiday has Already Added as a Holiday";
            }

            foreach ($holidayList as $holiday) {
                $saveHoliday = new HolidayInfo();
                $saveHoliday->holiday = $holiday;
                $saveHoliday->save();
            }
            Session::flash('flashSuccess', 'Holiday Created Successfully');
            return 'true';

        } else {
            return view('Company.createHoliday');
        }
    }

    /**
     * @return \Illuminate\View\View
     */
    public function anyAllHoliday()
    {
        $data['allHoliday'] = HolidayInfo::orderBy('holiday', 'desc')->get();
        return view('Company.allHoliday', $data);
    }

    /**
     * @param $id
     * @return string
     */
    public function anyDeleteHoliday($id)
    {
        $holidayDelete = HolidayInfo::find($id);
        $holidayDelete->delete();
        return 'true';
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getAllLeave()
    {
        Leave::whereHas('User', function ($q) {
            $q->where('company_id', Auth::user()->company_id);
        })
            ->update(array('admin_noti_status' => 0));
        $data['allLeave'] = Leave::with(['LeaveCategories'])->whereHas('User', function ($q) {
            $q->where('company_id', Auth::user()->company_id);
        })
            ->whereHas('LeaveCategories', function ($q) {
                $q->where('deleted_at');
            })
            ->orderBy('id', 'desc')
            ->paginate(15);
        //return $data['allLeave'];
        $data['leaveTable'] = view('Company.leaveTable', $data);
        return view('Company.allLeave', $data);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getSearchLeave()
    {
        $data['allLeave'] = Leave::whereHas('User', function ($q) {
            $search = Input::get('search');
            $q->where('company_id', Auth::user()->company_id);
            $q->where('username', 'like', '%' . $search . '%');
        })
            ->whereHas('LeaveCategories', function ($q) {
                $q->where('deleted_at');
            })
            ->orderBy('id', 'desc')
            ->paginate(15);
        return view('Company.leaveTable', $data);
    }

    /**
     * @param $id
     * @return string
     */
    public function getChangeLeaveStatus($id)
    {

        if (Input::get('status') == 'grant') {
            $categoryID = Input::get('categoryID');
            $first_day_this_year = date('Y-01-01');
            $last_day_this_year = date('Y-12-t');
            $leaveNumber = Leave::where('leave_category_id', $categoryID)
                ->where('leave_date', '>=', $first_day_this_year)
                ->where('leave_date', '<=', $last_day_this_year)
                ->where('user_id', Input::get('userID'))
                ->where('leave_status', 1)
                ->select(DB::raw('SUM(CASE WHEN is_half_day = 1 THEN 0.5 ELSE 1 END) as total_used'))
                ->value('total_used');
                //->count();
            $leaveNumber = floatval($leaveNumber);
            //return 'true';
            if($categoryID != 25){
                \Log::info($leaveNumber);
                \Log::info(Input::get('categoryBudget'));
                if ($leaveNumber == Input::get('categoryBudget') || $leaveNumber > Input::get('categoryBudget')){
                    \Log::info('In');
                    return 'false';
                }
            }
            $statusChange = Leave::find($id);
            $statusChange->leave_status = 1;
            $statusChange->user_noti_status = 1;
            $statusChange->save();
            Session::flash('success', 'Leave Status Changed');
            return 'true';
        } elseif (Input::get('status') == 'reject') {
            $statusChange = Leave::find($id);
            $statusChange->leave_status = 2;
            $statusChange->user_noti_status = 1;
            $statusChange->save();
            Session::flash('success', 'Leave Status Changed');
            return 'true';
        } elseif (Input::get('status') == 'delete') {
            $statusChange = Leave::find($id);
            $statusChange->leave_status = 2;
            $statusChange->save();
            $leaveDelete = Leave::find($id);
            $leaveDelete->delete();
            return 'true';
        }
        $data['allLeave'] = Leave::whereHas('User', function ($q) {
            $q->where('company_id', Auth::user()->company_id);
        })->get();
        return (String)view('Company.allLeaveAjax', $data);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function anyLeaveCategory()
    {
        if (Input::all()) {
            $checkExist = LeaveCategories::where('category', Input::get('category'))
                ->where('company_id', Auth::user()->company_id)
                ->first();
            if ($checkExist) {
                $response['type'] = 'error';
                $response['info'] = 'This Category Already Taken';
                return Response::json($response);
            }

            $rules = array(
                'category' => "required|alpha_dash",
                'category_num' => 'required|max:2',
            );
            /* Laravel Validator Rules Apply */
            $validator = Validator::make(Input::all(), $rules);
            if ($validator->fails()):
                $errorMessage = $validator->messages()->first();
                $response['type'] = 'error';
                $response['info'] = $errorMessage;
                return Response::json($response);
            else:
                $categoryCreate = new LeaveCategories();
                $categoryCreate->category = trim(Input::get('category'));
                $categoryCreate->category_num = Input::get('category_num');
                $categoryCreate->save();
                $response['type'] = 'success';
                $response['id'] = $categoryCreate->id;
                $data['allCategory'] = LeaveCategories::all();
//                    $response['info'] = (String) view('Company.leaveCategoryAjax',$data);
                $response['info'] = 'Leave Category Created Successfully';
                return Response::json($response);
            endif;

        } else {
            $data['allCategory'] = LeaveCategories::all();
            return view('Company.leaveCategory', $data);
        }
    }

    /**
     * @param $id
     * @return string
     */
    public function getDeleteLeaveCategory($id)
    {
        $leaveCategoriesDelete = LeaveCategories::find($id);
        Leave::where('leave_category_id', $id)->delete();
        $leaveCategoriesDelete->delete();
        return 'true';
    }

    public function getEditLeaveCategory($id)
    {
        $data['allCategory'] = LeaveCategories::all();
        $data['leaveCategory'] = LeaveCategories::find($id);
        //return $data['leaveCategory'];
        return view('Company.editLeaveCategory', $data);
    }
    public function postUpdateLeaveCategory(Request $request, $leaveCategoryId){
        $categoryCreate = LeaveCategories::find($leaveCategoryId);
        $categoryCreate->category = trim(Input::get('category'));
        $categoryCreate->category_num = Input::get('category_num');
        $categoryCreate->save();
        Session::flash('flashSuccess', 'Leave Category Updated Successfully');
        return redirect('company/leave-category');
    }

    /**
     * @return \Illuminate\View\View|string
     */
    public function getReport()
    {
        $data['startDate'] = Input::get('s_date');
        $data['endDate'] = Input::get('e_date');
        $data['id'] = Input::get('id');
        $data['userInfo'] = \App\User::where('company_id', Auth::user()->company_id)
            ->where('id', $data['id'])->first();
        if (!$data['userInfo'])
            return 'There User is Not Your Company';
        $data['attendanceReport'] = UserDetails::
        select(DB::raw('timediff(logout_time,login_time) as timediff'),
            'login_date', 'logout_date', 'id', 'login_time', 'logout_time', 'user_id', 'status')
            ->where('user_id', $data['id'])
            ->where('login_date', '>=', $data['startDate'])
            ->where('logout_date', '<=', $data['endDate'])
            ->orderBy('id', 'ASC')
            ->get()
            ->toArray();

        // ✅ Calculate total work time duration
        $totalSeconds = 0;
        foreach ($data['attendanceReport'] as $log) {
            if ($log['timediff']) {
                list($h, $m, $s) = explode(':', $log['timediff']);
                $totalSeconds += ($h * 3600) + ($m * 60) + $s;
            }
        }
        // Format to HH:MM:SS
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        $data['totalWorkingHour'] = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        $data['allDate'] = $this->getDatesFromRange($data['startDate'], $data['endDate']);
        $data['allHoliday'] = HolidayInfo::where('holiday', '>=', $data['startDate'])
            ->where('holiday', '<=', $data['endDate'])
            ->get()
            ->toArray();
        $data['allLeave'] = Leave::where('leave_date', '>=', $data['startDate'])
            ->where('leave_date', '<=', $data['endDate'])
            ->where('user_id', $data['id'])
            ->where('leave_status', 1)
            ->get()
            ->toArray();
        //return $data['attendanceReport'];
        return view('Company.attendanceLog', $data);

    }

    /**
     * @return \Illuminate\View\View
     */
    public function getSummeryReport()
    {
        $data['startDate'] = Input::get('s_date');
        $data['endDate'] = Input::get('e_date');
        $data['attendanceReport'] = UserDetails::
        select(DB::raw('timediff(logout_time,login_time) as timediff'),
            'login_date', 'logout_date', 'id', 'login_time', 'logout_time', 'user_id')
            ->whereHas('User', function ($q) {
                $q->where('company_id', Auth::user()->company_id);
            })
            ->where('login_date', '>=', $data['startDate'])
            ->where('logout_date', '<=', $data['endDate'])
            ->where('logout_time', '!=', '0000-00-00 00:00:00')
            ->orderBy('id', 'ASC')
            ->get();
        //return $data['attendanceReport'];
        $data['allDates'] = $this->getDatesFromRange($data['startDate'], $data['endDate']);
        return view('Company.summeryReport', $data);
    }

    /**
     * @return Redirect|\Illuminate\View\View
     */
    public function anyReportSummery()
    {
        if (Input::all()) {
            $data['startDate'] = Input::get('from');
            $data['endDate'] = Input::get('to');
            $inactiveUserIds = User::where('status', 0)->where('user_label', 2)->lists('id');
            $data['allDates'] = array_filter(
                $this->getDatesFromRange($data['startDate'], $data['endDate']),
                function ($date) {
                    $dayOfWeek = Carbon::parse($date)->dayOfWeek;
                    return !in_array($dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]); // Exclude weekends
                }
            );
            $data['attendanceReport'] = UserDetails::
            select(DB::raw('timediff(logout_time,login_time) as timediff'),
                'login_date', 'logout_date', 'id', 'login_time', 'logout_time', 'user_id')
                ->whereHas('User', function ($q) {
                    $q->where('company_id', Auth::user()->company_id);
                })
                ->whereNotIn('user_id', $inactiveUserIds)
                ->whereIn('login_date', $data['allDates'])  // Only working days
                //->where('logout_time', '!=', '0000-00-00 00:00:00')
                ->orderBy('id', 'ASC')
                ->get();
            //return collect($data['attendanceReport']);
            // Fetch break times
            $data['breakReport'] = DB::table('user_breaks')
                ->select('user_id', DB::raw('SUM(TIMESTAMPDIFF(SECOND, break_start, break_end)) as break_seconds'))
                ->whereIn(DB::raw('DATE(break_start)'), $data['allDates']) // Only working days
                ->groupBy('user_id')
                ->get();

            // Convert to key-value array (for Laravel 5.1)
            $breaksByUser = [];
            foreach ($data['breakReport'] as $break) {
                $breaksByUser[$break->user_id] = $break->break_seconds;
            }
            $data['breakReport'] = $breaksByUser;

            $workingDays = $data['allDates'];
            $data['approvedLeaves'] = User::with(['approvedLeave'=>function($query) use($workingDays){
                $query->whereIn('leave_date', $workingDays)
                    ->where('leave_category_id', '!=', 25)
                    ->orderBy('leave_date', 'asc')
                    ->select('user_id', 'leave_date', 'is_half_day', 'leave_category_id');
            }])->select('id')
                ->where('company_id', Auth::user()->company_id)
                ->where('user_label', 2)
                ->where('status', 1)
                ->get();
            $data['approvedAuthorizedLeaves'] = User::with(['approvedLeave'=>function($query) use($workingDays){
                $query->whereIn('leave_date', $workingDays)
                    ->where('leave_category_id', 25)
                    ->orderBy('leave_date', 'asc')
                    ->select('user_id', 'leave_date', 'is_half_day', 'leave_category_id');
            }])->select('id')
                ->where('company_id', Auth::user()->company_id)
                ->where('user_label', 2)
                ->where('status', 1)
                ->get();

            //return $data['approvedLeaves'];

            $weekEnd =  array_filter(
                $this->getDatesFromRange($data['startDate'], $data['endDate']),
                function ($date) {
                    $dayOfWeek = Carbon::parse($date)->dayOfWeek;
                    return in_array($dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]); // Exclude weekends
                }
            );
            $data['weekEndAttendanceReport'] = UserDetails::
            select(DB::raw('timediff(logout_time,login_time) as timediff'),
                'login_date', 'logout_date', 'id', 'login_time', 'logout_time', 'user_id')
                ->whereHas('User', function ($q) {
                    $q->where('company_id', Auth::user()->company_id);
                })
                ->whereNotIn('user_id', $inactiveUserIds)
                ->whereIn('login_date', collect($weekEnd)->values())  // Only weekEnd
                ->where('logout_time', '!=', '0000-00-00 00:00:00')
                ->orderBy('id', 'ASC')
                ->get();

            $data['holidays'] = HolidayInfo::whereBetween('holiday', [$data['startDate'], $data['endDate']])
                ->select('holiday')
                ->lists('holiday')
                ->toArray();

            //return $data['approvedLeaves'];
            //return $data['breakReport'];
            //return collect($data['attendanceReport'])->where('user_id', 102);
            if ($data['attendanceReport']->isEmpty()) {
                Session::flash('flashError', 'There is no report.Because None of Employee Has Not Work From ' . $data['startDate'] . ' to ' . $data['endDate']);
                return redirect('company/report-summery');
            }

            return view('Company.summeryReport', $data);
        } else {
            return view('Company.summeryReportRequest');
        }
    }

    /**
     * @param $start
     * @param $end
     * @return array
     */
    public function getDatesFromRange($start, $end)
    {
        $dates = array($start);
        while (end($dates) < $end) {
            $dates[] = date('Y-m-d', strtotime(end($dates) . ' +1 day'));
        }
        return $dates;
    }

    /**
     * @return Redirect|\Illuminate\View\View
     */
    public function anyFullCalender()
    {
        if (Input::all()) {
            $data['startDate'] = Input::get('from');
            $data['endDate'] = Input::get('to');
            $data['id'] = Input::get('id');
            $data['userInfo'] = \App\User::where('company_id', Auth::user()->company_id)
                ->where('id', $data['id'])->first();
            //return $data['userInfo'];
            if (!$data['userInfo']) {
                Session::flash('flashError', 'This User Is Not Your Company');
                return redirect('company/full-calender');
            }



            $allDates = $this->getDatesFromRange($data['startDate'], $data['endDate']);
            $weekends = $this->getWeekendDates($data['startDate'], $data['endDate']);

            $holidays = HolidayInfo::whereBetween('holiday', [$data['startDate'], $data['endDate']])
                ->select('holiday')
                ->lists('holiday')
                ->toArray();

            $leaves = Leave::whereBetween('leave_date', [$data['startDate'], $data['endDate']])
                ->where('user_id', $data['id'])
                ->where('leave_status', 1)
                ->select('leave_date','leave_category_id','is_half_day')
                ->get();
            //return $leaves;

            $attendance = UserDetails::select(DB::raw('timediff(logout_time,login_time) as timediff'),
                'login_date', 'logout_date', 'id', 'login_time', 'logout_time', 'user_id', 'status')
                ->where('user_id', $data['id'])
                ->whereBetween('login_date', [$data['startDate'], $data['endDate']])
                ->orderBy('id', 'ASC')
                ->get()
                ->toArray();
            //return $attendance;

            // Prepare report array
            $attendanceReport = [];

            // Add Present records
            foreach ($attendance as $record) {
                $attendanceReport[] = [
                    'id' => $record['id'],
                    'login_date' => $record['login_date'],
                    'logout_date' => $record['logout_date'],
                    'login_time' => $record['login_time'],
                    'logout_time' => $record['logout_time'],
                    'status' => 'Present',
                ];
            }

            //return $attendanceReport;
            // Add Leave records
            foreach ($leaves as $leave) {
                $leaveDate = $leave->leave_date;
                $_status = 'On Leave';
                if($leave->leave_category_id == 25){
                    if($leave->is_half_day){
                        $_status = 'Authorized [Half day]';
                    }else{
                        $_status = 'Authorized [Full day]';
                    }
                }
                $attendanceReport[] = [
                    'id' => uniqid(), // temp ID
                    'login_date' => $leaveDate,
                    'logout_date' => $leaveDate,
                    'login_time' => $leaveDate . ' 00:00:00',
                    'logout_time' => $leaveDate . ' 23:59:59',
                    'status' => $_status,
                ];
            }

            // Add Absent records
            foreach ($allDates as $date) {
                if (in_array($date, $weekends)) {
                    continue; // Skip weekends and holidays
                }
                if(in_array($date, $holidays)){
                    $attendanceReport[] = [
                        'id' => uniqid(), // temp ID
                        'login_date' => $date,
                        'logout_date' => $date,
                        'login_time' => $date . ' 00:00:00',
                        'logout_time' => $date . ' 23:59:59',
                        'status' => 'Holiday',
                    ];
                }

                $isPresent = collect($attendanceReport)->where('login_date', $date)->count();
                if ($isPresent == 0) {
                    $attendanceReport[] = [
                        'id' => uniqid(), // temp ID
                        'login_date' => $date,
                        'logout_date' => $date,
                        'login_time' => $date . ' 00:00:00',
                        'logout_time' => $date . ' 23:59:59',
                        'status' => 'Absent',
                    ];
                }
            }

            // Sort by date
            usort($attendanceReport, function ($a, $b) {
                return strtotime($a['login_date']) - strtotime($b['login_date']);
            });

            $data['attendanceReport'] = $attendanceReport;

            if (!$data['attendanceReport']) {
                Session::flash('flashError', $data['userInfo']->username . ' Has not Any Work From ' . $data['startDate'] . ' to ' . $data['endDate']);
                return redirect('company/full-calender');
            }


            return view('Company.fullCalender', $data);
        } else {
            $user = new \App\User();
            $data['allUser'] = $user->allUser();
            return view('Company.fullCalenderRequest', $data);
        }
    }

    /**
     * @return Redirect|\Illuminate\View\View
     */
    public function anyTableReport()
    {
        if (Input::all()) {
            $data['startDate'] = Input::get('from');
            $data['endDate'] = Input::get('to');
            $data['id'] = Input::get('id');
            $data['userInfo'] = \App\User::where('company_id', Auth::user()->company_id)
                ->where('id', $data['id'])->first();
            if (!$data['userInfo']) {
                Session::flash('flashError', 'This User Is Not Your Company');
                return redirect('company/table-report');
            }
            $data['allDate'] = $this->getDatesFromRange($data['startDate'], $data['endDate']);
            $data['weekends'] = $this->getWeekendDates($data['startDate'], $data['endDate']);
            $data['allHoliday'] = HolidayInfo::where('holiday', '>=', $data['startDate'])
                ->where('holiday', '<=', $data['endDate'])
                ->get()
                ->toArray();
            $data['allLeave'] = Leave::where('leave_date', '>=', $data['startDate'])
                ->where('leave_date', '<=', $data['endDate'])
                ->where('user_id', $data['id'])
                ->where('leave_status', 1)
                ->get()
                ->toArray();

            //return $data['allLeave'];

            $data['attendanceReport'] = UserDetails::select(
                'login_date',
                DB::raw('MIN(login_time) as first_login'),
                DB::raw('MAX(logout_time) as last_logout'),
                DB::raw('SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(logout_time, login_time)))) as total_work_time'),
                DB::raw('(SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(break_end, break_start))))
                  FROM user_breaks 
                  WHERE user_breaks.user_id = user_details.user_id 
                  AND DATE(user_breaks.break_start) = user_details.login_date) as total_break_time')
            )
                ->where('user_id', $data['id'])
                ->whereBetween('login_date', [$data['startDate'], $data['endDate']])
                //->where('logout_time', '!=', '0000-00-00 00:00:00')
                ->groupBy('login_date')
                ->orderBy('login_date', 'ASC')
                ->get()
                ->map(function($item){
                    $item->work_time_second = $this->durationToSeconds($item->total_work_time);
                    $item->break_time_second = 0;
                    if($item->total_break_time){
                        $item->break_time_second = $this->durationToSeconds($item->total_break_time);
                    }
                    return $item;
                })
                ->values()
                ->toArray();
            //return $data['attendanceReport'];
            $totalWorkSeconds = collect($data['attendanceReport'])->sum('work_time_second');
            $totalBreakSeconds = collect($data['attendanceReport'])->sum('break_time_second');
            $totalActiveSeconds = $totalWorkSeconds - $totalBreakSeconds;
            //return $totalWorkSeconds;
            $data['summary'] = [
                'total_work_time' => $this->formatDuration($totalWorkSeconds),
                'total_break_time' => $this->formatDuration($totalBreakSeconds),
                'total_active_time' => $this->formatDuration($totalActiveSeconds),
            ];
            //return $data['summary'];

            if (!$data['attendanceReport']) {
                Session::flash('flashError', $data['userInfo']->username . ' Has not Any Work From ' . $data['startDate'] . ' to ' . $data['endDate']);
                return redirect('company/table-report');
            }

            return view('Company.report', $data);
        } else {
            $user = new \App\User();
            $data['allUser'] = $user->allUser();
            return view('Company.tableReportRequest', $data);
        }
    }

    function durationToSeconds($time) {
        if (!$time) return 0;
        list($h, $m, $s) = explode(':', $time);
        return ($h * 3600) + ($m * 60) + $s;
    }

    function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    /**
     * @return Redirect
     */
    public function getLogout()
    {
        Auth::logout();
        return redirect('/');
    }

    /**
     * @param $id
     */
    public function getDeleteUser($id)
    {
        $user = \App\User::find($id);
        UserDetails::where('user_id', $id)->delete();
        Leave::where('user_id', $id)->delete();
        $user->delete();
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getNoticeBoardCreate()
    {
        return view('Company.noticeBoardCreate');
    }

    /**
     * @param $id
     * @return \Illuminate\View\View
     */
    public function getNoticeBoardEdit($id)
    {
        $data['notice'] = NoticeBoard::find($id);
        return view('Company.noticeBoardView', $data);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getNoticeBoard()
    {
        $data['allNotice'] = NoticeBoard::orderBy('id', 'DESC')->paginate(10);
        return view('Company.noticeBoard', $data);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function putNoticeBoard($id)
    {
        $rules = array(
            'subject' => "required",
            'message' => "required",
        );
        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()):
            $errorMessage = $validator->messages()->first();
            $response['type'] = 'error';
            $response['info'] = $errorMessage;
            return Response::json($response);
        else:
            $notice = NoticeBoard::find($id);
            $notice->subject = trim(Input::get('subject'));
            $notice->message = Input::get('message');
            $notice->save();
            $response['type'] = 'success';
            Session::flash('success', 'Notice Updated Successfully');
            return Response::json($response);
        endif;
    }

    /**
     * @return mixed
     */
    public function postNoticeBoard()
    {
        $rules = array(
            'subject' => "required",
            'message' => "required",
        );
        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()):
            $errorMessage = $validator->messages()->first();
            $response['type'] = 'error';
            $response['info'] = $errorMessage;
            return Response::json($response);
        else:
            $notice = new NoticeBoard();
            $notice->subject = trim(Input::get('subject'));
            $notice->message = Input::get('message');
            $notice->save();
            $response['type'] = 'success';
            Session::flash('success', 'New Notice Created Successfully');
            return Response::json($response);
        endif;
    }

    /**
     * @param $id
     * @return string
     */
    public function deleteNotice($id)
    {
        $notice = NoticeBoard::find($id);
        $notice->delete();
        return 'true';
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getDesignation()
    {
        $data['designations'] = Designation::paginate(10);
        return view('Company.designation', $data);
    }

    /**
     * @return mixed
     */
    public function postDesignation()
    {
        $exists = Designation::where('name', Input::get('name'))->get();
        if ($exists && $exists->count() > 0) {
            $response['type'] = 'error';
            $response['info'] = 'Already Exists This Designation';
            return Response::json($response);
        }
        $rules = array(
            'name' => "required|alpha_dash_spaces"
        );
        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()):
            $errorMessage = $validator->messages()->first();
            $response['type'] = 'error';
            $response['info'] = $errorMessage;
            return Response::json($response);
        else:
            $designation = new Designation();
            $designation->name = trim(Input::get('name'));
            $designation->save();
            $response['type'] = 'success';
            $response['info'] = 'Designation Created Successfully';
            $response['id'] = $designation->id;
            return Response::json($response);
        endif;
    }

    public function getDesignationEdit($id)
    {
        $data['designation'] = Designation::orderBy('id', 'DESC')->find($id);
        return view('Company.designationEdit', $data);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function putDesignation($id)
    {
        $exists = Designation::where('id', '!=', $id)
            ->where('name', Input::get('name'))->get();
        if ($exists && $exists->count() > 0) {
            $response['type'] = 'error';
            $response['info'] = 'Already Exists This Designation';
            return Response::json($response);
        }
        $rules = array(
            'name' => "required|alpha_dash_spaces"
        );
        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()):
            $errorMessage = $validator->messages()->first();
            $response['type'] = 'error';
            $response['info'] = $errorMessage;
            return Response::json($response);
        else:
            $designation = Designation::find($id);
            $designation->name = trim(Input::get('name'));
            $designation->save();
            $response['type'] = 'success';
            Session::flash('success', 'Designation Updated Successfully');
            $response['id'] = $designation->id;
            return Response::json($response);
        endif;
    }

    /**
     * @param $id
     * @return string
     */
    public function postUpdateDesignation($id)
    {
        $designation = \App\User::find($id);
        $designation->designation_id = Input::get('designation_id');
        $designation->save();
        return 'true';

    }

    /**
     * @param $id
     * @return string
     */
    public function deleteDesignation($id)
    {
        $notice = Designation::find($id);
        $notice->delete();
        \App\User::where('designation_id', $id)->update(array('designation_id' => 0));
        return 'true';
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getChat()
    {
        $sortedSenderUser = DB::table('users')
            ->join('messages', 'users.id', '=', 'messages.sender_id', 'left outer')
            ->select(DB::raw('sum(messages.read) as total_read'),
                DB::raw('count(messages.id) as total_messages'),
                'users.id', 'users.username', 'users.user_first_name', 'users.user_last_name', DB::raw('max(messages.created_at) as created_at'))
            ->where('users.company_id', Auth::user()->company_id)
            ->where('messages.receiver_id', Auth::user()->id)
            ->where('users.id', '!=', Auth::user()->id)
            ->orderBy('created_at', 'DESC')
            ->groupBy('users.id')
            ->get();

        $sortedReceiverUser = DB::table('users')
            ->join('messages', 'users.id', '=', 'messages.receiver_id', 'left outer')
            ->select('users.id', 'users.username', 'users.user_first_name', 'users.user_last_name', DB::raw('max(messages.created_at) as created_at'))
            ->where('users.company_id', Auth::user()->company_id)
            ->where('messages.sender_id', Auth::user()->id)
            ->where('users.id', '!=', Auth::user()->id)
            ->orderBy('created_at', 'DESC')
            ->groupBy('users.id')
            ->get();
        $totalSorted = (array)array_merge((array)$sortedSenderUser, (array)$sortedReceiverUser);
        usort($totalSorted, function ($a, $b) {
            return $a->created_at < $b->created_at;
        });
        $uniqueSorted = [];
        foreach ($totalSorted as $row) {
            if (!array_key_exists($row->id, $uniqueSorted))
                $uniqueSorted[$row->id] = $row;
        }
        $existingId = array();
        foreach ($uniqueSorted as $sender) {
            $existingId[] = $sender->id;
        }
        $generalUser = DB::table('users')->select('id', 'username', 'user_first_name', 'user_last_name')
            ->whereNotIn('id', $existingId)
            ->where('id', '!=', Auth::user()->id)
            ->where('company_id', Auth::user()->company_id)
            ->get();
        $data['sorted_user'] = (object)array_merge((array)$uniqueSorted, (array)$generalUser);
        if ($data['sorted_user'] && !empty($data['sorted_user']) && count((array)$data['sorted_user']) > 0) {
            $data['active_message'] = Messages::with('User')->where('sender_id', reset($data['sorted_user'])->id)
                ->where('receiver_id', Auth::user()->id)
                ->orWhere(function ($query) use ($data) {
                    $query->where('sender_id', Auth::user()->id)
                        ->where('receiver_id', reset($data['sorted_user'])->id);
                })
                ->where('company_id', Auth::user()->company_id)
                ->orderBy('id', 'desc')
                ->take(4)->get();
        }
        return view('Company.chat', $data);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getCheckMessage($id)
    {
        Messages::where('sender_id', $id)
            ->where('receiver_id', Auth::user()->id)
            ->update(['read' => 1]);
        return Messages::with('User')
            ->where(function ($query) use ($id) {
                $query->where('sender_id', $id)
                    ->where('receiver_id', Auth::user()->id);
            })
            ->orWhere(function ($query) use ($id) {
                $query->where('sender_id', Auth::user()->id)
                    ->where('receiver_id', $id);
            })
            ->where('company_id', Auth::user()->company_id)
            ->orderBy('id', 'desc')->take(4)
            ->get();
    }

    /**
     * @return mixed
     */
    public function postMessageMore()
    {
        $senderId = Input::get('userId');
        return Messages::with('User')
            ->where(function ($query) use ($senderId) {
                $query->where('sender_id', $senderId)
                    ->where('receiver_id', Auth::user()->id);
            })
            ->where('id', '<', Input::get('minRow'))
            ->orWhere(function ($query) use ($senderId) {
                $query->where('sender_id', Auth::user()->id)
                    ->where('receiver_id', $senderId);
            })
            ->where('id', '<', Input::get('minRow'))
            ->where('company_id', Auth::user()->company_id)
            ->orderBy('id', 'desc')->take(4)
            ->get();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getMessageMarkRead($id)
    {
        return Messages::where('sender_id', $id)
            ->where('receiver_id', Auth::user()->id)
            ->update(['read' => 1]);
    }

    /**
     * @return Messages
     */
    public function postMessageSave()
    {
        $message = new Messages();
        $message->message = Input::get('message');
        $message->sender_id = Input::get('sender_id');
        $message->receiver_id = Input::get('receiver_id');
        $message->save();
        return $message;
    }


    public function getForce($id)
    {
        $data['currentPunchStatus'] = UserDetails::currentPunchStatus($id);
        $data['id'] = $id;
        return view('Company.force.index', $data);
    }


    public function postForce($id)
    {
        if (!UserDetails::currentPunchStatus($id)) {
            $userInfo = User::select('username')->where('id', $id)->first();
            $userDetails = new UserDetails();
            $userDetails->user_id = $id;
            $userDetails->user_name = $userInfo->username;
            $userDetails->login_time = Request::get('time');
            $userDetails->login_date = date('Y-m-d', strtotime(Request::get('time')));
            $userDetails->save();

            Session::flash('success', 'Force Punch In Done');
            return \Redirect::back();
        }else{
            $userInfo = User::select('username')->where('id', $id)->first();
            $userDetails = UserDetails::maxRow($id);
            $userDetails->user_id = $id;
            $userDetails->user_name = $userInfo->username;
            $userDetails->logout_time = Request::get('time');
            $userDetails->logout_date = date('Y-m-d', strtotime(Request::get('time')));
            $userDetails->save();
            Session::flash('error', 'Force Punch Out Done');
            return \Redirect::back();
        }
    }

    public function activityWiseUserList(){
        $onLeaveUsers = Leave::with([
            'User'=>function($query){
                $query->select('id', 'username', 'user_first_name', 'user_last_name');
            }
        ])->where('leave_date', date('Y-m-d'))
            ->where('leave_status', 1)
            ->select('user_id')
            ->get();
        $allUsers = \App\User::where('user_label', 2)
            ->where('status', 1)
            ->whereNotIn('id', $onLeaveUsers->pluck('user_id')->toArray())
            ->orderBy('username', 'asc')
            ->get();
        //return $allUsers;
        $punchedInUsers = [];
        $notPunchedInUsers = [];
        $onBreakUsers = [];
        $loggedOutUsers = [];
        $usersIdleTime = [];
        foreach ($allUsers as $user) {

            $attendUser = UserDetails::where('user_id', $user->id)
                ->where('login_date', date('Y-m-d'))
                ->latest()
                ->first();

            if($attendUser){
                $idleTimeLogQuery = UserIdleTimeLog::where('user_id', $user->id)
                    ->where('log_date', Carbon::parse($attendUser->login_time)->toDateString())
                    ->where('time_start','>=', Carbon::parse($attendUser->login_time)->toTimeString());
                if($attendUser->logout_date != '0000-00-00'){
                    $idleTimeLogQuery->where('log_date', Carbon::parse($attendUser->logout_time)->toDateString())
                    ->where('time_end', '<=', Carbon::parse($attendUser->logout_time)->toTimeString());
                }
                $idleTimeLog = $idleTimeLogQuery->get();
                $totalIdleSeconds = $idleTimeLog->sum('time_count_in_second');

                // Optional: Convert to HH:MM:SS for easy viewing
                $hours = floor($totalIdleSeconds / 3600);
                $minutes = floor(($totalIdleSeconds % 3600) / 60);
                $seconds = $totalIdleSeconds % 60;
                $totalIdleFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                $usersIdleTime[] = [
                    'user_name'=>$attendUser->user_name,
                    'totalIdleTime' => $totalIdleFormatted
                ];
            }

            $punchData = UserDetails::where('user_id', $user->id)
                ->where('login_date', date('Y-m-d'))
                ->where('logout_date', '0000-00-00')
                ->latest()
                ->first();

            // Fetch total break time for today
            $totalBreakTime = DB::table('user_breaks')
                ->where('user_id', $user->id)
                ->where('break_start', '>=', date('Y-m-d') . ' 00:00:00')
                ->where('break_start', '<=', date('Y-m-d') . ' 23:59:59')
                ->whereNotNull('break_end')
                ->select(DB::raw('SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(break_end, break_start)))) AS total_break_time'))
                ->first();
            $totalBreakDuration = $totalBreakTime->total_break_time ?? '00:00';
            $userInfo = $user->username;
            if ($punchData) {
                // Check if the user is on break
                $activeBreak = UserBreak::where('user_id', $user->id)
                    ->whereNull('break_end')
                    ->latest()
                    ->first();
                // Always add to punchedInUsers if punched in
                $workingHours = Carbon::parse($punchData->login_time)
                    ->diff(Carbon::now())
                    ->format('%H:%I');
                $punchedInUsers[] = [
                    'id' => $user->id,
                    'name' => $userInfo,
                    'working_hours' => $workingHours,
                    'total_break_duration' => Carbon::parse($totalBreakDuration)->format('H:i'),
                    'logged_in_at' => Carbon::parse($punchData->login_time)->format("H:i A")
                ];

                // Check if the user is on break
                $activeBreak = UserBreak::where('user_id', $user->id)
                    ->whereNull('break_end')
                    ->latest()
                    ->first();
                if ($activeBreak) {
                    // User is on break, so add to "On Break" list
                    $breakDuration = Carbon::parse($activeBreak->break_start)
                        ->diff(Carbon::now())
                        ->format('%H:%I');
                    $onBreakUsers[] = [
                        'id' => $user->id,
                        'name' => $userInfo,
                        'break_duration' => $breakDuration,
                        'total_break_duration' => Carbon::parse($totalBreakDuration)->format('H:i'),
                        'logged_in_at' => Carbon::parse($punchData->login_time)->format("H:i A"),
                        'break_started_at' => Carbon::parse($activeBreak->break_start)->format("H:i A")
                    ];
                }
            } else {

                $loggedOutRecord = UserDetails::where('user_id', $user->id)
                    ->where('login_date', date('Y-m-d'))
                    ->where('logout_date', date('Y-m-d'))
                    ->latest()
                    ->first();
                if ($loggedOutRecord) {
                    $loggedOutUsers[] = [
                        'id' => $user->id,
                        'name' => $userInfo,
                        'logged_out_at' => Carbon::parse($loggedOutRecord->logout_time)->format("H:i A"),
                        'total_break_duration' => Carbon::parse($totalBreakDuration)->format('H:i'),
                        'logged_in_at' => Carbon::parse($loggedOutRecord->login_time)->format("H:i A"),
                        'duration' => Carbon::parse($loggedOutRecord->login_time)
                            ->diff(Carbon::parse($loggedOutRecord->logout_time))
                            ->format('%H:%I')
                    ];
                } else {
                    // Not punched in at all
                    $notPunchedInUsers[] = [
                        'id' => $user->id,
                        'name' => $userInfo
                    ];
                }
            }
        }
        usort($punchedInUsers, function ($a, $b) {
            return strtotime($a['logged_in_at']) - strtotime($b['logged_in_at']);
        });
        usort($loggedOutUsers, function ($a, $b) {
            return strtotime($a['logged_out_at']) - strtotime($b['logged_out_at']);
        });

        return [
            'punchedInUser' => $punchedInUsers,  // Now excludes users who are on break
            'notPunchedInUser' => $notPunchedInUsers,
            'onBreakUser' => $onBreakUsers,
            'onLeaveUser' => $onLeaveUsers,
            'punchedOutUser' => $loggedOutUsers,
            'usersIdleTimeLog' => $usersIdleTime
        ];
    }

    public function anyAttendanceLog()
    {
        $data['startDate'] = Input::get('s_date');
        $data['endDate'] = Input::get('e_date');
        $data['id'] = Input::get('id');
        $data['weekends'] = $this->getWeekendDates($data['startDate'], $data['endDate']);
        //return $data['weekends'];
        $data['userInfo'] = \App\User::where('company_id', Auth::user()->company_id)
            ->where('id', $data['id'])->first();
        if (!$data['userInfo'])
            return 'The User is Not In Your Company';
        $data['attendanceReport'] = UserDetails::
        select(DB::raw('timediff(logout_time,login_time) as timediff'),
            'login_date', 'logout_date', 'id', 'login_time', 'logout_time', 'user_id', 'status')
            ->where('user_id', $data['id'])
            ->where('login_date', '=', $data['startDate'])
            ->orderBy('id', 'ASC')
            ->get()
            ->toArray();

        // ✅ Calculate total work time duration
        $totalSeconds = 0;
        foreach ($data['attendanceReport'] as $log) {
            if ($log['timediff']) {
                list($h, $m, $s) = explode(':', $log['timediff']);
                $totalSeconds += ($h * 3600) + ($m * 60) + $s;
            }
        }
        // Format to HH:MM:SS
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        $data['totalWorkingHour'] = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        //return $data['totalWorkingHour'];
        //return $data['attendanceReport'];
        $data['allDate'] = $this->getDatesFromRange($data['startDate'], $data['endDate']);
        $data['allHoliday'] = HolidayInfo::where('holiday', '>=', $data['startDate'])
            ->where('holiday', '<=', $data['endDate'])
            ->get()
            ->toArray();
        $data['allLeave'] = Leave::where('leave_date', '>=', $data['startDate'])
            ->where('leave_date', '<=', $data['endDate'])
            ->where('user_id', $data['id'])
            ->where('leave_status', 1)
            ->get()
            ->toArray();
        return view('Company.attendanceLog', $data);
    }

    public function anyBreakTimeLog(){
        $data['startDate'] = Input::get('s_date');
        $data['endDate'] = Input::get('e_date');
        $data['id'] = Input::get('id');
        $data['userInfo'] = \App\User::find($data['id']);
        $data['breakLogs'] = DB::table('user_breaks')
            ->select(
                'id',
                'user_id',
                'break_start',
                'break_end',
                DB::raw('TIMEDIFF(break_end, break_start) AS break_duration')
            )
            ->where('user_id', $data['id'])
            ->where('break_start', '>=', $data['startDate'].' 00:00:00')
            ->where('break_start', '<=', $data['endDate'].' 23:59:59')
            ->orderBy('break_start', 'ASC')
            ->get();

        // ✅ Calculate total break duration
        $totalSeconds = 0;
        foreach ($data['breakLogs'] as $log) {
            if ($log->break_duration) {
                list($h, $m, $s) = explode(':', $log->break_duration);
                $totalSeconds += ($h * 3600) + ($m * 60) + $s;
            }
        }
        // Format to HH:MM:SS
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        $data['totalBreakDuration'] = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        //return $data['totalBreakDuration'];
        //return $data['breakLogs'];
        //return auth()->user()->user_label;

        $data['allDate'] = $this->getDatesFromRange($data['startDate'], $data['endDate']);

        return view('Company.breakTimeLog', $data);
    }

    function getWeekendDates($startDate, $endDate)
    {
        $dateRange = $this->getDatesFromRange($startDate, $endDate); // Assuming this returns an array of dates

        $weekends = array_values(array_filter($dateRange, function ($date) {
            $dayOfWeek = Carbon::parse($date)->dayOfWeek;
            return in_array($dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]); // Get only Saturdays & Sundays
        }));

        return $weekends;
    }

    function getUploadAttendance(){
        return view('Company.uploadAttendanceExcel');
    }

    public function postUploadAttendanceLog(Request $request)
    {
        // Define validation rules
        $rules = [
            'attendance_log' => 'required|mimes:csv,txt|max:2048'
        ];

        // Validate request
        $validator = Validator::make(Input::all(), $rules); // ✅ Using Input::all() for Laravel 5

        if ($validator->fails()) {
            Session::flash('flashError', $validator->messages()->first());
            return redirect('company/upload-attendance');
        }

        // Get the uploaded file
        $file = Input::file('attendance_log'); // ✅ Using Input::file() for Laravel 5

        if (!$file) {
            return back()->with('flashError', 'No file was uploaded.');
        }

        $handle = fopen($file->getRealPath(), 'r'); // ✅ Using getRealPath() for file handling

        if (!$handle) {
            return back()->with('flashError', 'Could not open the file.');
        }

        $header = fgetcsv($handle); // Read CSV headers

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                //return $row;
                $userName = trim($row[1]);  // Employee name
                $inTime = trim($row[2]);    // Check-in time
                $outTime = trim($row[3]);   // Check-out time
                $date = date('Y-m-d', strtotime(trim($row[4])));      // Date

                $formattedInTime = date('Y-m-d H:i:s', strtotime("$date $inTime"));
                $formattedOutTime = date('Y-m-d H:i:s', strtotime("$date $outTime"));

                $user = User::where('username', $userName)->first();

                if ($user) {

                    $existingRecords = UserDetails::where('user_id', $user->id)
                        ->where('user_name', $user->username)
                        ->where('login_date', $date)
                        ->orderBy('id') // Ensure deterministic order
                        ->get();
                    if (!$existingRecords->isEmpty()) {
                        // Update the first matched record
                        $primary = $existingRecords->first();
                        $primary->login_time = $formattedInTime;
                        $primary->logout_time = $formattedOutTime;
                        $primary->login_date = $date;
                        $primary->logout_date = $date;
                        $primary->status = 'Present';
                        $primary->save();

                        // Delete all other duplicates
                        try {
                            $duplicateIds = $existingRecords->pluck('id')->slice(1)->values()->toArray();
                            foreach($duplicateIds as $item){
                                UserDetails::find($item)->delete();
                            }
                        } catch (\Throwable $e) {
                            \Log::error('Delete error: ' . $e->getMessage());
                        }
                    }else{
                        // Insert new record
                        $newRecord = new UserDetails();
                        $newRecord->user_id = $user->id;
                        $newRecord->user_name = $user->username;
                        $newRecord->login_time = $formattedInTime;
                        $newRecord->logout_time = $formattedOutTime;
                        $newRecord->login_date = $date;
                        $newRecord->logout_date = $date;
                        $newRecord->status = 'Present';
                        $newRecord->save();
                    }
                }
            }
            DB::commit();
            fclose($handle);
            Session::flash('success', 'Attendance imported successfully!');
            return back();

        }catch (\Exception $e){
            DB::rollBack();
            fclose($handle);
            \Log::error('Attendance Upload Failed: ' . $e->getMessage());
            Session::flash('error', 'Failed to import attendance. Please check the file or contact support.');
            return back();
        }

    }

    public function getDailyAttendanceReport(){
        return view('Company.dailyAttendanceReportRequest');
    }
    public function postDailyAttendanceReport(){
        $date = Input::get('date');
        $inactiveUserIds = User::where('status', 0)->where('user_label', 2)->lists('id');
        //return $allActiveUserIds;
        $breakReport = DB::table('user_breaks')
            ->select('user_id', DB::raw('SUM(TIMESTAMPDIFF(SECOND, break_start, break_end)) as break_seconds'))
            ->where('break_start', '>=', $date . ' 00:00:00')
            ->where('break_end', '<=', $date . ' 23:59:59')
            ->groupBy('user_id')
            ->get();
        //return $breakReport;
        // Convert to key-value array (for Laravel 5.1)
        $breaksByUser = [];
        foreach ($breakReport as $break) {
            $breaksByUser[$break->user_id] = $break->break_seconds;
        }
        //return $breaksByUser;


        $attendanceReport = UserDetails::
        select(DB::raw('timediff(logout_time,login_time) as timediff'),
            'user_name', 'login_date', 'logout_date', 'id', 'login_time', 'logout_time', 'user_id')
            ->whereHas('User', function ($q) {
                $q->where('company_id', Auth::user()->company_id);
            })
            ->whereNotIn('user_id', $inactiveUserIds)
            ->where('login_date', '=', $date)
            ->orderBy('id', 'ASC')
            ->get();
        //return $attendanceReport;
        $reports = [];
        //return $attendanceReport;
        foreach ($attendanceReport as $report) {
            $userId = $report->user_id;

            if (!isset($reports[$userId])) {
                $reports[$userId] = [
                    'id' => $report->id,
                    'user_id' => $userId,
                    'username' => $report->User->username,
                    'totalSeconds' => 0, // Reset for each user
                    'totalBreakSeconds' => 0,
                ];
            }

            // Extract time components
            $timeParts = explode(":", $report->timediff);
            $hours = intval($timeParts[0]);
            $minutes = intval($timeParts[1]);
            $seconds = intval($timeParts[2]);

            // Convert everything to seconds and accumulate only for the current user
            $reports[$userId]['totalSeconds'] += ($hours * 3600) + ($minutes * 60) + $seconds;

            // Add break time if exists
            if (isset($breaksByUser[$userId])) {
                $reports[$userId]['totalBreakSeconds'] = (int) $breaksByUser[$userId];
            }
        }
        foreach ($reports as &$report) {

            $report['first_login'] = $attendanceReport->where('user_id', $report['user_id'])->first()->login_time;
            $report['last_logout'] = $attendanceReport->where('user_id', $report['user_id'])->last()->logout_time;
            // Convert total working time
            $workHours = floor($report['totalSeconds'] / 3600);
            $workMinutes = floor(($report['totalSeconds'] % 3600) / 60);
            $report['workingTime'] = sprintf("%d:%02d", $workHours, $workMinutes);

            // Convert break time
            $breakHours = floor($report['totalBreakSeconds'] / 3600);
            $breakMinutes = floor(($report['totalBreakSeconds'] % 3600) / 60);
            $report['breakTime'] = sprintf("%d:%02d", $breakHours, $breakMinutes);

            // Calculate Active Time (Total Time - Break Time)
            $activeSeconds = max(0, $report['totalSeconds'] - $report['totalBreakSeconds']);
            $activeHours = floor($activeSeconds / 3600);
            $activeMinutes = floor(($activeSeconds % 3600) / 60);

            $report['activeSeconds'] = $activeSeconds;
            $report['activeTime'] = sprintf("%d:%02d", $activeHours, $activeMinutes);

        }
        unset($report);
        $reports = collect($reports)->values()->toArray();
        usort($reports, function ($a, $b) {
            return $b['activeSeconds'] <=> $a['activeSeconds'];
        });
        $presentUserIds =  collect($reports)->lists('user_id')->toArray();
        //return $presentUserIds;
        $absentUserLists  = User::select('id', 'username')
            ->where('user_label', 2)
            ->where('status', 1)
            ->whereNotIn('id', $presentUserIds)
            ->orderBy('username', 'asc')
            ->get();
        //return $absentUserLists;
        foreach ($absentUserLists as $absentUser) {
            $reports[] = [
                'id' => null,
                'user_id' => $absentUser->id,
                'username' => $absentUser->username,
                'first_login'=>null,
                'last_logout'=> null,
                'totalSeconds' => 0,
                'totalBreakSeconds' => 0,
                'workingTime' => '0:00',
                'breakTime' => '0:00',
                'activeSeconds' => 0,
                'activeTime' => '0:00',
            ];
        }
        $data['reports'] = $reports;
        $data['date'] = $date;
        //return $data['reports'];
        return view('Company.dailyAttendanceReport', $data);
    }

    public function getBreakLogTimeEditRequest($logId){
        $data['log'] =  UserBreak::find($logId);
        return view('Company.breakTimeLogEditRequest', $data);
    }
    public function postBreakLogTimeEditRequest($logId){
        $log = UserBreak::find($logId);
        $current_from_date = Carbon::parse($log->break_start)->toDateString();
        $current_to_date = Carbon::parse($log->break_end)->toDateString();
        if (!$log) {
            return redirect()->back()->with('flashError', 'Break log not found.');
        }

        //return $log;

        // Validate request (optional but recommended)
        $validator = Validator::make(Input::all(), [
            'from' => 'required|date',
            'to' => 'required|date|after:from',
        ]);

        if ($validator->fails()) {
            Session::flash('error', 'Failed to update log');
            return back()->withErrors($validator)->withInput();
        }

        // Update break times
        $log->break_start = Input::get('from');
        $log->break_end = Input::get('to');
        $log->save();

        Session::flash('success', 'Break log updated successfully!');
        return redirect('company/break-time-log?s_date='.$current_from_date.'&e_date='.$current_to_date.'&id='.$log->user_id);
    }
    public function postBreakLogDelete($id)
    {
        $log = UserBreak::find($id);

        if (!$log) {
            Session::flash('error', 'Failed to delete log');
            return redirect()->back();
        }

        $log->delete();
        Session::flash('success', 'Break log deleted successfully!');
        return redirect()->back();
    }
    public function getAttendanceLogTimeEditRequest($logId){
        $data['log'] =  UserDetails::find($logId);
        //return $data['log'];
        return view('Company.attendanceTimeLogEditRequest', $data);
    }
    public function postAttendanceLogTimeEditRequest($logId){
        $log = UserDetails::find($logId);
        //return $log;
        if (!$log) {
            return redirect()->back()->with('flashError', 'Break log not found.');
        }
        $current_from_date = $log->login_date;
        $current_to_date = $log->login_date;
        //return $log;

        // Validate request (optional but recommended)
        $validator = Validator::make(Input::all(), [
            'from' => 'required|date',
            'to' => 'required|date|after:from',
        ]);

        if ($validator->fails()) {
            Session::flash('error', 'Failed to update log');
            return back()->withErrors($validator)->withInput();
        }

        // Update break times
        $log->login_time = Input::get('from');
        $log->login_date = Carbon::parse(Input::get('from'))->toDateString();
        $log->logout_time = Input::get('to');
        $log->logout_date = Carbon::parse(Input::get('to'))->toDateString();
        $log->save();

        Session::flash('success', 'Attendance log updated successfully!');
        return redirect('company/attendance-log?s_date='.$current_from_date.'&e_date='.$current_to_date.'&id='.$log->user_id);
    }
    public function postAttendanceLogDelete($id)
    {
        $log = UserDetails::find($id);

        if (!$log) {
            Session::flash('error', 'Failed to delete log');
            return redirect()->back();
        }

        $log->delete();
        Session::flash('success', 'Attendance log deleted successfully!');
        return redirect()->back();
    }

    public function getScreenshotListRequest(){
        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', 1)
            ->where('user_label', 2)
            ->orderBy('username')
            ->select('username', 'id')
            ->get();

        return view('Company.userScreenshotsRequest', compact('users'));
    }

    public function postScreenshotsByUserAndDate()
    {
        $userId = Input::get('user_id');
        $date   = Input::get('date');        // expected YYYY-MM-DD

        if (!$userId || !$date) {
            return response()->json(
                ['error' => 'user_id and date are required'],
                422
            );
        }

        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', 1)
            ->where('user_label', 2)
            ->orderBy('username')
            ->select('username', 'id')
            ->get();

        /* ---------- Cloudinary REST call ------------------------------------ */
        $cloud  = [
            'name'   => 'dmncite2f',
            'key'    => '375416852919216',
            'secret' => 'phgjs_lv4FERMePh0KZJx_ZygM8',
        ];

        $client = new Client([
            'base_uri' => "https://api.cloudinary.com/v1_1/{$cloud['name']}/",
            'auth'     => [$cloud['key'], $cloud['secret']],
            'timeout'  => 15,
        ]);

        $resources = [];
        $cursor    = null;

        do {
            $query = [
                'type'        => 'upload',
                'prefix'      => 'screenshots/',   // only our folder
                'max_results' => 500,
                'context'     => true,             // include custom context
            ];
            if ($cursor) $query['next_cursor'] = $cursor;

            $resp     = $client->get('resources/image', ['query' => $query]);
            $payload  = json_decode($resp->getBody()->getContents(), true);

            if (!isset($payload['resources'])) break;

            $resources = array_merge($resources, $payload['resources']);
            $cursor    = $payload['next_cursor'] ?? null;

        } while ($cursor);
        /* ------------------------------------------------------------------- */

        $filtered = array_values(array_filter($resources, function ($res) use ($userId, $date) {

            $ctx = $res['context']['custom'] ?? [];

            /* --------- 1. user match --------------------------------------- */
            if (($ctx['user_id'] ?? null) != $userId) {
                return false;
            }

            /* --------- 2. date match  -------------------------------------- */
            // NEW screenshots have ctx['date'] === 'YYYY-MM-DD'
            if (isset($ctx['date']) && $ctx['date'] === $date) {
                return true;
            }

            // OLD screenshots: only timestamp (ms). Convert on-the-fly
            if (isset($ctx['timestamp'])) {
                $ts  = (int) substr($ctx['timestamp'], 0, 10);      // ms → s
                $day = date('Y-m-d', $ts);
                return $day === $date;
            }

            return false;   // no usable metadata
        }));
        $shots = $filtered;
        //return $shots;
        return view('Company.userScreenshotsRequest', compact('users', 'shots'));
    }

    public function getIdleTime(){
        $userId   = Input::get('user_id');
        $from     = Input::get('from_date'); // e.g., 2025-06-05 09:00:00
        $to       = Input::get('to_date');   // e.g., 2025-06-05 17:00:00

        $query = UserIdleTimeLog::with('user');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($from && $to) {
            // Apply combined datetime range
            $query->whereRaw("STR_TO_DATE(CONCAT(log_date, ' ', time_end), '%Y-%m-%d %H:%i:%s') >= ?", [$from])
                ->whereRaw("STR_TO_DATE(CONCAT(log_date, ' ', time_start), '%Y-%m-%d %H:%i:%s') <= ?", [$to]);
        }

        $logs = $query->orderBy('log_date', 'desc')
            ->orderBy('time_start', 'desc')
            ->get();

        $users = User::where('company_id', auth()->user()->company_id)
            ->where('status', 1)
            ->orderBy('username')
            ->get();

        return view('Company.userIdleTimeLog', compact('logs', 'users'));
    }


}