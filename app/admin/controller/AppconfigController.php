<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use think\Db;

class AppconfigController extends AdminBaseController{
	
	
	function index(){
         
        $check = array();
        $sys_config = cmf_get_option("sys_config");
	    if(empty($sys_config['turnout_audit1'])) 
	        $check['audit1_check_0']  = 'selected';
	    else
	        $check['audit1_check_0']  = '';

	    if(!empty($sys_config['turnout_audit1'])) 
	        $check['audit1_check_1']  = 'selected';
	    else
	        $check['audit1_check_1']  = '';

	    if(empty($sys_config['turnout_audit2'])) 
	        $check['audit2_check_0']  = 'selected';
	    else
	        $check['audit2_check_0']  = '';

	    if(!empty($sys_config['turnout_audit2'])) 
	        $check['audit2_check_1']  = 'selected';
	    else
	        $check['audit2_check_1']  = '';

	    if(empty($sys_config['huizong_auto'])) 
	        $check['auto_check_0']  = 'selected';
	    else
	        $check['auto_check_0']  = '';

	    if(!empty($sys_config['huizong_auto'])) 
	        $check['auto_check_1']  = 'selected';
	    else
	        $check['auto_check_1']  = '';

	    $this->assign("sys_config",$sys_config);
	    $this->assign('check', $check);
        return $this->fetch();
	}
	
	function indexPost(){
        $post = $this->request->post("sys_config/a");

        cmf_set_option('sys_config', $post);

        $this->success("保存成功！");
	}
		
}