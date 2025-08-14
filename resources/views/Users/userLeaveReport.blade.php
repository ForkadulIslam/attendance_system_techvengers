@extends('Users.UserLayout')
@section('content')
    <div>
        <ul class="breadcrumb">
            <li>
                <a href="{!! URL::to('user') !!}">Home</a> <span class="divider">/</span>
            </li>
            <li>
                <a href="{!! URL::to('user/leave-report') !!}">Leave Report</a>
            </li>
        </ul>
    </div>
    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-user"></i> Leave Report</h2>
            </div>
            <div class="box-content">
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
            </div>
        </div><!--/span-->
    </div>
@endsection
