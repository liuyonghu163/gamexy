define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var adminids=$("#adminids").val();
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                search: false,
                advancedSearch: true,
                pagination: true,
                extend: {
                    index_url: 'userer/index/'+adminids,
                    add_url: 'userer/add',
                    edit_url: 'userre/edit',
                    del_url: 'userer/del',
                    multi_url: 'userer/multi',
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
                        {checkbox: true},
                        {field: 'headimgurl', title: __('Headimgurl'), formatter: Controller.api.formatter.thumb,operate: false},
                        {field: 'nickname', title: __('Nickname')},
                        {field: 'uselessid', title: __('Uselessid')},
                        {field: 'agent_text', title: __('类型'),operate: false},
                        {field: 'ctime', title: __('绑定时间'),operate: false},
                        {field: 'commissiontxt', title: __('推广金额'),formatter: Controller.api.formatter.commissiotest,operate: false},
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
                    if (row.headimgurl) {
                        return '<a href="' + row.headimgurl + '" target="_blank"><img src="' + row.headimgurl + '" alt="" style="max-height:50px;max-width:50px"></a>';
                    } else {
                        return '<a href="' + row.headimgurl + '" target="_blank">' + __('None') + '</a>';
                    }
                },
                 commissiotest: function (value, row, index) {
                    return row.commissionOne+"<br/>"+"+"+row.commissionTwo;
                },
                operate: function (value, row, index) {
                    var table = this.table;
                    // 操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    // 默认按钮组
                    var buttons = $.extend([], this.buttons || []);
                    if(row.totalnum>=10){
                        if(row.agent==0){
                                buttons.push({
                                name: 'ajax',
                                title: __('设为代理'),
                                text: '设为代理',
                                icon: 'fa fa-list',
                                icon: 'fa fa-magic',
                                classname: 'btn btn-xs btn-primary btn-ajax',
                                url: 'user/is_agent',
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