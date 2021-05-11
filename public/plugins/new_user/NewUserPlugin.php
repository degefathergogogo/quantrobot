<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\new_user;
use cmf\lib\Plugin;
use think\Db;
/**
 * WalletPlugin
 */
class NewUserPlugin extends Plugin
{

    public $info = [
        'name'        => 'NewUser',
        'title'       => '新用户注册后事件插件',
        'description' => '新用户注册后事件插件',
        'status'      => 1,
        'author'      => 'Kinlink',
        'version'     => '1.0'
    ];

    public $has_admin = 0;//插件是否有后台管理界面

    public function install() //安装方法必须实现
    {
        return true;//安装成功返回true，失败false
    }

    public function uninstall() //卸载方法必须实现
    {
        return true;//卸载成功返回true，失败false
    }

    //实现的generate_wallet钩子方法
    public function afterRegister($param)
    {
        $uid = $param['uid'];//用户ID

        $task_data_all = [];

        $p['uid'] = $uid ;

        $task_data['params'] = json_encode($p);
        $task_data['task_name'] = "init_user_wallet";
        $task_data['uid'] = $uid;
        $task_data['schedule_time'] =0;

        $task_data_all[] = $task_data;
        
        //var_dump($task_data_all);

        Db::name('cron')->insertAll($task_data_all);                           

        $result = [
            'error'     => 0,
            'message' => '',
        ];
        return $result;
    }

   
}