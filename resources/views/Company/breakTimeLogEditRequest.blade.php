@extends('Company.CompanyLayout')
@section('content')
    <div>
        <ul class="breadcrumb">
            <li>
                <a href="{!! URL::to('company') !!}">Home</a> <span class="divider">/</span>
            </li>
            <li>
                <a href="{!! URL::to('company/break-time-log') !!}?s_date=<?php echo \Carbon\Carbon::parse($log->break_start)->toDateString() ?>&e_date=<?php echo \Carbon\Carbon::parse($log->break_end)->toDateString() ?>&id=<?php echo $log->user_id ?>">Break Log</a>
            </li>
        </ul>
    </div>
    <div>
        <div class="row-fluid sortable">
            <div class="box span12">
                <div class="box-header well" data-original-title>
                    <h2><i class="icon-edit"></i> Edit Attendance Log</h2>

                </div>
                <div class="box-content">
                    {!! Form::open(array('id' => 'notice', 'accept-charset' => 'utf-8', 'class' => 'form-horizontal','url' => url('company/break-log-time-edit-request', $log->id), 'autocomplete'=>'off')) !!}
                    <fieldset>
                        <div class="control-group">
                            <label class="control-label" for="from">From date</label>
                            <div class="controls">
                                <input type="text" id="from" required class="input-xlarge" name="from" placeholder="From Date"
                                       value="{!! $log->break_start !!}" >
                                @if ($errors->has('from'))
                                    <span class="help-inline text-danger">{{ $errors->first('from') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="from">To date</label>
                            <div class="controls">
                                <input type="text" id="to" required class="input-xlarge" name="to" placeholder="To Date"
                                       value="{!! $log->break_end !!}" >
                                @if ($errors->has('to'))
                                    <span class="help-inline text-danger">{{ $errors->first('to') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-actions">
                            <button  type="submit"  class="btn btn-success">Update</button>
                            <button type="reset" class="btn">Cancel</button>
                        </div>
                    </fieldset>
                    </form>

                </div>
            </div><!--/span-->

        </div>
    </div>
@endsection

@section('jsBottom')

    <script type="text/javascript">
        $(document).ready(function(){
            $( "#from" ).datetimepicker({
                dateFormat:'yy-mm-dd',
                timeFormat: 'HH:mm:ss',
                changeMonth: true,
                numberOfMonths: 1,
            });
            $( "#to" ).datetimepicker({
                dateFormat:'yy-mm-dd',
                timeFormat: 'HH:mm:ss',
                changeMonth: true,
                numberOfMonths: 1,
            });
        })
    </script>
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
@endsection