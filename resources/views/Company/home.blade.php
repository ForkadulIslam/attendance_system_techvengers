@extends('Company/CompanyLayout')

@section('cssTop')
    <style>
        .punchOutList{
            list-style: none;
            margin:0;
            padding: 0;
        }
        .punchOutList li p{
            margin:3px 0 4px 0;
            font-size: 10px;
            font-weight: 700;
            color:#868383;
        }
        .punchOutList li{
            margin-bottom: 10px;
            border-bottom:1px solid #dddddd;
        }

    </style>

    <style>
        .user-status-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .user-status-list li {
            padding: 3px 0;
            border-bottom: 1px solid #eee;
        }
        .online-users h4 {
            color: #5cb85c;
        }
        .offline-users h4 {
            color: #d9534f;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .online .status-indicator {
            background-color: #5cb85c;
        }
        .offline .status-indicator {
            background-color: #d9534f;
        }
    </style>
@endsection

@section('content')
    @if ($activeUser->count())
        <div class="alert alert-info">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><?php $aU = $activeUser->count(); ?>
                @foreach($activeUser as $userActive)
                <?php $aU = $aU - 1; ?>
                <a target="blank" style="text-decoration:none;cursor:pointer"
                   href="{!! URL::to('company/report') !!}?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=<?php echo $userActive->user_id ?>">
                    {{ @$userActive->User->username }}
                    @if ($aU != 0) ,
                    @endif
                </a>
                @endforeach
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
        <strong><?php $lU = $lateUser->count(); ?>
            @foreach($lateUser as $userLate)
            <?php $lU = $lU - 1; ?>
            <a target="blank" style="text-decoration:none;cursor:pointer"
               href="{!! URL::to('company/report') !!}?s_date=<?php echo date('Y-m-d') ?>&e_date=<?php echo date('Y-m-d') ?>&id=<?php echo $userLate->user_id ?>">
                <?php echo $userLate->User->username;
                if ($lU != 0) echo ',';?>
            </a>
            @endforeach
            @if ($lateUser->count() > 1)
                are
            @else
                is
            @endif
            late today</strong>
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
                    <small id="punched-in-count">{!! count($activityWiseUserList['punchedInUser']) !!}</small></h2>

            </div>
            <div class="box-content">
                <ul id="punched-in-list">
                    @foreach($activityWiseUserList['punchedInUser'] as $user)
                        <li id="punched-in-user-{!! $user['id'] !!}">
                            <a style="font-size: 13px; color:#666;"
                               href="{!! URL::to('company/attendance-log') !!}?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=<?php echo $user['id'] ?>">
                                {!! $user['name'] !!} <small>Break {!! $user['total_break_duration'] !!}</small>
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
                    <small id="on-break-count">{!! count($activityWiseUserList['onBreakUser']) !!}</small></h2>

            </div>
            <div class="box-content">
                <ul id="on-break-list">
                    @foreach($activityWiseUserList['onBreakUser'] as $user)

                        <?php
                        list($hours, $minutes) = explode(':', $user['break_duration']);
                        $totalMinutes = ($hours * 60) + $minutes;
                        $highlightClass = $totalMinutes > 30 ? 'breakTimeHighlighter' : '';
                        ?>

                        <li id="on-break-user-{!! $user['id'] !!}" class="{{ $highlightClass }}">
                            <a style="font-size: 13px; color:#666;"
                               href="{!! URL::to('company/break-time-log') !!}?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=<?php echo $user['id'] ?>">
                                {!! $user['name'] !!}
                                <small>Break Started: {!! $user['break_started_at'] !!}</small>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="box span2">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> Punched OUT
                    <small id="punched-out-count">{!! count($activityWiseUserList['punchedOutUser']) !!}</small></h2>

            </div>
            <div class="box-content">
                <ul class="punchOutList" id="punched-out-list">
                    @foreach($activityWiseUserList['punchedOutUser'] as $user)
                        <li id="punched-out-user-{!! $user['id'] !!}">
                            {!! $user['name'] !!}
                            <span class="badge badge-brown pull-right">{!! $user['logged_out_at'] !!}</span>
                            <p>IN: {!! $user['logged_in_at'] !!} || Break: {!! $user['total_break_duration'] !!}</p>

                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="box span2">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> IDLE Time </h2>

            </div>
            <div class="box-content">
                <ul>
                    @foreach($activityWiseUserList['usersIdleTimeLog'] as $user)
                        <li>
                            <a style="font-size: 13px; color:#666;" href="#">
                                {!! $user['user_name'] !!} - {!! $user['totalIdleTime'] !!}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="box span2">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> Absent
                    <small id="absent-count">{!! count($activityWiseUserList['notPunchedInUser']) !!}</small></h2>

            </div>
            <div class="box-content">
                <ul id="absent-list">
                    @foreach($activityWiseUserList['notPunchedInUser'] as $user)
                        <li id="absent-user-{!! $user['id'] !!}">
                            <a style="font-size: 13px; color:#666;" href="#">
                                {!! $user['name'] !!}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <hr>
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
    </div>



    <div class="row-fluid">
        <div class="box span4">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-user"></i> Online Status</h2>
            </div>
            <div class="box-content">
                <div id="realtime-status">
                    <div class="online-users">
                        <h4>Online (<span id="online-count">0</span>)</h4>
                        <ul id="online-list" class="user-status-list"></ul>
                    </div>
                    <div class="offline-users">
                        <h4>Offline (<span id="offline-count">0</span>)</h4>
                        <ul id="offline-list" class="user-status-list"></ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="box span8">
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





    <script src="https://cdn.ably.io/lib/ably.min-2.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // Configuration
            const ablyConfig = {
                key: 'BEm5bw.24xxVQ:3nIhmsZUfMy_KRKWtOd5KcitYvWF-5VAUeTCieD_41k',
                clientId: 'admin-panel-' + Math.random().toString(36).substring(2)
            };

            // User data from server
            const allUsers = {!! json_encode($allUsers ?? []) !!};
            const userStatusMap = new Map(allUsers.map(user => [user.id.toString(), {
                ...user,
                online: false
            }]));

            // Initialize Ably
            const ably = new Ably.Realtime(ablyConfig);
            const presenceChannel = ably.channels.get('tracker-presence');
            const attendanceChannel = ably.channels.get('attendance-updates');

            // Update UI function
            function updateUserLists() {
                const onlineList = document.getElementById('online-list');
                const offlineList = document.getElementById('offline-list');
                const onlineCount = document.getElementById('online-count');
                const offlineCount = document.getElementById('offline-count');

                onlineList.innerHTML = '';
                offlineList.innerHTML = '';

                let onlineUsers = 0;
                let offlineUsers = 0;

                userStatusMap.forEach(user => {
                    const li = document.createElement('li');
                    li.className = user.online ? 'online' : 'offline';
                    li.innerHTML = `
                        <span class="status-indicator"></span>
                        <a href="${user.link || '#'}" style="color: #666; font-size: 13px;">
                            ${user.name}
                        </a>
                    `;

                    if (user.online) {
                        onlineList.appendChild(li);
                        onlineUsers++;
                    } else {
                        offlineList.appendChild(li);
                        offlineUsers++;
                    }
                });

                onlineCount.textContent = onlineUsers;
                offlineCount.textContent = offlineUsers;
            }

            // Handle presence updates
            async function handlePresenceUpdate(member) {
                const userId = member.data?.userId?.toString();
                if (!userId || !userStatusMap.has(userId)) return;

                const isOnline = member.action !== 'leave';
                userStatusMap.get(userId).online = isOnline;
                updateUserLists();
            }

            // Main presence setup
            async function setupPresence() {
                try {
                    // Wait for connection
                    if (ably.connection.state !== 'connected') {
                        await new Promise(resolve => ably.connection.once('connected', resolve));
                    }

                    // Attach channel
                    await presenceChannel.attach();

                    // Get current presence
                    const presentMembers = await presenceChannel.presence.get();
                    presentMembers.forEach(member => {
                        member.action = 'present'; // Mark as existing presence
                        handlePresenceUpdate(member);
                    });

                    // Subscribe to changes
                    presenceChannel.presence.subscribe(['enter', 'leave'], handlePresenceUpdate);

                    // Enter admin presence
                    await presenceChannel.presence.enter({ admin: true });

                } catch (error) {
                    console.error('Presence error:', error);
                }
            }

            // Handle attendance updates
            async function handleAttendanceUpdate(message) {
                const { status, user_id, name, logged_in_at, total_break_duration, break_start_time, logged_out_at } = message.data;

                if (status === 'Punch In') {
                    // Remove user from Absent list
                    const absentUserElement = document.getElementById(`absent-user-${user_id}`);
                    if (absentUserElement) {
                        absentUserElement.remove();
                        const absentCount = document.getElementById('absent-count');
                        absentCount.textContent = parseInt(absentCount.textContent) - 1;
                    }

                    // Add user to Punched In list
                    const punchedInList = document.getElementById('punched-in-list');
                    const punchedInUserElement = document.createElement('li');
                    punchedInUserElement.id = `punched-in-user-${user_id}`;
                    let formatted_logged_in_at = '';
                    try {
                        formatted_logged_in_at = new Date(logged_in_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'America/New_York' });
                    } catch (e) {
                        console.error('Error formatting logged_in_at:', e, logged_in_at);
                        formatted_logged_in_at = 'Invalid Time'; // Fallback
                    }
                    punchedInUserElement.innerHTML = `
                        <a style="font-size: 13px; color:#666;"
                           href="/company/attendance-log?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=${user_id}">
                            ${name} ${total_break_duration && total_break_duration !== '00:00' ? `<small>Break ${total_break_duration}</small>` : ''}
                            <span class="badge badge-brown pull-right">${formatted_logged_in_at}</span>
                        </a>
                    `;
                    punchedInList.appendChild(punchedInUserElement);

                    const punchedInCount = document.getElementById('punched-in-count');
                    punchedInCount.textContent = parseInt(punchedInCount.textContent) + 1;
                } else if (status === 'Start Break') {
                    // Add user to Break list
                    const onBreakList = document.getElementById('on-break-list');
                    const onBreakUserElement = document.createElement('li');
                    onBreakUserElement.id = `on-break-user-${user_id}`;
                    const formatted_break_start_time = new Date(break_start_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'America/New_York' });
                    onBreakUserElement.innerHTML = `
                        <a style="font-size: 13px; color:#666;"
                           href="/company/break-time-log?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=${user_id}">
                            ${name}
                            <small>Break Started: ${formatted_break_start_time}</small>
                        </a>
                    `;
                    onBreakList.appendChild(onBreakUserElement);

                    const onBreakCount = document.getElementById('on-break-count');
                    onBreakCount.textContent = parseInt(onBreakCount.textContent) + 1;
                } else if (status === 'End Break') {
                    // Remove user from Break list
                    const onBreakUserElement = document.getElementById(`on-break-user-${user_id}`);
                    if (onBreakUserElement) {
                        onBreakUserElement.remove();
                        const onBreakCount = document.getElementById('on-break-count');
                        onBreakCount.textContent = parseInt(onBreakCount.textContent) - 1;
                    }

                    // Update break duration in Punched In list
                    const punchedInUserElement = document.getElementById(`punched-in-user-${user_id}`);
                    if(punchedInUserElement) {
                        let formatted_logged_in_at = '';
                        try {
                            formatted_logged_in_at = new Date(logged_in_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'America/New_York' });
                        } catch (e) {
                            console.error('Error formatting logged_in_at:', e, logged_in_at);
                            formatted_logged_in_at = 'Invalid Time'; // Fallback
                        }
                        punchedInUserElement.innerHTML = `
                        <a style="font-size: 13px; color:#666;"
                           href="/company/attendance-log?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=${user_id}">
                            ${name} ${total_break_duration && total_break_duration !== '00:00' ? `<small>Break ${total_break_duration}</small>` : ''}
                            <span class="badge badge-brown pull-right">${formatted_logged_in_at}</span>
                        </a>
                    `;
                    }

                } else if (status === 'Punch Out') {
                    // Remove user from Punched In list
                    const punchedInUserElement = document.getElementById(`punched-in-user-${user_id}`);
                    if (punchedInUserElement) {
                        punchedInUserElement.remove();
                        const punchedInCount = document.getElementById('punched-in-count');
                        punchedInCount.textContent = parseInt(punchedInCount.textContent) - 1;
                    }

                    // Remove user from Break list (if they were on break)
                    const onBreakUserElement = document.getElementById(`on-break-user-${user_id}`);
                    if (onBreakUserElement) {
                        onBreakUserElement.remove();
                        const onBreakCount = document.getElementById('on-break-count');
                        onBreakCount.textContent = parseInt(onBreakCount.textContent) - 1;
                    }

                    // Add user to Punched Out list
                    const punchedOutList = document.getElementById('punched-out-list');
                    const punchedOutUserElement = document.createElement('li');
                    punchedOutUserElement.id = `punched-out-user-${user_id}`;
                    let formatted_logged_out_at = '';
                    try {
                        formatted_logged_out_at = new Date(logged_out_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'America/New_York' });
                    } catch (e) {
                        console.error('Error formatting logged_out_at:', e, logged_out_at);
                        formatted_logged_out_at = 'Invalid Time'; // Fallback
                    }
                    let formatted_logged_in_at_punch_out = '';
                    try {
                        formatted_logged_in_at_punch_out = new Date(logged_in_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'America/New_York' });
                    } catch (e) {
                        console.error('Error formatting logged_in_at_punch_out:', e, logged_in_at);
                        formatted_logged_in_at_punch_out = 'Invalid Time'; // Fallback
                    }
                    punchedOutUserElement.innerHTML = `
                        ${name}
                        <span class="badge badge-brown pull-right">${formatted_logged_out_at}</span>
                        <p>IN: ${formatted_logged_in_at_punch_out} || Break: ${total_break_duration}</p>
                    `;
                    punchedOutList.appendChild(punchedOutUserElement);

                    const punchedOutCount = document.getElementById('punched-out-count');
                    punchedOutCount.textContent = parseInt(punchedOutCount.textContent) + 1;
                }
            }

            async function setupAttendanceUpdates() {
                await attendanceChannel.subscribe('update', handleAttendanceUpdate);
            }

            // Initialize
            updateUserLists();
            setupPresence();
            setupAttendanceUpdates();
        });
    </script>

@endsection