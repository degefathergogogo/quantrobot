<?php
namespace app\user\controller;
use cmf\controller\AdminBaseController;
use app\admin\model\HookModel;
use app\admin\model\PluginModel;
use app\admin\model\HookPluginModel;
use think\Db;

/**
 * Class UserAuthController 用户实名制审核
 * @package app\admin\controller
 */
class UserAuthController extends AdminBaseController
{
   
    public function index(){
        $server= $_SERVER['HTTP_HOST'];
        $data =  Db::name("auth")->where('status', 2 )->select();
        $count= count($data);
        $this->assign('server', $server );
        $this->assign('data', $data );
        $this->assign('count',$count);
        return $this->fetch();
    }
    public function ajaxupdate(){
        if($_POST){
            $id=$_POST['ids'];
            $type=$_POST['types'];
            if( $type==1){
                // 审核通过且修改用户表的状态为3   
                $data= Db::name("auth")->where('id', $id )->find();
                $u_id= $data['user_id'];
                Db::name("auth")->where('id', $id )->update([
                    'status' => 1
                ]);
                Db::name("user")->where('id', $u_id )->update([
                    'auth_id' => 3
                ]);
                echo "操作成功，已通过该申请！";
            }
            else if( $type==3){
                Db::name("auth")->where('id', $id )->update([
                    'status' => 3,
                    'refuse_at'=>time()
                ]);
                echo "操作成功，已拒绝该申请！";
            }
        }else{
            echo "请求类型有误！";
        }
    }



    
}