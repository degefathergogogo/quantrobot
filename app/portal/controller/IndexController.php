<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use Think\Db;

class IndexController extends HomeBaseController
{
    public function index()
    {
    	die();
        return $this->fetch(':index');
    }

    public function sendEnvelope()
    {
    	$id = $this->request->param('red_envelope_id');
    	$key = $this->request->param('red_envelope_key');
        $code = $this->request->param('invitation_code');
        $red_envelope = Db::name("UserRedEnvelope")->where(['id'=>$id])->field("id,coin_symbol,total_amount,total_num,nickname,wish")->find();
        $this->assign('red_envelope',$red_envelope);
    	$this->assign('id',$id);
    	$this->assign('key',$key);
        $this->assign('code',$code);
        return $this->fetch(':SendEnvelope');
    }
}
