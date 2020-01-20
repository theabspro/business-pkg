@if(config('custom.PKG_DEV'))
    <?php $business_pkg_prefix = '/packages/abs/business-pkg/src';?>
@else
    <?php $business_pkg_prefix = '';?>
@endif

<script type="text/javascript">
    var lob_list_template_url = "{{URL::asset($business_pkg_prefix.'/public/angular/business-pkg/pages/lob/list.html')}}";
    var lob_get_form_data_url = "{{url('business-pkg/lob/get-form-data/')}}";
    var lob_form_template_url = "{{URL::asset($business_pkg_prefix.'/public/angular/business-pkg/pages/lob/form.html')}}";
    var lob_delete_data_url = "{{url('business-pkg/lob/delete/')}}";
</script>
<script type="text/javascript" src="{{URL::asset($business_pkg_prefix.'/public/angular/business-pkg/pages/lob/controller.js?v=2')}}"></script>


<script type="text/javascript">
    var sbu_list_template_url = "{{URL::asset($business_pkg_prefix.'/public/angular/business-pkg/pages/sbu/list.html')}}";
    var sbu_get_form_data_url = "{{url('business-pkg/sbu/get-form-data/')}}";
    var sbu_form_template_url = "{{URL::asset($business_pkg_prefix.'/public/angular/business-pkg/pages/sbu/form.html')}}";
    var sbu_delete_data_url = "{{url('business-pkg/sbu/delete/')}}";
</script>
<script type="text/javascript" src="{{URL::asset($business_pkg_prefix.'/public/angular/business-pkg/pages/sbu/controller.js?v=2')}}"></script>
