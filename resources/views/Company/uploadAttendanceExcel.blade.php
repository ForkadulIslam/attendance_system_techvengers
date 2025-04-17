@extends('Company.CompanyLayout')
@section('content')

    <div>
        <ul class="breadcrumb">
            <li>
                <a href="{!! URL::to('user') !!}">Home</a> <span class="divider">/</span>
            </li>
            <li>
                <a href=''{!! URL::to("company/report-summery") !!}'>Summery Report</a>
            </li>
        </ul>
    </div>

    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header well">
                <h2><i class="icon-user"></i> UPLOAD CSV</h2>
            </div>
            <div class="box-content">
                <form action="{{ url('company/upload-attendance-log') }}" method="POST" enctype="multipart/form-data">
                    {!! csrf_field() !!}
                    <div class="control-group">
                        <label class="control-label">Select CSV File</label>
                        <div class="controls">
                            <input type="file" name="attendance_log" accept=".csv" required>
                        </div>
                    </div>
                    <br>
                    <button type="submit" class="btn btn-primary">Upload & Import</button>
                </form>
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
            $('#example').DataTable({
                dom: 'T<"clear">lfrtip'
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
