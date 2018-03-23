<?php

namespace app\admin\model;

use think\Model;

class Userer extends Model
{
    // 表名
    protected $name = 'user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'source_text',
        'agent_text',
        'status_text',
    ];
    public function getSourceList()
    {
        return ['0' => __('Source 0'),'1' => __('Source 1')];
    } 
     public function getStatusList()
    {
        return ['0' => __('Status 0'),'1' => __('Status 1'),'2' => __('Status 2')];
    }  
    public function getAgentList()
    {
        return ['0' => __('Agent 0'),'1' => __('Agent 1'),'2' => __('Agent 2')];
    } 
    public function getSourceTextAttr($value, $data){
        $value = $value ? $value : $data['source'];
        $list = $this->getSourceList();
        return isset($list[$value]) ? $list[$value] : '';
    }
     
    public function getAgentTextAttr($value, $data){
        $value = $value ? $value : $data['agent'];
        $list = $this->getAgentList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    public function getStatusTextAttr($value, $data){
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }






}
