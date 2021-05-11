<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use think\Db;
use think\Request;
/**
 * Class CronController 任务日志
 * @package app\admin\controller
 */
class CronController extends AdminBaseController
{

    public function balance(){
        $status = [
            "-1" => "<font color='#ff0000'>处理失败</font>",
            "0"  => "未处理",
            "1"  => "<font color='#008B45'>处理成功</font>",
        ];  
        $size=30;
        $data=  Db::name('Cron')
        ->order('id desc')//schedule_time
        ->where('task_name',"update_wallet_balance")//task_name
        ->paginate($size , false, [  'query' =>request()->param()  ]    );
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('status',$status );
        return $this->fetch();
    }

    public function notify(){

        $status = [
            "-1" => "<font color='#ff0000'>处理失败</font>",
            "0"  => "未处理",
            "1"  => "<font color='#008B45'>处理成功</font>",
        ];        
        $size=30;
        $data=  Db::name('Cron')
        ->order('id desc')//schedule_time
        ->where('task_name',"notify_url")//task_name
        ->paginate($size , false, [  'query' =>request()->param()  ]    );
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('status',$status );
        return $this->fetch();
    }
  
    public function collect(){

        $status = [
            "-1" => "<font color='#ff0000'>处理失败</font>",
            "0"  => "未处理",
            "1"  => "<font color='#008B45'>处理成功</font>",
        ];        
        $size=30;
        $data=  Db::name('Cron')
        ->order('id desc')//schedule_time
        ->where('task_name',"collect")//task_name
        ->paginate($size , false, [  'query' =>request()->param()  ]    );
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('status',$status );
        return $this->fetch();
    }
     
}