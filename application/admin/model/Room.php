<?php

namespace app\admin\model;

use think\Model;

class Room extends Model
{
    // 表名
    protected $name = 'room';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'status_text',
    ];
    
    public function getStatusList()
    {
        return ['0' => __('Status 0'),'1' => __('Status 1'),'2' => __('Status 2'),'3' => __('Status 3'),'4' => __('Status 4')];
    } 
    public function getStatusTextAttr($value, $data){
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    public function getTypeOne(){
         return ['1' => __('斗地主'),'2' => __('炸金花'),'3' => __('牛牛'),'4' => __('五子棋'),'5' => __('自由拼牌')];

    }
    public function getTypeTwo(){
         return ['1' => __('元宝'),'2' => __('金币'),'3' => __('房卡')];

    }






}
