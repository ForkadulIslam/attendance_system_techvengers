@extends('Company/CompanyLayout')
@section('content')
    @if ($activeUser->count())
        <div class="alert alert-info">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?php $aU = $activeUser->count();
                foreach($activeUser as $userActive):
                $aU = $aU - 1;
                ?>
                <a target="blank" style="text-decoration:none;cursor:pointer"
                   href="{!! URL::to('company/report') !!}?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=<?php echo $userActive->user_id ?>">
                    {{ @$userActive->User->username }}
                    @if ($aU != 0) ,
                    @endif
                </a>
                <?php
                endforeach; ?>
                @if ($activeUser->count() > 1)
                    are
                @else
                    is
                @endif
                present today</strong>
        </div>


    @endif
    <?php
    if($lateUser->count()){
    ?>
    <div class="alert alert-info">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong><?php $lU = $lateUser->count();
            foreach($lateUser as $userLate):
            $lU = $lU - 1;
            ?>
            <a target="blank" style="text-decoration:none;cursor:pointer"
               href="{!! URL::to('company/report') !!}?s_date=<?php echo date('Y-m-d') ?>&e_date=<?php echo date('Y-m-d') ?>&id=<?php echo $userLate->user_id ?>">
                <?php echo $userLate->User->username;
                if ($lU != 0) echo ',';?>
            </a>
            <?php
            endforeach;
            if ($lateUser->count() > 1)
                echo 'are';
            else echo 'is';
            ?> late today</strong>
    </div>
    <?php } if($totalUser - $activeUser->count()) { ?>
    <div class="alert alert-info">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>
            <?php
            $aU = $totalUser - $activeUser->count();
            if ($aU == $totalUser) echo 'No';
            else echo $aU;
            ?> users <?php
            if ($aU > 1)
                echo 'are';
            else echo 'is';
            ?> not present yet</strong>
    </div>
    <?php
    }
    if($withLeaveNotification){
    ?>
    <div class="alert alert-info">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong><a style="text-decoration:none; cursor:pointer" href="{!! URL::to('company/all-leave') !!}">You
                Have <?php echo $withLeaveNotification?> day's Leave Request</a></strong>
    </div>
    <?php }?>
    <div>
        <ul class="breadcrumb">
            <li>
                <a href="{!! URL::to('company') !!}">Home</a> <span class="divider">/</span>
            </li>
            <li>
                <a href="{!! URL::to('company') !!}">Dashboard</a>
            </li>
        </ul>
    </div>

    <div class="row-fluid">
        <div class="box span12">
            <div class="box-header well">
                <h2><i class="icon-info-sign"></i> Introduction</h2>

            </div>
            <div class="box-content">
                <h1>Welcome to {{ @Auth::user()->Company->company_name }} dashboard </h1>

                <div class="clearfix"></div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="box span3">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> Punched IN
                    <small>{!! count($activityWiseUserList['punchedInUser']) !!}</small></h2>

            </div>
            <div class="box-content">
                <ul>
                    @foreach($activityWiseUserList['punchedInUser'] as $user)
                        <li>
                            <a style="font-size: 13px; color:#666;"
                               href="{!! URL::to('company/attendance-log') !!}?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=<?php echo $user['id'] ?>">
                                {!! $user['name'] !!} <small>{!! $user['working_hours'] !!}</small>
                                <span class="badge badge-brown pull-right">{!! $user['logged_in_at'] !!}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <!--/span-->

        <div class="box span3">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> Break
                    <small>{!! count($activityWiseUserList['onBreakUser']) !!}</small></h2>

            </div>
            <div class="box-content">
                <ul>
                    @foreach($activityWiseUserList['onBreakUser'] as $user)

                        <?php
                        list($hours, $minutes) = explode(':', $user['break_duration']);
                        $totalMinutes = ($hours * 60) + $minutes;
                        $highlightClass = $totalMinutes > 30 ? 'breakTimeHighlighter' : '';
                        ?>

                        <li class="{{ $highlightClass }}">
                            <a style="font-size: 13px; color:#666;"
                               href="{!! URL::to('company/break-time-log') !!}?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=<?php echo $user['id'] ?>">
                                {!! $user['name'] !!}
                                <small>{!! $user['break_duration'] !!}</small>
                                <small class="badge badge-brown pull-right">{!! $user['total_break_duration'] !!}</small>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="box span2">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> Punched OUT
                    <small>{!! count($activityWiseUserList['punchedOutUser']) !!}</small></h2>

            </div>
            <div class="box-content">
                <ul>
                    @foreach($activityWiseUserList['punchedOutUser'] as $user)
                        <li>
                            {!! $user['name'] !!}
                            <span class="badge badge-brown pull-right">{!! $user['logged_out_at'] !!}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="box span2">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> On Leave
                    <small>{!! count($activityWiseUserList['onLeaveUser']) !!}</small></h2>

            </div>
            <div class="box-content">
                <ul>
                    @foreach($activityWiseUserList['onLeaveUser'] as $user)
                        <li>
                            <a style="font-size: 13px; color:#666;" href="#">
                                {!! $user->user->username !!}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="box span2">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> Absent
                    <small>{!! count($activityWiseUserList['notPunchedInUser']) !!}</small></h2>

            </div>
            <div class="box-content">
                <ul>
                    @foreach($activityWiseUserList['notPunchedInUser'] as $user)
                        <li>
                            <a style="font-size: 13px; color:#666;" href="#">
                                {!! $user['name'] !!}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>



    <div class="row-fluid">
        <div class="box span12 ">
            <div class="box-header well">
                <h2><i class="icon icon-notice"></i> Notice Board</h2>
            </div>
            <div class="box-content">
                <div ng-app="myApp" ng-controller="deleteController">
                    <?php
                    if($allNotice){
                    foreach($allNotice as $key=>$notice): ?>
                    <div class="row-fluid sortable" id="row_{!! $notice->id !!}">
                        <div class="box span12">
                            <div class="box-header well" data-original-title>
                                <h2><i class="icon icon-notice"></i> <?php echo $notice->subject?></h2>
                                @if(Auth::user()->user_label ==1 )
                                    <div class="box-icon">
                                        <a href='{!! URL::to("company/notice-board/$notice->id/edit") !!}'
                                           class="btn btn-minimize btn-round"><i class="icon-edit"></i></a>
                                        <a href="#" ng-click="delete(<?php echo $notice->id ?>,$event)"
                                           class="btn btn-close btn-round"><i class="icon-remove"></i></a>
                                    </div>
                                @endif
                            </div>
                            <div class="box-content" id="ajax_table">
                                <?php echo $notice->message?>
                            </div>
                        </div><!--/span-->

                    </div>
                    <?php endforeach;}?>
                </div>
            </div>
        </div>
    </div>


    <?php $message_error = Session::get('flashError'); if ($message_error) { ?>
    <script type="text/javascript">
        $(document).ready(function () {
            $.pnotify({
                title: 'Error',
                text: '<?php echo $message_error ?>',
                type: 'error',
                delay: 3000

            });
        });
    </script>

    <?php } ?>
    <?php $message_success = Session::get('flashSuccess');; if ($message_success) { ?>
    <script type="text/javascript">
        $(document).ready(function () {
            $.pnotify({
                title: 'Message',
                text: '<?php echo $message_success ?>',
                type: 'success',
                delay: 3000

            });
        });
    </script>

    <?php } ?>
    <?php
    $totalHours = 0;
    $totalMinutes = 0;
    $totalSeconds = 0;
    $reports = array();
    foreach ($attendanceReport as $key => $report) {
        if (!isset($reports[$report->user_id])) {
            $totalHours = 0;
            $totalMinutes = 0;
            $totalSeconds = 0;
        }
        $reports[$report->user_id]['id'] = $report->id;
        $reports[$report->user_id]['user_id'] = $report->user_id;
        $reports[$report->user_id]['username'] = $report->User->username;
        $reports[$report->user_id]['time'] = explode(":", $report->timediff);;
        $reports[$report->user_id]['workingHours'] = ($totalHours = $totalHours + $reports[$report->user_id]['time'][0]);
        $reports[$report->user_id]['workingMinutes'] = ($totalMinutes = $totalMinutes + $reports[$report->user_id]['time'][1]);
        $reports[$report->user_id]['workingSeconds'] = ($totalSeconds = $totalSeconds + $reports[$report->user_id]['time'][2]);
    }
    ?>
@endsection
@section('jsBottom')
    <script>
        var myApp = angular.module('myApp', [], function ($interpolateProvider) {
            $interpolateProvider.startSymbol('{kp');
            $interpolateProvider.endSymbol('kp}');
        });

        myApp.controller('deleteController', function ($scope, $http) {
            $scope.delete = function (id, event) {
                event.preventDefault();
                var req = {
                    method: 'DELETE',
                    url: '{!! URL::to("company/notice/") !!}/' + id,
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: ''
                };
                var chk = confirm("Are you sure to delete this?");
                if (chk) {
                    $http(req).success(function (response) {
                        if (response == 'true') {
                            $("#row_" + id).html('');
                            $.pnotify({
                                title: 'Success',
                                text: 'Notice Deleted',
                                type: 'success',
                                delay: 3000
                            });
                        } else {
                            $.pnotify({
                                title: 'ERROR',
                                text: response,
                                type: 'error',
                                delay: 3000
                            });
                        }
                    });
                }
            };
        });
    </script>
    <script>
        var chart = AmCharts.makeChart("chartdiv", {
            "type": "serial",
            "theme": "none",
            "pathToImages": "http://www.amcharts.com/lib/3/images/",
            "dataProvider": [
                    <?php foreach($reports as $report){
                    $minutesActual = $report['workingHours'] * 60 + $report['workingMinutes'];
                    $hours = $report['workingHours'];
                    $minutes = $report['workingMinutes'];
                    $seconds = $report['workingSeconds'];
                    if ($report['workingMinutes'] / 60) {
                        $hours = intval($hours + $report['workingMinutes'] / 60);
                        $minutes = intval($report['workingMinutes'] % 60);
                    }
                    if ($report['workingSeconds'] / 60) {
                        $minutes = intval($minutes + $report['workingSeconds'] / 60);
                        $seconds = intval($report['workingSeconds'] % 60);
                    }
                    if (intval($hours) < 10)
                        $hours = '0' . intval($hours);
                    if (intval($minutes) < 10)
                        $minutes = '0' . intval($minutes);
                    if (intval($seconds) < 10)
                        $seconds = '0' . intval($seconds);
                    $timeSHow = $hours . ':' . $minutes . ':' . $seconds;
                    ?>
                {
                    "country": "<?php echo $report['username'] . '(' . $timeSHow . ')'?>",
                    "visits": "<?php echo $minutesActual?>",
                    "color": "<?php echo sprintf('#%06X', mt_rand(0, 0xFFFFFF));?>"
                },
                <?php }?>
            ],
            "valueAxes": [{
                "axisAlpha": 0,
                "position": "left",
                "title": "Minutes Work Today"
            }],
            "startDuration": 1,
            "graphs": [{
                "balloonText": "<b>[[category]]: [[value]]</b>",
                "colorField": "color",
                "fillAlphas": 0.9,
                "lineAlpha": 0.2,
                "type": "column",
                "valueField": "visits"
            }],
            "chartCursor": {
                "categoryBalloonEnabled": false,
                "cursorAlpha": 0,
                "zoomable": false
            },
            "categoryField": "country",
            "categoryAxis": {
                "gridPosition": "start",
                "labelRotation": 45
            },
            "amExport": {}

        });

    </script>

    <script type="text/javascript">
        setInterval(function () {
            location.reload();
        }, 30000);
        google.load("visualization", "1", {packages: ["corechart"]});
        google.setOnLoadCallback(drawChart);

        function drawChart() {

            var data = google.visualization.arrayToDataTable([
                ['Task', 'Hours per Day'],
                    <?php foreach($reports as $report){
                    $minutesActual = $report['workingHours'] * 60 + $report['workingMinutes'];
                    $hours = $report['workingHours'];
                    $minutes = $report['workingMinutes'];
                    $seconds = $report['workingSeconds'];
                    if ($report['workingMinutes'] / 60) {
                        $hours = intval($hours + $report['workingMinutes'] / 60);
                        $minutes = intval($report['workingMinutes'] % 60);
                    }
                    if ($report['workingSeconds'] / 60) {
                        $minutes = intval($minutes + $report['workingSeconds'] / 60);
                        $seconds = intval($report['workingSeconds'] % 60);
                    }
                    if (intval($hours) < 10)
                        $hours = '0' . intval($hours);
                    if (intval($minutes) < 10)
                        $minutes = '0' . intval($minutes);
                    if (intval($seconds) < 10)
                        $seconds = '0' . intval($seconds);
                    $timeSHow = $hours . ':' . $minutes . ':' . $seconds;
                    ?>

                ["<?php echo $report['username'] . '(' . $timeSHow . ')'?>",     <?php echo $minutesActual?>],
                <?php }?>
            ]);

            var options = {
                title: 'Daily Activities of All User'
            };

            var chart = new google.visualization.PieChart(document.getElementById('piechart'));

            chart.draw(data, options);
        }
    </script>
@endsection