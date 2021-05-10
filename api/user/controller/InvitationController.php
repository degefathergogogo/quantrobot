<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------

namespace api\user\controller;

use api\wallet\service\ColumnName;
use cmf\controller\RestBaseController;
use think\Validate;
use think\Db;

class InvitationController extends RestBaseController
{
    protected $reward_rule;
    protected $detial_types = [];
    public function _initialize(){
        $this->reward_rule = Db::name("UserInviteReward")->where(['status'=>1])->field('type,reg_parent_reward,reg_offspring_reward,deposit_parent_reward,deposit_offspring_reward')->select()->toArray();
        $this->reward_rule = array_column($this->reward_rule,NULL,'type');
        foreach (array_keys($this->reward_rule) as $k => $x){
            $this->detial_types[$x] = "invite_reward_{$x}_reg_parent_score_income";
        }
    }
    /**
     * 显示邀请信息
     */
    public function info()
    {
        $userId = $this->getUserId();
        $field = "";
        foreach ($this->detial_types as $k => $x){
            $field .= "CAST(SUM(if(detial_type='{$x}',`change`,0)) AS UNSIGNED) as {$k}_sum,COUNT(detial_type='{$x}' or null) as {$k}_count,";
        }
        $field = rtrim($field,",");
        $data = Db::name("BalanceLog")->where(['user_id'=>$userId,'balance_type'=>'score'])->field($field)->find();

        $total_sum = 0;
        $total_count = 0;
        foreach ($this->detial_types as $k => $x){
            $total_sum += $data["{$k}_sum"];
            $total_count += $data["{$k}_count"];
        }
        $total_other_sum = $total_sum - $data["1_sum"];
        $data['total_sum'] = $total_sum;
        $data['total_other_sum'] = $total_other_sum;
        $data['total_count'] = $total_count;
        $data['invite_1_reg_parent_reward'] = $this->reward_rule[1]['reg_parent_reward'];
        $data['invite_1_reg_offspring_reward'] = $this->reward_rule[1]['reg_offspring_reward'];
        $this->success('请求成功', ['data'=>$data,'reward_rule'=>array_values($this->reward_rule)]);
    }

    /**
     * 邀请列表
     */
    public function getList()
    {
        $limit_begin     = $this->request->param('limit_begin', 0, 'intval');
        $limit_num     = $this->request->param('limit_num', 20, 'intval');
        $userId = $this->getUserId();

        $where = [
            'a.user_id' => $userId,
            'a.balance_type' => 'score',
            'a.detial_type' => ['in',$this->detial_types],
        ];
        $model = Db::name("BalanceLog");
        $count = $model->alias('a')->where($where)->count();

        $data = $model->alias('a')->join('user b','a.detial = b.id','LEFT')->where($where)->field('b.id as user_id,b.mobile,a.change,a.detial_type,a.ctime')->limit($limit_begin.','.$limit_num)->order('a.id desc')->select()->toArray();
        if (!empty($data)){
            foreach ($data as &$x){
                $x['mobile'] = substr_replace($x['mobile'],'****',3,4);
                $x['change'] = sprintf('%d',$x['change']);
                $x['detial_type_msg'] = ColumnName::$db_balance_log['detial_type'][$x['detial_type']];
                $x['ctime'] = date("Y-m-d H:i",$x['ctime']);
            }
        }
        $this->success('请求成功', ['count'=>$count,'list'=>$data]);
    }
}
