<?php

namespace app\admin\model;

use think\Model;

class Configs extends Model
{
    // 表名
    protected $name = 'config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];
    

    







}
