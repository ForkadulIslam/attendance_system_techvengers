@extends('Users/UserLayout')
@section('content')
    <div>
        <ul class="breadcrumb">
            <li>
                <a href="{!! URL::to('user') !!}">Home</a> <span class="divider">/</span>
            </li>
            <li>
                <a href="{!! URL::to('user/apply-leave') !!}">Apply Leave</a>
            </li>
        </ul>
    </div>
    <?php if(!empty($leaveBudget)){
    foreach($leaveBudget as $category):
    if($category['categoryBudget']>0){
    ?>
    <div class="alert alert-info">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>You can be apply <?php echo $category['category'] ?>'s <?php echo $category['categoryBudget'] ?> days leave</strong>
    </div>
    <?php
    };
    endforeach;
    }
    ?>
    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header well" data-original-title>
                <h2><i class="icon-edit"></i> Apply For Leave</h2>

            </div>
            <div class="box-content">
                {!! Form::open(array('role' => 'form', 'method' => 'post', 'id' => 'leave', 'accept-charset' => 'utf-8', 'class' => 'form-horizontal', 'url' => 'user/leave-apply')) !!}
                    <fieldset>
                        <div class="control-group" id="app">
                            <label class="control-label" for="date">Select a date</label>
                            <div class="controls">
                                <input type="text" required readonly class="input-xlarge datepicker"  name="leave_date[]" placeholder="leave_date">
                            </div>
                        </div>
                        <div id="more" >
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="date"></label>
                            <div class="controls">
                                <button type="button" id="add" class="btn btn-default">Add More</button>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="description">Leave category</label>
                            <div class="controls">
                                <select required name="leave_category_id" id="leave_category_id">
                                    <option value="">Select a category</option>
                                    <?php foreach($leaveBudget as $category):?>
                                    <?php if($category['categoryBudget']>0){?>
                                    <option value="<?php echo $category['id']?>"><?php echo $category['category']?></option>
                                    <?php } endforeach;?>
                                    <option value="25">Authorize Leave</option>
                                </select>
                            </div>
                        </div>
                        <div class="control-group" id="half_day_option_field" style="display: none">
                            <label class="control-label" for="is_half_day">Half day</label>
                            <div class="controls">
                                <input type="checkbox" id="is_half_day" name="is_half_day" value="">
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="description">Description</label>
                            <div class="controls">
                                <textarea required name="leave_cause" id="description" placeholder="Describe Your Leave Cause"  class="form-control" rows="7" cols="10"></textarea>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">Save</button>
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
        $(document).ready(function() {
            $("#leave").submit(function(event) {
                event.preventDefault();
                var values = $("#leave").serialize();
                $.ajax({
                    url: "{!! URL::to('user/leave-apply') !!}",
                    type: "POST",
                    data: values,
                    cache: false,
                    beforeSend: function(){
                        $('#loader').html('<img src="{{ URL::to('images/loader_gif.gif') }}" style="height: 100px;margin-left: 100px;">');
                    },
                    success: function(data) {
                        $('#loader').hide();
                        if(data == 'true') {
                            $("#leave")[0].reset();
                            $.pnotify({
                                title: 'Success',
                                text: 'Leave Successfully Apply',
                                type: 'success',
                                delay: 3000
                            });
                        }else{
                            $.pnotify({
                                title: 'Error',
                                text: data,
                                type: 'error',
                                delay: 3000
                            });
                        }
                    }
                });
            });
        });
    </script>
    <script type="text/javascript">
        $(document).ready(function(){
            $("#add").click(function(event) {
                event.preventDefault();
                $("#more").append('<div class="control-group"><label class="control-label">Select another date</label><div class="controls"><input required readonly type="text" readonly class="input-xlarge datepicker"  name="leave_date[]" placeholder="leave_date">  <button class="remove">x</button></div></div>');
                $(".datepicker").datepicker({
                    changeMonth: true,
                    changeYear: true,
                    dateFormat:'yy-mm-dd'
                });
            });


            $("#leave_category_id").on('change', function(){
                let selectedCategory = $(this).val();
                if(selectedCategory != 25){
                    $("#half_day_option_field").show();
                }else{
                    $("#half_day_option_field").hide();
                    $("#is_half_day").prop('checked', false);
                }
            })
        });
        $(document).on('click', ".remove", function () {
            $(this).parent().parent().closest(".control-group").html('');
        });
    </script>
@endsection