@extends('Company.CompanyLayout')
@section('content')
    <div>
        <ul class="breadcrumb">
            <li>
                <a href="{!! URL::to('company') !!}">Home</a> <span class="divider">/</span>
            </li>
            <li>
                <a href="{!! URL::to('company/leave-report') !!}">Leave Report</a>
            </li>
        </ul>
    </div>
    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-user"></i> Leave Report</h2>
            </div>
            <div class="box-content">
                <table id="leaveReportTable" class="table table-striped table-bordered bootstrap-datatable">
                    <thead>
                    <tr>
                        <th>Username</th>
                        <th>Leave Category</th>
                        <th>Yearly Budget</th>
                        <th>Approved</th>
                        <th>Remaining</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($leaveReports as $report)
                        @foreach($report['leave_details'] as $details)
                            <tr>
                                <td class="username">{{ $report['username'] }}</td>
                                <td>{{ $details['category'] }}</td>
                                <td>{{ $details['budget'] }}</td>
                                <td>{{ $details['approved'] }}</td>
                                <td>{{ $details['remaining'] }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div><!--/span-->
    </div>
@endsection
@section('jsBottom')
    {!! HTML::script('js/charisma/js/jquery.dataTables.min.js') !!}
    <script>
        $(document).ready(function() {
            var table = $('#leaveReportTable').DataTable({
                dom: 'T<"clear">lfrtip',
                pageLength: 40
            });

            $('#search').on('keyup', function() {
                table.column(0).search(this.value).draw();
            });
        });
    </script>
@endsection
