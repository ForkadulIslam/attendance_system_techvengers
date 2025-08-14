@extends('Users/UserLayout')
@section('cssTop')
    <style>
        .DTTT_button_print{
            display: none!important;
        }
        .dataTables_filter input{
            width: 130px;
        }
    </style>
@endsection
@section('content')
<?php $welcome_message=Session::get('welcome_message'); ?>
@if ($welcome_message)
<div class="alert alert-info">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <strong>{{ $welcome_message }}</strong>
</div>
@endif
@if ($leaveUpdate)
<div class="alert alert-info">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <strong><a style="text-decoration:none; cursor:pointer" href="{!! URL::to('user/my-leave') !!}">Your's {{ $leaveUpdate }} Leave Application Updated</a></strong>
</div>
@endif

<div>
    <ul class="breadcrumb">
        <li>
            <a href=" {!! URL::to('user') !!}">Home</a> <span class="divider">/</span>
        </li>
        <li>
            <a href="{!! URL::to('user') !!}">Dashboard</a>
        </li>
    </ul>
</div>
<div class="row-fluid sortable">
    <div class="box span4">
        <div class="box-header well" data-original-title>
            <h2><i class="icon-list-alt"></i>  Punch In/Out</h2>

        </div>
        <div class="box-content">
            <?php
            if ($status == 'Punch Out')
                $punch_url = 'punch-out';
            else
                $punch_url = 'punch-in';
            ?>

                @if (\App\UserBreak::isUserOnBreak(Auth::id()))
                    <a href="#" onclick="confirmPunch('{!! route('break.end') !!}', 'End Break')" class="btn btn-large btn-danger">
                        End Break
                    </a>
                @else
                    <a href="#" onclick="confirmPunch('{!! URL::to("user/$punch_url/") !!}', '{{ $status }}')" class="btn btn-large btn-success">
                        {{ $status }}
                    </a>
                    @if($status == 'Punch Out')
                    <a href="#" onclick="confirmPunch('{!! route('break.start') !!}', 'Start Break')" class="btn btn-large btn-warning">
                        Start Break
                    </a>
                    @endif
                @endif
        </div>
    </div>
    <!--/span-->

    <div class="box span4">
        <div class="box-header well" data-original-title>
            <h2><i class="icon-user"></i> Attendance Log</h2>

        </div>
        <div class="box-content">
            <div class="box-content">


                <div class="form-group span12">
                    <label for="datepick2" class="span2 control-label">Date</label>
                    <div class="span6">
                        <input type="text" readonly id="datepicker" class="datepicker" name="first_date" value="<?php echo date('Y-m-d', time()); ?>">
                    </div>
                </div>
                <div class="form-group span12">
                    <label for="datepick4" class="span2 control-label">To</label>
                    <div class="span6">
                        <input type="text" readonly id="datepicker2" class="datepicker2" name="second_date" value="<?php echo date('Y-m-d', time()); ?>">
                    </div>
                </div>
                <div class="form-group span12">
                    <label for="datepick4" class="span2 control-label"></label>
                    <div class="span6">
                        <button onclick="window.open('{!! URL::to("user/report") !!}?s_date=' + datepicker.value + '&e_date=' + datepicker2.value)" type="button" class="btn btn-primary">
                            Run Report</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="box span4">
        <div class="box-header well" data-original-title>
            <h2><i class="icon-user"></i> Break-time Log</h2>

        </div>
        <div class="box-content">
            <div class="box-content">


                <div class="form-group span12">
                    <label for="datepick2" class="span2 control-label">Date</label>
                    <div class="span6">
                        <input type="text" readonly id="datepicker3" class="datepicker" name="first_date" value="<?php echo date('Y-m-d', time()); ?>">
                    </div>
                </div>
                <div class="form-group span12">
                    <label for="datepick4" class="span2 control-label">To</label>
                    <div class="span6">
                        <input type="text" readonly id="datepicker4" class="datepicker2" name="second_date" value="<?php echo date('Y-m-d', time()); ?>">
                    </div>
                </div>
                <div class="form-group span12">
                    <label for="datepick4" class="span2 control-label"></label>
                    <div class="span6">
                        <button onclick="window.open('{!! URL::to("user/break-time-log") !!}?s_date=' + datepicker3.value + '&e_date=' + datepicker4.value)" type="button" class="btn btn-primary">
                            Check Report</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div><!--/span-->

<div class="row-fluid">
    <div class="box span4">
        <div class="box-header well" data-original-title>
            <h2><i class="icon-list-alt"></i> Punched IN
                <small id="punched-in-count">{!! count($activityWiseUserList['punchedInUser']) !!}</small></h2>

        </div>
        <div class="box-content">
            <table id="punched-in-table" class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Total break</th>
                    <th>Idle Time</th>
                    <th>Punched in</th>
                </tr>
                </thead>
                <tbody id="punched-in-list">
                @foreach($activityWiseUserList['punchedInUser'] as $user)
                    <tr id="punched-in-user-{!! $user['id'] !!}">
                        <td>
                            <a style="font-size: 13px; color:#666;"
                               href="{!! URL::to('company/attendance-log') !!}?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=<?php echo $user['id'] ?>">
                                {!! $user['name'] !!}
                            </a>
                        </td>
                        <td>
                            <small>{!! $user['total_break_duration'] !!}</small>
                        </td>
                        <td>
                            <small>
                                <?php
                                $idleTime = collect($activityWiseUserList['usersIdleTimeLog'])->where('user_name',$user['name'])->first();
                                //Log::info($idleTime['totalIdleTime'])
                                ?>
                                {!!  $idleTime ? $idleTime['totalIdleTime'] : 0 !!}
                            </small>
                        </td>
                        <td>
                            <span class="badge badge-brown">{!! $user['logged_in_at'] !!}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <!--/span-->

    <div class="box span4">

        <div class="box-header well" data-original-title>
            <h2><i class="icon-time"></i> Idle Users <small id="idle-count">0</small></h2>
        </div>
        <div class="box-content">
            <ul id="idle-list" class="user-status-list">
                {{-- This list will be populated by Ably --}}
            </ul>
        </div>

        <hr>

        <div class="box-header well" data-original-title>
            <h2><i class="icon-list-alt"></i> Break
                <small id="on-break-count">{!! count($activityWiseUserList['onBreakUser']) !!}</small></h2>

        </div>
        <div class="box-content">
            <table id="on-break-table" class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Total break</th>
                    <th>Running break</th>
                </tr>
                </thead>
                <tbody id="on-break-list">
                @foreach($activityWiseUserList['onBreakUser'] as $user)

                    <?php
                    list($hours, $minutes) = explode(':', $user['break_duration']);
                    $totalMinutes = ($hours * 60) + $minutes;
                    $highlightClass = $totalMinutes > 30 ? 'breakTimeHighlighter' : '';
                    ?>

                    <tr id="on-break-user-{!! $user['id'] !!}" class="{{ $highlightClass }}">
                        <td>
                            <a style="font-size: 13px; color:#666;"
                               href="{!! URL::to('company/break-time-log') !!}?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=<?php echo $user['id'] ?>">
                                {!! $user['name'] !!}
                            </a>
                        </td>
                        <td>
                            <small>{!! $user['total_break_duration'] !!}</small>
                        </td>

                        <td>
                            <small class="badge badge-brown">{!! $user['break_duration'] !!}</small>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <hr>

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

    <div class="box span4">
        <div class="box-header well" data-original-title>
            <h2><i class="icon-list-alt"></i> Punched OUT
                <small id="punched-out-count">{!! count($activityWiseUserList['punchedOutUser']) !!}</small></h2>

        </div>
        <div class="box-content">
            <table id="punched-out-table" class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>User</th>
                    <th>Break</th>
                    <th>Punched in</th>
                    <th>Punched out</th>
                </tr>
                </thead>
                <tbody id="punched-out-list">
                @foreach($activityWiseUserList['punchedOutUser'] as $user)
                    <tr id="punched-out-user-{!! $user['id'] !!}">
                        <td>
                            {!! $user['name'] !!}
                        </td>
                        <td>
                            <small>{!! $user['total_break_duration'] !!}</small>
                        </td>
                        <td>
                            <span class="badge badge-brown">{!! $user['logged_in_at'] !!}</span>
                        </td>
                        <td>
                            <span class="badge badge-brown">{!! $user['logged_out_at'] !!}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
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
</div>

<div class="row-fluid">
    <div class="box span12 ">
        <div class="box-header well">
            <h2><i class="icon icon-notice"></i> Notice Board</h2>
        </div>
        <div class="box-content">
            <div>
                <?php
                if($allNotice){
                foreach($allNotice as $key=>$notice): ?>
                <div class="row-fluid sortable" id="row_{!! $notice->id !!}">
                    <div class="box span12">
                        <div class="box-header well" data-original-title>
                            <h2><i class="icon icon-notice"></i> <?php echo $notice->subject?></h2>

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

<?php $punch_message_success=Session::get('punchMessageSuccess');; if ($punch_message_success) { ?>
<script type="text/javascript">
    $(document).ready(function() {
        $.pnotify({
            title: 'Message',
            text: '<?php echo $punch_message_success ?>',
            type: 'success',
            delay: 3000

        });
    });
</script>

<?php } ?>
<?php $punch_message_error=Session::get('punchMessageError'); if ($punch_message_error) { ?>
<script type="text/javascript">
    $(document).ready(function() {
        $.pnotify({
            title: 'Logout',
            text: '<?php echo $punch_message_error ?>',
            type: 'success',
            delay: 3000

        });
    });
</script>

<?php } ?>

<?php $break_message_success = Session::get('success'); ?>
@if ($break_message_success)
    <script type="text/javascript">
        $(document).ready(function() {
            $.pnotify({
                title: 'Message',
                text: '{{ $break_message_success }}',
                type: 'success',
                delay: 3000
            });
        });
    </script>
@endif

<script src="https://cdn.ably.io/lib/ably.min-2.js"></script>
{!! HTML::script('js/jquery.dataTables.js') !!}
{!! HTML::script('js/dataTables.tableTools.js') !!}
<script>
    const ably = new Ably.Realtime('BEm5bw.24xxVQ:3nIhmsZUfMy_KRKWtOd5KcitYvWF-5VAUeTCieD_41k');
    const channel = ably.channels.get('attendance-updates');

    setInterval(function() {
        location.reload();
    }, 30000);
    function confirmPunch(url, status) {
        console.log(url);
        Swal.fire({
            title: "Are you sure?",
            text: "You are about to " + status.toLowerCase(),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, " + status.toLowerCase() + "!"
        }).then(async (result) => {
            if (result.isConfirmed) {
                let messagePayload = {
                    status: status,
                    user_id: '{{ Auth::user()->id }}',
                    name: '{{ Auth::user()->username }}'
                };

                if (status === 'Punch In') {

                    try{
                        const response = await fetch('/api/getUser/' + '{{ Auth::user()->id }}');
                        const data = await response.json();
                        const now = new Date();
                        messagePayload.logged_in_at = now.toISOString();
                        messagePayload.total_break_duration = '00:00';
                        messagePayload.net_idle_time = data.total_idle_time
                    }catch(error){
                        console.log("Start Break Event- Error:"+error)
                    }
                } else if (status === 'Start Break') {
                    try{
                        const response = await fetch('/api/getUser/' + '{{ Auth::user()->id }}');
                        const data = await response.json();
                        const now = new Date();
                        messagePayload.break_start_time = now.toISOString();
                        messagePayload.total_break_duration = data.total_break_duration;
                    }catch(error){
                        console.log("Start Break Event- Error:"+error)
                    }
                } else if (status === 'End Break' || status === 'Punch Out') {
                    try {
                        const response = await fetch('/api/getUser/' + '{{ Auth::user()->id }}');
                        const data = await response.json();
                        let loggedInAtApi = data.logged_in_at;
                        if (loggedInAtApi && loggedInAtApi.includes(' ')) {
                            loggedInAtApi = loggedInAtApi.replace(' ', 'T');
                        }
                        messagePayload.logged_in_at = new Date(loggedInAtApi).toISOString();
                        messagePayload.total_break_duration = data.total_break_duration;
                        if (status === 'Punch Out') {
                            const now = new Date();
                            messagePayload.logged_out_at = now.toISOString();
                            messagePayload.total_break_duration = data.total_break_duration; // Ensure break duration is sent
                        }
                    } catch (error) {
                        console.error('Error fetching user data:', error);
                        // Proceed without the extra data if API call fails
                    }
                }

                try {
                    await channel.publish('update', messagePayload);
                } catch (err) {
                    console.error('Ably publish failed:', err);
                } finally {
                    window.location.href = url;
                }
            }
        });
    }
</script>

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
            online: false,
            activity: 'active' // 'active' or 'idle'
        }]));

        // Initialize Ably
        const ably = new Ably.Realtime(ablyConfig);
        const presenceChannel = ably.channels.get('tracker-presence');
        const attendanceChannel = ably.channels.get('attendance-updates');

        // Datatables
        var punchedInTable = $('#punched-in-table').DataTable({
            dom: 'T<"clear">lfrtip',
            pageLength: 40
        });
        var onBreakTable = $('#on-break-table').DataTable({
            dom: 'T<"clear">lfrtip',
            pageLength: 40
        });
        var punchedOutTable = $('#punched-out-table').DataTable({
            dom: 'T<"clear">lfrtip',
            pageLength: 40
        });

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

        function updateIdleList() {
            const idleList = document.getElementById('idle-list');
            const idleCount = document.getElementById('idle-count');

            if (!idleList || !idleCount) return;

            idleList.innerHTML = '';
            let idleUsers = 0;

            userStatusMap.forEach(user => {
                if (user.online && user.activity === 'idle') {
                    const li = document.createElement('li');
                    const idleTime = user.idleSince ? new Date(user.idleSince).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
                    li.innerHTML = `<a href="${user.link || '#'}" style="color: #666; font-size: 13px;">${user.name}</a> <small class="badge badge-warning">${idleTime}</small>`;
                    idleList.appendChild(li);
                    idleUsers++;
                }
            });

            idleCount.textContent = idleUsers;
        }

        // Handle presence updates
        async function handlePresenceUpdate(member) {
            //console.log(member.timestamp);
            let userId = null;
            if(member.clientId){
                let clientIdSplit = member.clientId.split('-');
                userId = clientIdSplit[0] === 'tracker' ? clientIdSplit[1] : null;
            }
            if (!userId || !userStatusMap.has(userId)) return;

            const user = userStatusMap.get(userId);
            user.online = member.action !== 'leave';

            if (user.online) {
                if (member.data && member.data.status) {
                    user.activity = member.data.status;
                    if (user.activity === 'idle') {
                        user.idleSince = member.timestamp;
                    } else {
                        delete user.idleSince;
                    }
                }
            } else {
                user.activity = 'offline';
                delete user.idleSince;
            }

            console.log(`User ${userId} is ${user.online ? 'online' : 'offline'} and status is ${user.activity}`);

            updateUserLists();
            updateIdleList();
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
                presenceChannel.presence.subscribe(['enter', 'update', 'leave'], handlePresenceUpdate);

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
                let formatted_logged_in_at = '';
                try {
                    formatted_logged_in_at = new Date(logged_in_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'America/New_York' });
                } catch (e) {
                    console.error('Error formatting logged_in_at:', e, logged_in_at);
                    formatted_logged_in_at = 'Invalid Time'; // Fallback
                }
                punchedInTable.row.add([
                    `<a style="font-size: 13px; color:#666;" href="/company/attendance-log?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=${user_id}">${name}</a>`,
                    `<small>${total_break_duration}</small>`,
                    `<small>${message.data.net_idle_time}</small>`
                        `<span class="badge badge-brown">${formatted_logged_in_at}</span>`
                ]).node().id = `punched-in-user-${user_id}`;
                punchedInTable.draw();


                const punchedInCount = document.getElementById('punched-in-count');
                punchedInCount.textContent = parseInt(punchedInCount.textContent) + 1;
            } else if (status === 'Start Break') {
                // Add user to Break list
                console.log(total_break_duration);
                const formatted_break_start_time = new Date(break_start_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'America/New_York' });
                onBreakTable.row.add([
                    `<a style="font-size: 13px; color:#666;" href="/company/break-time-log?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=${user_id}">${name}</a>`,
                    `<small>${total_break_duration}</small>`,
                    `<small>00:00</small>`
                ]).node().id = `on-break-user-${user_id}`;
                onBreakTable.draw();

                const onBreakCount = document.getElementById('on-break-count');
                onBreakCount.textContent = parseInt(onBreakCount.textContent) + 1;
            } else if (status === 'End Break') {
                // Remove user from Break list
                onBreakTable.row('#on-break-user-' + user_id).remove().draw();

                const onBreakCount = document.getElementById('on-break-count');
                onBreakCount.textContent = parseInt(onBreakCount.textContent) - 1;


                // Update break duration in Punched In list
                const punchedInUserRow = punchedInTable.row(`#punched-in-user-${user_id}`);
                if(punchedInUserRow.node()) {
                    let formatted_logged_in_at = '';
                    try {
                        formatted_logged_in_at = new Date(logged_in_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'America/New_York' });
                    } catch (e) {
                        console.error('Error formatting logged_in_at:', e, logged_in_at);
                        formatted_logged_in_at = 'Invalid Time'; // Fallback
                    }
                    punchedInUserRow.data([
                        `<a style="font-size: 13px; color:#666;" href="/company/attendance-log?s_date=<?php echo date('Y-m-d', time()) ?>&e_date=<?php echo date('Y-m-d') ?>&id=${user_id}">${name}</a>`,
                        `<small>${total_break_duration}</small>`,
                        `<small>${message.data.net_idle_time}</small>`
                            `<span class="badge badge-brown">${formatted_logged_in_at}</span>`
                    ]).draw();
                }

            } else if (status === 'Punch Out') {
                // Remove user from Punched In list
                punchedInTable.row('#punched-in-user-' + user_id).remove().draw();
                const punchedInCount = document.getElementById('punched-in-count');
                punchedInCount.textContent = parseInt(punchedInCount.textContent) - 1;


                // Remove user from Break list (if they were on break)
                onBreakTable.row('#on-break-user-' + user_id).remove().draw();
                const onBreakCount = document.getElementById('on-break-count');
                onBreakCount.textContent = parseInt(onBreakCount.textContent) - 1;


                // Add user to Punched Out list
                const formatted_logged_out_at = new Date(logged_out_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'America/New_York' });
                punchedOutTable.row.add([
                    `${name}`,
                    `<small>${total_break_duration}</small>`,
                    `<span class="badge badge-brown">${new Date(logged_in_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'America/New_York' })}</span>`,
                    `<span class="badge badge-brown">${formatted_logged_out_at}</span>`
                ]).node().id = `punched-out-user-${user_id}`;
                punchedOutTable.draw();

                const punchedOutCount = document.getElementById('punched-out-count');
                punchedOutCount.textContent = parseInt(punchedOutCount.textContent) + 1;
            }
        }

        async function setupAttendanceUpdates() {
            await attendanceChannel.subscribe('update', handleAttendanceUpdate);
        }

        // Initialize
        updateUserLists();
        updateIdleList();
        setupPresence();
        setupAttendanceUpdates();
    });
</script>
@endsection

