app.component('lobList', {
    templateUrl: lob_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location, $mdSelect) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.add_permission = self.hasPermission('add-lob');
        var table_scroll;
        table_scroll = $('.page-main-content').height() - 37;
        var dataTable = $('#lobs_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                // "search": "",
                // "searchPlaceholder": "Search",
                "lengthMenu": "Rows _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            pageLength: 10,
            processing: true,
            stateSaveCallback: function(settings, data) {
                localStorage.setItem('CDataTables_' + settings.sInstance, JSON.stringify(data));
            },
            stateLoadCallback: function(settings) {
                var state_save_val = JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                if (state_save_val) {
                    $('#search_lob').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            // ordering: false,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getLobPkgList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.lob_name = $('#lob_name').val();
                    d.status = $('#status').val();
                },
            },

            columns: [
                { data: 'action', class: 'action', searchable: false },
                { data: 'name', name: 'lobs.name' },
                { data: 'sbu_count', name: 'sbu_count', searchable: false },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            $('#search_lob').val('');
            $('#lobs_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#lobs_list').DataTable().ajax.reload();
        });

        var dataTables = $('#lobs_list').dataTable();
        $("#search_lob").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteLob = function($id) {
            $('#lob_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#lob_id').val();
            $http.get(
                laravel_routes['deleteLobPkg'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: response.data.message,
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 3000);
                    $('#lobs_list').DataTable().ajax.reload();
                    $scope.$apply();
                } else {
                    $noty = new Noty({
                        type: 'error',
                        layout: 'topRight',
                        text: response.data.errors,
                    }).show();
                }
            });
        }

        //FOR FILTER
        self.status = [
            { id: '', name: 'Select Status' },
            { id: '1', name: 'Active' },
            { id: '0', name: 'Inactive' },
        ];
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        $('#lob_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.onSelectedStatus = function(val) {
            $("#status").val(val);
            dataTables.fnFilter();
        }
        $scope.reset_filter = function() {
            $("#lob_name").val('');
            $("#status").val('');
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('lobForm', {
    templateUrl: lob_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getLobPkgFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id
                }
            }
        ).then(function(response) {
            console.log(response.data);
            self.lob = response.data.lob;
            // self.sbu = response.data.sbus;
            self.action = response.data.action;
            self.sbu_removal_ids = [];
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.lob.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });
        
        $("input:text:visible:first").focus();
        /* Tab Funtion */
        $('.btn-nxt').on("click", function() {
            $('.cndn-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.cndn-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });

        self.addNewSbu = function() {
            self.lob.sbus.push({
                id: '',
                company_id:'',
                name: '',
                switch_value: 'Active',
            });
        }

        self.removeSbu = function(index, sbu_id) {
            if(sbu_id) {
                self.sbu_removal_ids.push(sbu_id);
                $('#sbu_removal_ids').val(JSON.stringify(self.sbu_removal_ids));
            }
            self.lob.sbus.splice(index, 1);
        }

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs'
                }).show();
                // setTimeout(function() {
                //     $noty.close();
                // }, 3000)
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveLobPkg'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: res.message,
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 3000);
                            $('#submit').button('reset');
                            $location.path('/business-pkg/lob/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('#submit').prop('disabled', 'disabled');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                $noty = new Noty({
                                    type: 'error',
                                    layout: 'topRight',
                                    text: errors
                                }).show();
                                $('#submit').button('reset');
                                // setTimeout(function() {
                                //     $noty.close();
                                // }, 3000);
                            } else {
                                $('#submit').button('reset');
                                $location.path('/business-pkg/lob/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        $noty = new Noty({
                            type: 'error',
                            layout: 'topRight',
                            text: 'Something went wrong at server',
                        }).show();
                        // setTimeout(function() {
                        //     $noty.close();
                        // }, 3000);
                    });
            }
        });
    }
});