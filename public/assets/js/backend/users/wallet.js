define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'users/wallet/index',
                   // // add_url: 'users/wallet/add',
                     edit_url: 'users/wallet/edit',
                   //  //del_url: 'users/wallet/del',
                    multi_url: 'users/wallet/multi',
                    table: 'user_wallet',
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
                        {checkbox: true},
                        {field: 'pic', title: '头像', formatter: Controller.api.formatter.thumb,operate: false},
                        {field: 'nickname', title: '昵称',},
                        {field: 'uselessid', title: __('Uid')},
                        {field: 'ingot', title: __('金币'),operate: false},
                        
                        {field: 'ctime', title: __('Ctime'), operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
                    if (row.pic) {
                        return '<a href="' + row.pic + '" target="_blank"><img src="' + row.pic + '" alt="" style="max-height:50px;max-width:50px"></a>';
                    } else {
                        return '<a href="' + row.pic + '" target="_blank">' + __('None') + '</a>';
                    }
                }
            }
        }
    };
    return Controller;
});