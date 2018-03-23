define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'room/index',
                    add_url: 'room/add',
                    edit_url: 'room/edit',
                    del_url: 'room/del',
                    multi_url: 'room/multi',
                    table: 'room',
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
                        {field: 'room_num', title: __('Room_num')},
                        {field: 'nickname', title: '创建人',operate: false},
                        {field: 'name', title: __('Name')},
                        {field: 'type_text', title: __('Type')},
                        {field: 'seat', title: __('Seat')},
                        {field: 'bet', title: __('Bet')},
                        {field: 'create_time', title: __('Create_time')},
                        {field: 'status_text', title: __('Status'), formatter: Table.api.formatter.status,operate: false},
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
            }
        }
    };
    return Controller;
});