<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wallet\controller;

use cmf\controller\RestBaseController;
use think\Db;
use think\Validate;



class RpcTransferController extends RestBaseController
{

    public function index(){
        //写入交易日志

        $logs = Db::name('transfer_log')
        ->field('id,wallet_id,coin_symbol,from_address,to_address,to_memo,amount,type')
        ->where('type','in',['1','2','4','5']) //外部
        ->where('transfer_status',0)
        ->where('audit_status',1)
        ->limit(200)
        ->select()->toArray();  
        foreach ($logs as $key => $value) {
            //var_dump($value);
            $log_id = $value['id'];
            $coin_symbol = $value['coin_symbol'];
            $wallet_id = $value['wallet_id'];
            $to_address = $value['to_address'];
            $to_memo = $value['to_memo'];
            $amount = abs($value['amount']);
            $type =  $value['type'];
            if($type == 2){
                $p = array();     
                $p['transaction_id'] =  $log_id ;
                $p['notify_type'] = "payment";
                $task_data['params'] = json_encode($p);
                $task_data['task_name'] = "notify_url";
                $task_data['wallet_id'] = $wallet_id;
                $task_data['schedule_time'] =0;
                Db::name('cron')->insert($task_data);   

                $p = array();     
                $p['transaction_id'] = $log_id ;
                $p['notify_type'] = "confirm";
                $task_data['params'] = json_encode($p);
                $task_data['task_name'] = "notify_url";
                $task_data['wallet_id'] = $wallet_id;
                $task_data['schedule_time'] =1;
                Db::name('cron')->insert($task_data); 

                $update_data['tx_id'] = time();
                $update_data['memo'] = "";
                $update_data['transfer_status'] = 1;
                Db::name('transfer_log')->where('id',$log_id)->update($update_data);   
                die();
            }else{
                $ret = $this->rpc_send_transactions($wallet_id,$coin_symbol,$to_address,$amount,$type,$to_memo);
            }
           //var_dump($ret);
           // var_dump($ret);die();
            if($ret['code']==1){
                $update_data['tx_id'] = $ret['data'];
                $update_data['memo'] = "";
                $update_data['transfer_status'] = 2;
                Db::name('transfer_log')->where('id',$log_id)->update($update_data);               
            }elseif($ret['code']==0){
                $update_data['tx_id'] = "";
                $update_data['memo'] = $ret['msg'];
                $update_data['transfer_status'] = -1;
                Db::name('transfer_log')->where('id',$log_id)->update($update_data);                  
            }else{
                //暂不处理
            }
        }

    }

    function rpc_send_transactions($wallet_id,$coin_symbol,$to_address,$amount,$type,$to_memo){

        //检测钱包是否存在
        $fieldStr = 'coin_type,parent_coin,contract,decimals,rpc_ip,rpc_port,rpc_user,rpc_pass,rpc_last_pos,min_fee';
        $coin_data = Db::name('coin')
        ->field($fieldStr)
        ->where('coin_symbol', $coin_symbol)
        ->find();
        if(empty($coin_data)){ //没有该币种
            return array('code'=>0,'msg'=>"coin symbol not exists");  
        }
        if($coin_symbol=='XRP'||$coin_symbol=='EOS'){
            //return array('code'=>0,'msg'=>"XRP EOS not support auto transfer");
        }
        if($coin_data['coin_type'] != 'coin'){
            $coin_type = "token";
            $coin_fee =  $coin_data['min_fee'];
            $contract = $coin_data['contract'];
            $decimals = $coin_data['decimals'];
            if(empty($coin_data['parent_coin'])){
                return array('code'=>0,'msg'=>"given coin symbol is token but without parent coin");   
            }
            $coin_symbol_rpc = $coin_data['parent_coin'];
            $coin_data = Db::name('coin')
            ->field($fieldStr)
            ->where('coin_symbol',$coin_data['parent_coin'])
            ->find(); 
            if(empty($coin_data)){
                return array('code'=>0,'msg'=>"given token's parent coin not exists"); 

            }           
        }else{
            $coin_type = "coin";
            $coin_fee =  $coin_data['min_fee'];
            $coin_symbol_rpc = $coin_symbol;
        }

        $coin = strtolower($coin_symbol_rpc); 
        $vendor_name = "Chain.".$coin."rpc";
        Vendor($vendor_name);
        $class_name = "\\".$coin."rpc";
        //币种rpc配置
        $rpc_ip = $coin_data['rpc_ip'];
        $rpc_port = $coin_data['rpc_port'];
        $rpc_user = $coin_data['rpc_user'];
        $rpc_pass = $coin_data['rpc_pass'];       
        //RPC连接
        $rpc = new $class_name($rpc_ip,$rpc_port,$rpc_user,$rpc_pass);  

        if($coin_type== "coin"){
            if (!method_exists($rpc,"send_Transactions")) {
                return array('code'=>0,'msg'=>"$class_name method send_Transactions not exists");   
            }            
        }else{
            if (!method_exists($rpc,"send_TokenTransactions")) {
                return array('code'=>0,'msg'=>"$class_name method send_TokenTransactions not exists");   
            }                 
        }

        //获取系统账户钱包  读取系统配置
        $wallet_data = Db::name('wallet')
        ->field("chain_balance,chain_balance_fee,address,memo,seed")
        ->where('id', $wallet_id)
        ->find(); 
        if(empty($wallet_data)){
            return array('code'=>0,'msg'=>"wallet_id not exists");               
        }

        //如果是归集汇总
        if($type == 4){
            if($wallet_data['chain_balance']< $amount){
                return array('code'=>0,'msg'=>"collect balance insufficient"); 
            }
            if(($coin_symbol=='USDT')||($coin_type== "token")){
                if($wallet_data['chain_balance_fee'] < $coin_fee){
                    //var_dump($wallet_data['chain_balance_fee']);
                    //var_dump($coin_fee);
                    return array('code'=>-1,'msg'=>"collect  token fee insufficient"); 
                }
            }else{
                if($wallet_data['chain_balance'] < $coin_fee){
                    return array('code'=>0,'msg'=>"collect coin fee insufficient"); 
                } 
                $amount =   $amount - $coin_fee;   
                //var_dump( $coin_fee) ;
                //var_dump($amount);
            }
        }
                                                        dump($wallet_id);

        // if($wallet_data['chain_balance']< $amount){
        if($type!=4){
            //重新寻找合适的转出账户
            if(($coin_symbol=='USDT')||($coin_symbol=='ETH')||($coin_symbol=='ETC')||($coin_type== "token")){//USDT ETH ETC和代币  统一从出款账户出款
                $wallet_data2 = Db::name('wallet')
                ->field("chain_balance,address,memo,seed")
                ->where('coin_symbol',$coin_symbol)
                ->where('pay_status',1)
                ->find();                            
                if(!$wallet_data2){
                    return array('code'=>0,'msg'=>"payment wallet not exists"); 
                }else{
                    $wallet_data = $wallet_data2;
                } 
            }else{
                $wallet_data2 = Db::name('wallet')
                ->field("chain_balance,address,memo,seed")
                ->where('coin_symbol',$coin_symbol)
                ->where('chain_balance',"egt", $amount)
                ->find();                            
                if(!$wallet_data2){
                   if($coin_symbol == 'ETH'){
                     return array('code'=>0,'msg'=>"wallet balance insufficient"); 
                   }
                }else{
                    $wallet_data = $wallet_data2;
                } 
            }                   
        }
        $from_address = $wallet_data['address'];
        $seed = $wallet_data['seed'];
        if($coin_type== "coin"){
            //var_dump($from_address);
            //var_dump($to_address);
            //var_dump($amount);
            if(($coin_symbol=='EOS')||($coin_symbol=='XRP')){
                $ret = $rpc->send_Transactions($from_address,$to_address,$amount,$to_memo); 
            }else{
                $ret = $rpc->send_Transactions($from_address,$to_address,$amount,$seed); 
            }
        }else{
            $ret = $rpc->send_TokenTransactions($from_address,$to_address,$amount,$seed,$contract,$decimals); 
        }
        //var_dump($ret);die();

        if(!is_array($ret)){
        	return array('code'=>0,'msg'=>"$class_name send_Transactions error:".json_encode($ret->error->message),'data'=>""); 
        }
        if($ret['code'] == 1){
             return array('code'=>1,'msg'=>"$class_name method: send_Transactions ok",'data'=>$ret['data']['tx_id']);     
        }else{
             return array('code'=>0,'msg'=>"$class_name send_Transactions error:".json_encode($ret),'data'=>"");         
        }  
                         
    }

}
