<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\wallet;
use cmf\lib\Plugin;
use think\Db;
/**
 * WalletPlugin
 */
class WalletPlugin extends Plugin
{

    public $info = [
        'name'        => 'Wallet',
        'title'       => '钱包生成插件',
        'description' => '钱包生成插件',
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
    public function generateWallet($param)
    {
        $uid           = $param['uid'];//用户ID
        //$config        = $this->getConfig();
        //$default_coins = $config['default_coins'];
        //$coin_list =  preg_split("/[\s,]+/", $default_coins);
        $coin_list = array();
        $coin_data = Db::name('coin')
        ->field('coin_symbol')
        ->where('cloud_default', 1)
        ->where('cloud_status', 1)
        ->select()->toArray();
        foreach ($coin_data as $key => $value) {
            array_push($coin_list, $value['coin_symbol']);
        }

        $result  = false;
        
        foreach ($coin_list as $coin) {
           $wallet_count = Db::name('wallet')->where('uid', $uid)->where('type', 1)->where('coin_symbol', $coin)->count();
           if($wallet_count == 0){
                 $data['uid'] =  $uid ;
                 $data['coin_symbol'] =  $coin ;
                 $data['type'] =  1;
                 $data['add_time'] =  time();
                 Db::name('wallet')->insert($data);
                //增加生成地址任务
                $coin_info = Db::name('coin')->field('coin_type')->where('coin_symbol', $coin)->find(); 
                if(isset($coin_info['coin_type'])){
                    if($coin_info['coin_type']=='coin'){
                        $p['uid'] = $uid ;
                        $p['coin_symbol'] = $coin;
                        $task_data['params'] = json_encode($p);
                        $task_data['task_name'] = "generate_user_wallet_address";
                        $task_data['uid'] = $uid;
                        $task_data['schedule_time'] =0;
                        Db::name('cron')->insert($task_data);                           
                    }
                }      
           }
        } 
        $result = [
            'error'     => 0,
            'message' => '',
        ];
        return $result;
    }

    //实现的generate_ticker钩子方法
    public function generateTicker($param)
    {
        $uid           = $param['uid'];//用户ID
        //$config        = $this->getConfig();
        //$default_tickers = $config['default_tickers'];
        //$ticker_list =  preg_split("/[\s,]+/", $default_tickers);
        $ticker_id_list = array();
        $ticker_data = Db::name('ticker')
        ->field('id')
        ->where('default', 1)
        ->where('status', 1)
        ->select()->toArray();
        foreach ($ticker_data as $key => $value) {
            array_push($ticker_id_list, $value['id']);
        }
        $result  = false;
        
        foreach ($ticker_id_list as $ticker) {
           $ticker_count = Db::name('user_ticker')->where('uid', $uid)->where('ticker_id', $ticker)->count();
           if($ticker_count == 0){
                $insert_data['uid'] =  $uid ;
                $insert_data['ticker_id'] = $ticker;
                $insert_data['sort'] =  0;
                $insert_data['status'] =  1;
                $result = Db::name('user_ticker')->insert($insert_data);                 
           }
        } 
        $result = [
            'error'     => 0,
            'message' => '',
        ];
        return $result;
    }
}