@extends('Company.CompanyLayout')
@section('content')

<?php
use App\Leave;
use App\User;
$totalHours = 0;
$totalMinutes = 0;
$totalSeconds = 0;
$reports = [];


// Generate all dates between start and end date


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
    if (isset($breakReport[$userId])) {
        $reports[$userId]['totalBreakSeconds'] = (int) $breakReport[$userId];
    }
}


// Convert seconds to HH:MM:SS format
foreach ($reports as &$report) {

    // Convert total working time
    $workHours = floor($report['totalSeconds'] / 3600);
    $workMinutes = floor(($report['totalSeconds'] % 3600) / 60);
    $workSeconds = $report['totalSeconds'] % 60;
    $report['workingTime'] = sprintf("%d:%02d", $workHours, $workMinutes);

    // Convert break time
    $breakHours = floor($report['totalBreakSeconds'] / 3600);
    $breakMinutes = floor(($report['totalBreakSeconds'] % 3600) / 60);
    $breakSeconds = $report['totalBreakSeconds'] % 60;
    $report['breakTime'] = sprintf("%d:%02d", $breakHours, $breakMinutes);

    // Calculate Active Time (Total Time - Break Time)
    $activeSeconds = max(0, $report['totalSeconds'] - $report['totalBreakSeconds']);
    $activeHours = floor($activeSeconds / 3600);
    $activeMinutes = floor(($activeSeconds % 3600) / 60);
    $activeSec = $activeSeconds % 60;
    $report['activeSeconds'] = $activeSeconds;
    $report['activeTime'] = sprintf("%d:%02d", $activeHours, $activeMinutes);

    $attendanceWorkingDateCollection = collect($attendanceReport)->where('user_id', $report['user_id'])->groupBy('login_date');
    $report['presentDays'] = $attendanceWorkingDateCollection->count();
    $report['presentDays'] += collect($weekEndAttendanceReport)->where('user_id', $report['user_id'])->groupBy('login_date')->count();
    $userLeave = $approvedLeaves->where('id',$report['user_id'])->first();
    $userAuthorizedLeave = $approvedAuthorizedLeaves->where('id',$report['user_id'])->first();
    if($userLeave){
        $report['approvedLeave'] = $userLeave->approvedLeave->count();
    }else{
        $report['approvedLeave'] = 0;
    }
    if($userAuthorizedLeave){
        $authorizedHalfDayLeave = $userAuthorizedLeave->approvedLeave->where('is_half_day', .5)->sum('is_half_day');
        $authorizedLeaveCount = $userAuthorizedLeave->approvedLeave->where('is_half_day', null)->count();
        $report['authorizedLeave'] = $authorizedLeaveCount + $authorizedHalfDayLeave;
    }else{
        $report['authorizedLeave'] = 0;
    }

    $report['presentDays'] -= $authorizedHalfDayLeave;
    $attendanceDateList = array_keys($attendanceWorkingDateCollection->toArray());
    $absentCount = 0;
    foreach ($allDates as $date){
        if(!in_array($date, $attendanceDateList) && !in_array($date, $holidays) && !in_array($date, $userLeave->approvedLeave->lists('leave_date')->toArray())){
            $absentCount++;
        }
    }
    Log::info($userAuthorizedLeave->approvedLeave->where('is_half_day', null));
    $report['absentDays'] = $absentCount - $authorizedLeaveCount;

    // --- New: Average Break Time ---
    if ($report['presentDays'] > 0) {
        $avgBreakSeconds = round($report['totalBreakSeconds'] / $report['presentDays']);
        $report['averageBreakTime'] = gmdate("H:i", $avgBreakSeconds);

        $avgActiveSeconds = round($activeSeconds / $report['presentDays']);
        $report['averageActiveTime'] = gmdate("H:i", $avgActiveSeconds);
    } else {
        $report['averageBreakTime'] = '00:00';
        $report['averageActiveTime'] = '00:00';
    }
}
unset($report);
?>
    <div>
        <ul class="breadcrumb">
            <li>
                <a href="{!! URL::to('company') !!}">Home</a> <span class="divider">/</span>
            </li>
            <li>
                <a href=''{!! URL::to("company/report-summery") !!}'>Summery Report</a>
            </li>

        </ul>
    </div>
    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-user"></i> Summery Report <?php echo $startDate.' to '.$endDate ?></h2>

            </div>
            <div class="box-content">
                <table id="example" class="display" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <td>
                            Name
                        </td>
                        <td>
                            Time
                        </td>
                        <td>Break Time</td>
                        <td>
                            Active Hour
                        </td>
                        <td>AVG Break Time</td>
                        <td>AVG Active Hour</td>
                        <td>Present Days</td>
                        <td>Absent Days</td>
                        <td>Approved Leave</td>
                        <td>Authorized Leave</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($reports as $report){
                    ?>
                    <tr>
                        <td>
                            <?php echo $report['username']?>
                        </td>
                        <td data-order="{{ $report['totalSeconds'] }}">
                            <?php
                            echo $report['workingTime'];
                            ?>
                        </td>
                        <td data-order="{{ $report['totalBreakSeconds'] }}"><?php echo $report['breakTime']; ?></td>

                        <td data-order="{{ $report['activeSeconds'] }}">
                            <?php
                            echo $report['activeTime']
                            ?>
                        </td>
                        <td>
                            <?php
                                echo $report['averageBreakTime'];
                            ?>
                        </td>
                        <td>
                            <?php
                            echo $report['averageActiveTime'];
                            ?>
                        </td>
                        <td>
                            {{ $report['presentDays'] }}
                        </td>
                        <td>
                            {{ $report['absentDays'] }}
                        </td>
                        <td>
                            {{ $report['approvedLeave'] }}
                        </td>
                        <td>
                            {!! $report['authorizedLeave'] !!}
                        </td>

                    </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div><!--/span-->
    </div>
    @endsection
@section('jsBottom')
    {!! HTML::script('js/jquery.dataTables.js') !!}
    {!! HTML::script('js/dataTables.tableTools.js') !!}
    {!! HTML::style('css/jquery.dataTables.css') !!}
    {!! HTML::style('css/dataTables.tableTools.css') !!}
    <script type="text/javascript" language="javascript" class="init">
        $(document).ready(function() {
            $('#example').DataTable( {
                dom: 'T<"clear">lfrtip',
                pageLength: 40
            } );
        } );
    </script>
@endsection