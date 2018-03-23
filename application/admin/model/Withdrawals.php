<?php

namespace app\admin\model;

use think\Model;

class Withdrawals extends Model
{
    // 表名
    protected $name = 'withdrawals';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'types_text',
        'state_text',
    ];
     public function getTypesList()
    {
        return ['1' => __('Types 1'),'2' => __('Types 2'),'3' => __('Types 3')];
    }  
     public function getStateList()
    {
        return ['1' => __('State 1'),'2' => __('State 2')];
    }  
    public function getTypesTextAttr($value, $data){
        $value = $value ? $value : $data['types'];
        $list = $this->getTypesList();
        return isset($list[$value]) ? $list[$value] : '';
    }
     public function getStateTextAttr($value, $data){
        $value = $value ? $value : $data['state'];
        $list = $this->getStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }







}
