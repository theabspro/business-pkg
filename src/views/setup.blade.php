@if(config('business-pkg.DEV'))
    <?php $business_pkg_prefix = '/packages/abs/business-pkg/src';?>
@else
    <?php $business_pkg_prefix = '';?>
@endif

<script type="text/javascript">
app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    when('/business-pkg/lob/list', {
        template: '<lob-list></lob-list>',
        title: 'LOBs',
    }).
    when('/business-pkg/lob/add', {
        template: '<lob-form></lob-form>',
        title: 'Add LOB',
    }).
    when('/business-pkg/lob/edit/:id', {
        template: '<lob-form></lob-form>',
        title: 'Edit LOB',
    }).

    when('/business-pkg/sbu/list', {
        template: '<sbu-list></sbu-list>',
        title: 'SBUs',
    }).
    when('/business-pkg/sbu/add', {
        template: '<sbu-form></sbu-form>',
        title: 'Add SBU',
    }).
    when('/business-pkg/sbu/edit/:id', {
        template: '<sbu-form></sbu-form>',
        title: 'Edit SBU',
    });
}]);


    var lob_list_template_url = "{{URL::asset($business_pkg_prefix.'/public/themes/'.$theme.'/lob/list.html')}}";
    var lob_get_form_data_url = "{{url('business-pkg/lob/get-form-data/')}}";
    var lob_form_template_url = "{{URL::asset($business_pkg_prefix.'/public/themes/'.$theme.'/lob/form.html')}}";
</script>
<script type="text/javascript" src="{{URL::asset($business_pkg_prefix.'/public/themes/'.$theme.'/lob/controller.js?v=2')}}"></script>


<script type="text/javascript">
    var sbu_list_template_url = "{{URL::asset($business_pkg_prefix.'/public/themes/'.$theme.'/sbu/list.html')}}";
    var sbu_get_form_data_url = "{{url('business-pkg/sbu/get-form-data/')}}";
    var sbu_form_template_url = "{{URL::asset($business_pkg_prefix.'/public/themes/'.$theme.'/sbu/form.html')}}";
</script>
<script type="text/javascript" src="{{URL::asset($business_pkg_prefix.'/public/themes/'.$theme.'/sbu/controller.js?v=2')}}"></script>
