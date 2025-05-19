@extends('Company.CompanyLayout')

@section('content')
    <div>
        <ul class="breadcrumb">
            <li><a href="{{ url('company') }}">Home</a> <span class="divider">/</span></li>
            <li><a href="{!! url('company/screenshot-list-request') !!}">User Screenshots</a></li>
        </ul>
    </div>

    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header well">
                <h2><i class="icon-picture"></i> Screenshots</h2>
            </div>

            <div class="box-content">

                {{-- ───────────── Search form ───────────── --}}
                {!! Form::open([
                        'url'      => 'company/screenshots-by-user-and-date',
                        'method'   => 'post',
                        'id'       => 'shotForm',
                        'class'    => 'form-inline',
                        'autocomplete' => 'off'
                ]) !!}

                <?php
                $selectedUser = old('user_id', request('user_id'));
                $selectedDate = old('date', request('date', \Carbon\Carbon::now()->toDateString()));
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

                <input type="text" id="shotDate" name="date"
                       class="input-small"
                       placeholder="YYYY-MM-DD"
                       required
                       value="{{ $selectedDate }}">

                <button class="btn btn-primary" type="submit">Search</button>
                {!! Form::close() !!}

                {{-- ───────────── Results ───────────── --}}
                @if(isset($shots))
                    <hr>

                    @if(count($shots))
                        <table id="example" class="table table-striped table-bordered bootstrap-datatable datatable display" style="width:100%">
                            <thead>
                            <tr>
                                <th>Thumbnail</th>
                                <th>Public ID</th>
                                <th>Size (KB)</th>
                                <th>Capture</th>
                                <th>Date</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($shots as $s)
                                <tr>
                                    <td>
                                        <a href="{{ $s['secure_url'] }}" target="_blank">
                                            <img src="{{ $s['secure_url'] }}"
                                                 alt="shot"
                                                 style="max-height:70px">
                                        </a>
                                    </td>
                                    <td>{{ $s['public_id'] }}</td>
                                    <td>{{ round(($s['bytes'] ?? 0)/1024, 1) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($s['created_at'])->diffForHumans() }}</td>
                                    <td>{!! \Carbon\Carbon::parse($s['created_at'])->toDateTimeString() !!}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-info" style="margin-top:15px">
                            No screenshots found for this user / date.
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection



@section('jsBottom')
    {!! HTML::script('js/jquery.dataTables.js') !!}
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="//code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script>
        $(function () {
            $('#shotDate').datepicker({ dateFormat:'yy-mm-dd' });

            // turn the results table into a DataTable if it exists
            $('#example').DataTable( {
                dom: 'T<"clear">lfrtip',
                order: [[3,'desc']],
                pageLength: 100
            } );
        });
    </script>
@endsection
