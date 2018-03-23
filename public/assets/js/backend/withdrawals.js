define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'withdrawals/index',
                    add_url: 'withdrawals/add',
                   // edit_url: 'withdrawals/edit',
                    //del_url: 'withdrawals/del',
                    multi_url: 'withdrawals/multi',
                    table: 'withdrawals',
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
                        {field: 'pic', title: __('头像'), formatter: Controller.api.formatter.thumb,operate: false},
                        {field: 'nickname', title: __('用户'),operate: false},
                        {field: 'uselessid', title: __('游戏ID')},
                        {field: 'money', title: __('Money')},
                        {field: 'truename', title: __('Truename')},
                        {field: 'phone', title: __('手机号')},
                        {field: 'types_text', title: __('Types'),operate: false},
                        {field: 'number', title: __('Number')},
                        {field: 'bank', title: __('Bank')},
                        {field: 'remark', title: __('Remark')},
                        {field: 'ctime', title: __('Ctime')},
                        {field: 'state_text', title: __('State'), formatter: Controller.api.formatter.statestatus,operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Controller.api.formatter.operate}
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
                        return '<a href="javascript:;">' + __('None') + '</a>';
                    }
                },
                statestatus: function (value, row, index) {

                    if(row.state==1){
                        var btn="success";
                        var val=1;
                        var status_name=__('State 1');
                    }else if (row.state==2) {
                        var btn="info";
                        var val=2;
                        var status_name=__('State 2');
                    }
                    return "<a href='javascript:;' class='btn btn-" + btn + " btn-xs btn-change' data-id='"
                            + row.id + "' data-params='order_status_text=" + val + "'>" + status_name + "</a>";
                },
                operate: function (value, row, index) {
                    var table = this.table;
                    // 操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    // 默认按钮组
                    var buttons = $.extend([], this.buttons || []);
                    if(row.state==1){
                            buttons.push({
                            name: 'ajax',
                            title: __('设为提现'),
                            text: '设为提现',
                            icon: 'fa fa-list',
                            icon: 'fa fa-magic',
                            classname: 'btn btn-xs btn-primary btn-ajax',
                            url: 'withdrawals/is_state',
                            success: function (data, ret) {
                                Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                //如果需要阻止成功提示，则必须使用return false;
                                //return false;
                            }, error: function (data, ret) {
                                Layer.alert(ret.msg);
                                return false;
                            },

                        });
                    }
                   // buttons.push({name: 'dragsort', icon: 'fa fa-arrows', classname: 'btn btn-xs btn-primary btn-dragsort'});
                    var html = [];
                    $.each(buttons, function (i, j) {
                        var attr = table.data("operate-" + j.name);
                        //j.name === 'dragsort' && typeof row[Table.config.dragsortfield] == 'undefined')
                        if ((typeof attr === 'undefined' || attr) || (typeof row[Table.config.dragsortfield] == 'undefined')) {
                            if (['add', 'edit', 'del', 'multi'].indexOf(j.name) > -1 && !options.extend[j.name + "_url"]) {
                                return true;
                            }
                            //自动加上ids
                            j.url = j.url ? j.url + (j.url.match(/(\?|&)+/) ? "&ids=" : "/ids/") + row[options.pk] : '';
                            url = j.url ? Fast.api.fixurl(j.url) : 'javascript:;';
                            classname = j.classname ? j.classname : 'btn-primary btn-' + name + 'one';
                            icon = j.icon ? j.icon : '';
                            text = j.text ? j.text : '';
                            title = j.title ? j.title : text;
                            html.push('<a href="' + url + '" class="' + classname + '" title="' + title + '"><i class="' + icon + '"></i>' + (text ? ' ' + text : '') + '</a>');
                        }
                    });
                    return html.join(' ');
                }
            }
        }
    };
    return Controller;
});