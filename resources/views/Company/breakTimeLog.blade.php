@extends('Company.CompanyLayout')
@section('content')

    <div>
        <ul class="breadcrumb">
            <li>
                <a href="{!! URL::to('user') !!}">Home</a> <span class="divider">/</span>
            </li>
            <li>
                <a href=''{!! URL::to("user/break-time-log") !!}'>Break Log</a>
            </li>
        </ul>
    </div>

    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header well">
                <h2><i class="icon-user"></i> {{ $userInfo->username }}'s Break Time Log [ {{ $startDate }} TO {{ $endDate }} ]</h2>
            </div>
            <div class="box-content">
                <div class="alert alert-info" style="margin-top: 20px;">
                    <strong>Total Break Duration:</strong> {{ $totalBreakDuration }}
                </div>
                <table id="breakTimeLogTable" class="display" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <td>Date</td>
                        <td>Break In</td>
                        <td>Break Out</td>
                        <td>Break Duration</td>
                        <td>Action</td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($allDate as $date)
                        <?php
                        $breaks = array_filter($breakLogs, function($log) use ($date) {
                            return date('Y-m-d', strtotime($log->break_start)) == $date;
                        });

                        $i = 0;
                        ?>

                        @foreach($breaks as $break)
                            <?php $i++; ?>
                            <tr>
                                <td>
                                    @if($i == 1)
                                        {{ $date }}
                                    @else
                                        <span style="display: none">{{ $date }}</span>
                                    @endif
                                </td>
                                <td>{{ $break->break_start }}</td>
                                <td>{{ $break->break_end }}</td>
                                <td>{{ $break->break_duration }}</td>
                                <td>
                                    <a class="btn btn-info btn-sm" href="{!! url('company/break-log-time-edit-request', $break->id) !!}">Edit</a>

                                    {!! Form::open([
                                        'url' => url('company/break-log-delete', $break->id),
                                        'method' => 'POST',
                                        'style' => 'display:inline;',
                                        'onsubmit' => 'return confirm("Are you sure you want to delete this break log?");'
                                    ]) !!}
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    {!! Form::close() !!}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('jsBottom')
    {!! HTML::script('js/jquery.dataTables.js') !!}
    {!! HTML::script('js/dataTables.tableTools.js') !!}
    {!! HTML::style('css/jquery.dataTables.css') !!}
    {!! HTML::style('css/dataTables.tableTools.css') !!}
    <script type="text/javascript">
        $(document).ready(function() {
            $('#breakTimeLogTable').DataTable({
                dom: 'T<"clear">lfrtip',
                pageLength: 40
            });
        });
    </script>
@endsection
