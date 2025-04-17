@extends('Company.CompanyLayout')
@section('content')

    <div>
        <ul class="breadcrumb">
            <li>
                <a href="{!! URL::to('company') !!}">Home</a> <span class="divider">/</span>
            </li>
            <li>
                <a href=''{!! URL::to("company/table-report") !!}'>Table Report</a>
            </li>

        </ul>
    </div>
    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-user"></i> <?php echo $userInfo->username?>'s Attendance List
                    from <?php echo $startDate . ' to ' . $endDate ?></h2>

            </div>
            <div class="box-content">
                <table id="example" class="display" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <td>Date</td>
                        <td>In Time</td>
                        <td>Out Time</td>
                        <td>Working Hours</td>
                        <td>Break Time</td>
                        <td>Active Hour</td>
                        <td>Status</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allDate as $date): ?>
                    <?php
                    // Get attendance record for the day
                    $attendance = array_filter($attendanceReport, function ($ar) use ($date) {
                        return $ar['login_date'] == $date;
                    });
                    $attendance = reset($attendance); // Get first matched record


                    // Get holiday and leave status
                    $holiday = array_filter($allHoliday, fn($h) => $h['holiday'] == $date);
                    $leave = array_filter($allLeave, fn($l) => $l['leave_date'] == $date);

                    $dateAttendanceType = null;
                    if (!empty($holiday)) {
                        $dateAttendanceType =  'Holiday';
                    } elseif (!empty($leave)) {
                        $dateAttendanceType =  'Leave';
                    } elseif ($attendance) {
                        $dateAttendanceType =  'Present';
                    } else if(in_array($date, $weekends)){
                        $dateAttendanceType = 'Weekend';
                    } else {
                        $dateAttendanceType =  'Absent';
                    }

                    ?>
                    <tr>
                        <td><?= $date ?></td>
                        <td><?= $attendance ? \Carbon\Carbon::parse($attendance['first_login'])->toDateTimeString() : '-' ?></td>
                        <td>
                            <?php
                                if($dateAttendanceType == 'Present' && $attendance['last_logout'] != '0000-00-00 00:00:00'){
                                    echo \Carbon\Carbon::parse($attendance['last_logout'])->toDateTimeString();
                                }else if($dateAttendanceType == 'Present' && $attendance['last_logout'] == '0000-00-00 00:00:00'){
                                    echo "Not Yet Punch Out";
                                }else{
                                    echo '-';
                                }
                            ?>
                        </td>
                        <td data-order="{!! $attendance ? $attendance['work_time_second'] : 0 !!}">
                            <a href="{!! URL::to('company/attendance-log') !!}?s_date=<?php echo $date ?>&e_date=<?php echo $date ?>&id=<?php echo $userInfo->id ?>">
                                <?= $attendance ? $attendance['total_work_time'] : '00:00:00' ?>
                            </a>
                        </td>
                        <td data-order="{!! $attendance ? $attendance['break_time_second'] : 0 !!}">
                            <a href="{!! URL::to('company/break-time-log') !!}?s_date=<?php echo $date ?>&e_date=<?php echo $date ?>&id=<?php echo $userInfo->id ?>">
                                <?= $attendance ? $attendance['total_break_time'] : '00:00:00' ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            if ($attendance) {

                                $workTimeInSeconds = strtotime($attendance['total_work_time']) - strtotime("00:00:00");
                                $breakTimeInSeconds = strtotime($attendance['total_break_time']) - strtotime("00:00:00");
                                if($attendance['total_break_time'] != null){
                                    $activeTimeInSeconds = max(0, $workTimeInSeconds - $breakTimeInSeconds); // Ensure non-negative
                                }else{
                                    $activeTimeInSeconds = $workTimeInSeconds;
                                }
                                echo gmdate("H:i:s", $activeTimeInSeconds);
                            } else {
                                echo '00:00:00';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            echo $dateAttendanceType;
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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
        $(document).ready(function () {
            $('#example').DataTable({
                dom: 'T<"clear">lfrtip',
                pageLength: 31
            });
        });
    </script>
@endsection