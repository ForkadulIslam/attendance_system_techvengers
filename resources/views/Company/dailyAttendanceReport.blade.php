@extends('Company.CompanyLayout')
@section('content')

<div>
    <ul class="breadcrumb">
        <li>
            <a href="{!! URL::to('company') !!}">Home</a> <span class="divider">/</span>
        </li>
        <li>
            <a href=''{!! URL::to("company/daily-attendance-report") !!}'>Daily Attendance Report</a>
        </li>

    </ul>
</div>
<div class="row-fluid sortable">
    <div class="box span12">
        <div class="box-header well" data-original-title>
            <h2><i class="icon-user"></i> Daily Attendance Report [<?php echo $date ?>]</h2>

        </div>
        <div class="box-content">
            <table id="example" class="display" cellspacing="0" width="100%">
                <thead>
                <tr>
                    <td>
                        Name
                    </td>
                    <td>
                        In Time
                    </td>
                    <td>
                        Out Time
                    </td>
                    <td>Working Hour</td>
                    <td>Break Time</td>
                    <td>
                        Active Hour
                    </td>
                </tr>
                </thead>
                <tbody>
                <?php foreach($reports as $report){
                    ?>
                    <tr>
                        <td>
                            <?php echo $report['username']?>
                        </td>
                        <td>
                            <?php
                            echo $report['first_login'];
                            ?>
                        </td>
                        <td>
                            <?php
                            echo $report['last_logout'];
                            ?>
                        </td>
                        <td data-order="{{ $report['totalSeconds'] }}">
                            <a href="{!! URL::to('company/attendance-log') !!}?s_date=<?php echo $date ?>&e_date=<?php echo $date ?>&id=<?php echo $report['user_id'] ?>">
                                <?php echo $report['workingTime']; ?>
                            </a>
                        </td>
                        <td data-order="{{ $report['totalBreakSeconds'] }}">
                            <a href="{!! URL::to('company/break-time-log') !!}?s_date=<?php echo $date ?>&e_date=<?php echo $date ?>&id=<?php echo $report['user_id'] ?>">
                                <?php echo $report['breakTime']; ?>
                            </a>
                        </td>

                        <td data-order="{{ $report['activeSeconds'] }}">
                            <?php
                            echo $report['activeTime']
                            ?>
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
            order: [[5, 'desc']],
            pageLength: 40
        } );
    } );
</script>
@endsection