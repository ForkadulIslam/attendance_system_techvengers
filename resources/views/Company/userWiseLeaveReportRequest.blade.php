@extends('Company.CompanyLayout')

@section('content')
    <div class="container">
        <h3>User wise leave report</h3>
        <?php

        ?>

        {{-- ───────────── Search form ───────────── --}}
        {!! Form::open([
                'url'      => 'company/leave-report-by-user',
                'method'   => 'get',
                'id'       => 'shotForm',
                'class'    => 'form-inline',
                'autocomplete' => 'off'
        ]) !!}

        <?php
        $selectedUser = old('user_id', request('user_id'));
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

        <button class="btn btn-primary" type="submit">Search</button>
        {!! Form::close() !!}



        <?php
            if(isset($leaveDetails)){
                ?>
        <table class="table table-striped table-bordered bootstrap-datatable datatable">
            <thead>
            <tr>
                <th>Leave Category</th>
                <th>Yearly Budget</th>
                <th>Approved</th>
                <th>Remaining</th>
            </tr>
            </thead>
            <tbody>
            @foreach($leaveDetails as $details)
                <tr>
                    <td>{{ $details['category'] }}</td>
                    <td>{{ $details['budget'] }}</td>
                    <td>{{ $details['approved'] }}</td>
                    <td>{{ $details['remaining'] }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <th>TOTAL</th>
                <th>{!! $total_budged !!}</th>
                <th>{!! $total_approved !!}</th>
                <th>{!! $total_remaining !!}</th>
            </tr>
            </tfoot>
        </table>
<?php
            }
        ?>


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
