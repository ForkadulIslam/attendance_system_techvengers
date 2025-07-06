@extends('Company.CompanyLayout')

@section('content')
    <div class="container">
        <h3>User Idle Time Logs</h3>
        <?php
        $totalIdleSeconds = $logs->sum('time_count_in_second');
        $hours = floor($totalIdleSeconds / 3600);
        $minutes = floor(($totalIdleSeconds % 3600) / 60);
        $seconds = $totalIdleSeconds % 60;

        $totalIdleFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        ?>

        {{-- ───────────── Search form ───────────── --}}
        {!! Form::open([
                'url'      => 'company/idle-time',
                'method'   => 'get',
                'id'       => 'shotForm',
                'class'    => 'form-inline',
                'autocomplete' => 'off'
        ]) !!}

        <?php
        $selectedUser = old('user_id', request('user_id'));
        $selectedFromDate = old('from_date', request('date', \Carbon\Carbon::now()->startOfDay()->toDateTimeString()));
        $selectedToDate = old('to_date', request('date', \Carbon\Carbon::now()->endOfDay()->toDateTimeString()));
        ?>

        <select name="user_id" id="user_id" class="input-medium" required>
            <option value="">Select user</option>
            @foreach($users as $item)
                <option value="{{ $item->id }}"
                        {{ $item->id == $selectedUser ? 'selected' : '' }}>
                    {{ $item->username }}
                </option>
            @endforeach
        </select>

        <input type="text" id="from" name="from_date"
               class="input-lg"
               placeholder="YYYY-MM-DD"
               required
               value="{{ $selectedFromDate }}">
        <input type="text" id="to" name="to_date"
               class="input-lg"
               placeholder="YYYY-MM-DD"
               required
               value="{{ $selectedToDate }}">

        <button class="btn btn-primary" type="submit">Search</button>
        {!! Form::close() !!}


        <?php
            if(isset($totalIdleFormatted)){
                ?>
                        <div class="alert alert-info">
                            <strong>Total Idle Time:</strong> {{ $totalIdleFormatted }}
                        </div>
                <?php
            }
        ?>

        <table id="logTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>User</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Idle Duration</th>
            </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->user->username ?? 'Unknown' }}</td>
                    <td>{{ $log->log_date }}</td>
                    <td>{{ $log->time_start }}</td>
                    <td>{{ $log->time_end }}</td>
                    <td>{{ $log->formatted_idle_time }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No logs found</td>
                </tr>
            @endforelse
            </tbody>
        </table>


    </div>
@endsection

@section('jsBottom')
    {!! HTML::script('js/jquery.dataTables.js') !!}
    {!! HTML::script('js/dataTables.tableTools.js') !!}
    {!! HTML::style('css/jquery.dataTables.css') !!}
    {!! HTML::style('css/dataTables.tableTools.css') !!}
    <script type="text/javascript">
        $(document).ready(function() {
            $('#logTable').DataTable( {
                dom: 'T<"clear">lfrtip',
                order: [[0,'desc']],
                pageLength: 100
            } );
            $( "#from" ).datetimepicker({
                dateFormat:'yy-mm-dd',
                timeFormat: 'HH:mm', // 24-hour format
                changeMonth: true,
                numberOfMonths: 1
            });
            $( "#to" ).datetimepicker({
                dateFormat:'yy-mm-dd',
                timeFormat: 'HH:mm', // 24-hour format
                changeMonth: true,
                numberOfMonths: 1
            });
        });
    </script>
@endsection

<?php $flashError=Session::get('flashError');; if ($flashError) { ?>
<script type="text/javascript">
    $(document).ready(function() {
        $.pnotify({
            title: 'ERROR',
            text: '<?php echo $flashError ?>',
            type: 'error',
            delay: 5000

        });
    });
</script>

<?php } ?>
