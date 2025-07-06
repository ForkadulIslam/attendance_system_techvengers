@extends('Users/UserLayout')
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

<div class="row-fluid sortable">
    <div class="box span3">
        <div class="box-header well" data-original-title>
            <h2><i class="icon-list-alt"></i>  Punched IN <small>{!! count($activityWiseUserList['punchedInUser']) !!}</small></h2>

        </div>
        <div class="box-content">
            <ul>
                @foreach($activityWiseUserList['punchedInUser'] as $user)
                    <li>
                        {!! $user['name'] !!} <small>{!! $user['working_hours'] !!}</small>
                        <span class="badge badge-brown pull-right">{!! $user['logged_in_at'] !!}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <!--/span-->

    <div class="box span3">
        <div class="box-header well" data-original-title>
            <h2><i class="icon-list-alt"></i>  Break <small>{!! count($activityWiseUserList['onBreakUser']) !!}</small></h2>

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
                        {!! $user['name'] !!}
                        <small>{!! $user['break_duration'] !!}</small>
                        <small class="badge badge-brown pull-right">{!! $user['total_break_duration'] !!}</small>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="box span2">
        <div class="box-header well" data-original-title>
            <h2><i class="icon-list-alt"></i> Punched OUT <small>{!! count($activityWiseUserList['punchedOutUser']) !!}</small></h2>

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
            <h2><i class="icon-list-alt"></i> On Leave <small>{!! count($activityWiseUserList['onLeaveUser']) !!}</small></h2>

        </div>
        <div class="box-content">
            <ul>
                @foreach($activityWiseUserList['onLeaveUser'] as $user)
                    <li>
                        {!! $user->user->username !!}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="box span2">
        <div class="box-header well" data-original-title>
            <h2><i class="icon-list-alt"></i> Absent <small>{!! count($activityWiseUserList['notPunchedInUser']) !!}</small></h2>

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
                    const now = new Date();
                    messagePayload.logged_in_at = now.toISOString();
                    messagePayload.total_break_duration = '00:00';
                } else if (status === 'Start Break') {
                    const now = new Date();
                    messagePayload.break_start_time = now.toISOString();
                } else if (status === 'End Break' || status === 'Punch Out') {
                    try {
                        const response = await fetch('/api/getUser/' + '{{ Auth::user()->id }}');
                        const data = await response.json();
                        console.log(data);
                        let loggedInAtApi = data.logged_in_at;
                        if (loggedInAtApi && loggedInAtApi.includes(' ')) {
                            loggedInAtApi = loggedInAtApi.replace(' ', 'T');
                        }
                        messagePayload.logged_in_at = new Date(loggedInAtApi).toISOString();
                        messagePayload.total_break_duration = data.total_break_duration;
                        if (status === 'Punch Out') {
                            const now = new Date();
                            messagePayload.logged_out_at = now.toISOString();
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
@endsection

