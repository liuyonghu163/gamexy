define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var adminids=$("#adminids").val();
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                advancedSearch: true,
                mobileResponsive:false,
                searchFormVisible:true,
                cardView:false,
                extend: {
                    index_url: 'gener/index/'+adminids,
                    add_url: 'gener/add',
                    edit_url: 'gener/edit',
                    del_url: 'gener/del',
                    multi_url: 'gener/multi',
                    table: 'user',
                }
            });
            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'ctime', title: __('日期'), operate: 'BETWEEN', type: 'datetime', addclass: 'datetimepicker', data: 'data-date-format="YYYY-MM-DD"',style:'width:88px'},
                        {field: 'totalnum', title: __('区域收益'),operate: false},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                thumb: function (value, row, index) {
                    if (row.headimgurl) {
                        return '<a href="' + row.headimgurl + '" target="_blank"><img src="' + row.headimgurl + '" alt="" style="max-height:50px;max-width:50px"></a>';
                    } else {
                        return '<a href="' + row.headimgurl + '" target="_blank">' + __('None') + '</a>';
                    }
                },
                 commissiotest: function (value, row, index) {
                    return row.commissionOne+"<br/>"+"+"+row.commissionTwo;
                },
               
            }
        }
    };
    return Controller;
});