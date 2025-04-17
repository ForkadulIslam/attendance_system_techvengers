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
                <h2><i class="icon-edit"></i> Daily attendance report</h2>
            </div>
            <div class="box-content">
                {!! Form::open(array('role' => 'form', 'accept-charset' => 'utf-8', 'method' => 'post', 'class' => 'form-horizontal', 'url' => 'company/daily-attendance-report', 'autocomplete'=>'off')) !!}
                <fieldset>
                    <div class="control-group">
                        <label class="control-label" for="company_name">Date</label>
                        <div class="controls">
                            <input type="text" id="from" required class="input-xlarge" name="date" placeholder="Date">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">Report</button>
                        <button type="reset" class="btn">Cancel</button>
                    </div>
                    <div id="loader">
                    </div>
                </fieldset>
                </form>
            </div>
        </div><!--/span-->
    </div>
    <script type="text/javascript">
        $(function() {
            $( "#from" ).datepicker({
                dateFormat:'yy-mm-dd',
                //defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 1,
            });
        });
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