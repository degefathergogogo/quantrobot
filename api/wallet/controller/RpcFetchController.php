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

class RpcFetchController extends RestBaseController
{

    public function index($coin_symbol){
        if($coin_symbol=='TRX'){
            $ret = $this->rpc_get_transactions($coin_symbol);
            if($ret['code']==0){
                die($ret['msg']);
            }
            //dump($ret);
            $pos = $ret['data']['lastblock'];
            $list = $ret['data']['transactions'];
            $address_list = [];
            $address_data = Db::name('wallet')
            ->Distinct(true)
            ->field("address")
            ->where('coin_symbol', $coin_symbol)
            ->where('address', 'neq','')
            ->select()->toArray();
            foreach ($address_data as $key => $value) {
                $address_list[] = $value['address'];
            } 
            //token 地址
            $address_data = Db::name('wallet')
            ->Distinct(true)
            ->field("address")
            ->alias('a')
            ->join(config('database.prefix').'coin b',"a.coin_symbol = b.coin_symbol")
            ->where('parent_coin', $coin_symbol)
            ->where('address', 'neq','')
            ->select()->toArray();
            foreach ($address_data as $key => $value) {
                $address_list[] = $value['address'];
            }     
            //var_dump($address_list);       
            //合约列表
            $contract_list = [];
            $contract_info=[];
            $contract_data = Db::name('coin')
            ->field("coin_symbol,contract,decimals")
            ->where('parent_coin', $coin_symbol)
            ->select()->toArray();
            foreach ($contract_data as $key => $value) {
                $value['contract'] = $value['contract'];
                $contract_list[] = $value['contract'];
                $contract_info[$value['contract']]['decimals'] =  $value['decimals'];
                $contract_info[$value['contract']]['coin_symbol'] =  $value['coin_symbol'];
            }
            //var_dump($contract_list);       
            foreach ($list as $key => $value) {                
                if(!isset($value['ret'][0]['contractRet'])){
                    continue;
                }                
                if($value['ret'][0]['contractRet']!=='SUCCESS'){
                    continue;
                }
                $blockhash =$value['txID'];
                $txid = $value['txID'];
                foreach($value['raw_data']['contract'] as $v){
                    $transaction_type= $v['type'];
                    if($transaction_type=='TransferContract'){//TransferContract TRX转账    TriggerSmartContract调用智能合约TRC20  TransferAssetContract TRC10
                    //"txID": "7de7f041ad511a1ebe3c542663bcffad2bfd4276abf89f53340100bf8d6ceab1",                        
                        // "paramevter": {
                        //     "value": {
                        //         "amount": 28000000,
                        //         "owner_address": "41d31be6889ba1c94ff01d188b18d76a050860496e",
                        //         "to_address": "41213d16ae8859b34e4cd52bad2b62a695903df0c7"
                        //     },
                        //     "type_url": "type.googleapis.com/protocol.TransferContract"
                        // },
                        // "type": "TransferContract"
                        $amount = $v['parameter']['value']['amount']/pow(10,6);
                        $fee = 0;
                        $memo = "";
                        $from= $this->hexString2Base58check($v['parameter']['value']['owner_address']);    
                        $to= $this->hexString2Base58check($v['parameter']['value']['to_address']);  
                        //var_dump($to);
                        //判断发送方
                        if (in_array($from,$address_list)){
                            $this->confirm_transfer($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);
                        }                
                        //判断接收方
                        if (in_array($to,$address_list)){
                            $this->add_transfer_log($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);
                        }                        
                    }
                    if($transaction_type=='TriggerSmartContract'){//TRC20
                        // ["parameter"]=>
                        // array(2) {
                        //   ["value"]=>
                        //   array(3) {
                        //     ["data"]=>
                        //     string(136) "a9059cbb000000000000000000000041d544b6c43b65aec63da9d849c072604ea855129e00000000000000000000000000000000000000000000000000000000b8987840"
                        //     ["owner_address"]=>
                        //     string(42) "417efd1d58b568b8a9d80da9c041da62a486f62274"
                        //     ["contract_address"]=>
                        //     string(42) "41a614f803b6fd780986a42c78ec9c7f77e6ded13c"
                        //   }
                        //   ["type_url"]=>
                        //   string(49) "type.googleapis.com/protocol.TriggerSmartContract"
                        // }
                        // ["type"]=>
                        // string(20) "TriggerSmartContract"
                        $from =  $this->hexString2Base58check($v['parameter']['value']['owner_address']);   
                        $contract_address =  $this->hexString2Base58check($v['parameter']['value']['contract_address']);    
                        $contract_data = $v['parameter']['value']['data'];
                        $fee = 0;
                        $memo = "";   
                        //var_dump($contract_data );
                        //var_dump($contract_address );
                        //var_dump($txid); 

                        if(strpos($contract_data,'a9059cbb')===0){
                            $token_to =  $this->hexString2Base58check("41".substr($contract_data,32,40));  
                            //var_dump($from);
                            //var_dump($token_to);  
                            $token_amount = substr($contract_data,72); 
                            $hex_number = preg_replace('/^0+/','',$token_amount);
                            if(empty($hex_number)){
                                continue;
                            }
                            $token_amount = gmp_init($hex_number,16);
                        }else{
                            continue;
                        }
                        if(in_array($contract_address,$contract_list)){
                            $token_decimal =  $contract_info[$contract_address]['decimals'];
                            $token_amount  = round( gmp_strval($token_amount ,10) /pow(10,$token_decimal),8);
                            // var_dump($contract_data);
                            //var_dump($token_to);
                            // var_dump($hex_number);
                            // var_dump($token_amount);
                            $token_symbol =  $contract_info[$contract_address]['coin_symbol'];
                            //判断发送方
                            if (in_array($from,$address_list)){
                                $this->confirm_transfer($token_symbol,$from,$token_to,$memo,$token_amount,$fee,$blockhash,$txid);
                            }                
                            //判断接收方
                            if (in_array($token_to,$address_list)){
                                $this->add_transfer_log($token_symbol,$from,$token_to,$memo,$token_amount,$fee,$blockhash,$txid);
                            }      
                        }                        
                    }
                    if($transaction_type=='TransferAssetContract'){//TRC10通证 不做兼容
                    //		"txID": "51cd0c9d53d8a2378b008731c798fb77a15bfd0bc7f202ba8763968a327cce24",
                        // "parameter": {
                        //     "value": {
                        //         "amount": 23,
                        //         "asset_name": "4861766541427265616b48617665414b69744b6174",
                        //         "owner_address": "417eb55685506a4dd30df8d657542631be7e456371",
                        //         "to_address": "41b0eae9ba1e917a650933c7d55a2d89c87dd7e5e2"
                        //     },
                        //     "type_url": "type.googleapis.com/protocol.TransferAssetContract"
                        // },
                        // "type": "TransferAssetContract"
                    }
                }
            }
            $this->update_rpc_last_pos($coin_symbol,$pos);      
        }

        if($coin_symbol=='BTC'||$coin_symbol=='BCH'||$coin_symbol=='LTC'||$coin_symbol=='VDS'){
            $ret = $this->rpc_get_transactions($coin_symbol);
            if($ret['code']==0){
                die($ret['msg']);
            }
            dump($ret);
            $list = $ret['data']['transactions'];
            $pos = $ret['data']['lastblock'];
            //$pos = $ret['data']['transactions'];
            foreach ($list as $key => $value) {
                $amount = $value['amount'];
                if(isset($value['blockhash'])){
                   $blockhash = $value['blockhash']; 
               }else{
                   $blockhash =$value['txid'];
               }
                
                $txid = $value['txid'];
                $fee = 0;
                $memo = "";
                if($value['category']=='receive'){
                   $from= "";
                   $to= $value['address'];                    
                   $this->add_transfer_log($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);
                }else{
                   $from= "";
                   $to= $value['address'];             
                   $fee = $value['fee'];     
                   $this->confirm_transfer($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);
                }
                //var_dump($value);  
            }
            $this->update_rpc_last_pos($coin_symbol,$pos);      
        }

        if($coin_symbol=='USDT'){
            //地址列表
            $address_list = [];
            $address_data = Db::name('wallet')
            ->Distinct(true)
            ->field("address")
            ->where('coin_symbol', $coin_symbol)
            ->where('address', 'neq','')
            ->select()->toArray();
            foreach ($address_data as $key => $value) {
                $address_list[] = $value['address'];
            }            
            $ret = $this->rpc_get_transactions($coin_symbol);
            if($ret['code']==0){
                die($ret['msg']);
            }
            $list = $ret['data']['transactions'];
            $pos = $ret['data']['lastblock'];

            foreach ($list as $key => $value) {
                $from= $value['sendingaddress'];
                $to= $value['referenceaddress'];
                $amount = $value['amount'];
                $blockhash = $value['blockhash'];
                $txid = $value['txid'];
                $memo = "";
                $fee = $value['fee'];
                if(in_array($to,$address_list)){
                   $this->add_transfer_log($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);
                }
                if(in_array($from,$address_list)){
                   $this->confirm_transfer($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);
                }                
            }
            $this->update_rpc_last_pos($coin_symbol,$pos);      
        }

        if($coin_symbol=='ETH'||$coin_symbol=='ETZ'){
            //地址列表
            $address_list = [];
            $address_data = Db::name('wallet')
            ->Distinct(true)
            ->field("address")
            ->where('coin_symbol', $coin_symbol)
            ->where('address', 'neq','')
            ->select()->toArray();
            foreach ($address_data as $key => $value) {
                $address_list[] = $value['address'];
            }
            $address_data = Db::name('wallet')
            ->Distinct(true)
            ->field("address")
            ->alias('a')
            ->join(config('database.prefix').'coin b',"a.coin_symbol = b.coin_symbol")
            ->where('parent_coin', $coin_symbol)
            ->where('address', 'neq','')
            ->select()->toArray();
            foreach ($address_data as $key => $value) {
                $address_list[] = $value['address'];
            }            
            //合约列表
            $contract_list = [];
            $contract_info=[];
            $contract_data = Db::name('coin')
            ->field("coin_symbol,contract,decimals")
            ->where('parent_coin', $coin_symbol)
            ->where('contract', 'neq','')
            ->select()->toArray();
            foreach ($contract_data as $key => $value) {
                $value['contract'] = strtolower($value['contract']);
                $contract_list[] = $value['contract'];
                $contract_info[$value['contract']]['coin_symbol'] =  $value['coin_symbol'];
                $contract_info[$value['contract']]['decimals'] =  $value['decimals'];
            }
            $ret = $this->rpc_get_transactions($coin_symbol);
            if($ret['code']==0){
                die($ret['msg']);
            }
            $list = $ret['data']['transactions'];
            $pos = $ret['data']['lastblock'];
            foreach ($list as $key => $value) {   
                $from = $value['from'];
                $to = $value['to'];     
                $amount  = gmp_init($value['value'],16);
                $amount  = round(gmp_strval($amount ,10)/pow(10,18),8) ;//转为十进制 
                $blockhash = $value['blockHash'];
                $gas_price =  gmp_init($value['gasPrice'],16);
                $gas_price  = gmp_strval($gas_price ,10)/pow(10,18);//转为十进制 
                $gas =  gmp_init($value['gas'],16);
                $gas  = gmp_strval($gas ,10);//转为十进制 
                $fee = $gas*$gas_price;
                $txid = $value['hash'];
                $memo = "";
                //判断发送方
                if (in_array($from,$address_list)){
                    if($amount == 0){ //是合约交易
                        if (in_array($to,$contract_list)){
                            $info_coin_symbol = $contract_info[$to]['coin_symbol'];
                            $info_decimals = $contract_info[$to]['decimals'];                            
                            //解析合约
                            $ret = $this->rpc_get_transaction_receipt($coin_symbol,$txid);
                            if($ret['code']==0){
                                die($ret['msg']);
                            }
                            if(isset($ret['data']['logs'][0])){
                                $info = $ret['data']['logs'][0];
                                $info_from = "0x".substr($info['topics'][1],26);
                                $info_to = "0x".substr($info['topics'][2],26);
                                if($info['data']=='0x'){
                                	continue;
                                }
                                $info_amount  = gmp_init($info['data'],16);
                                $info_amount  = round(gmp_strval($info_amount ,10)/pow(10,$info_decimals),8) ;//转为十进制  
                                $this->confirm_transfer($info_coin_symbol,$info_from,$info_to,$memo,$info_amount,$fee,$blockhash,$txid);
                            }
                        }
                    }else{
                        $this->confirm_transfer($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);
                    }   
                }
                //判断接收方
                if (in_array($to,$address_list)){
                   $this->add_transfer_log($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);
                }
                //判断合约地址          
                if (in_array($to,$contract_list)){
                  
                    $info_coin_symbol = $contract_info[$to]['coin_symbol'];
                    $info_decimals = $contract_info[$to]['decimals'];  
                  
                    $token_address_list = [];
                    $token_address_data = Db::name('wallet')
                    ->Distinct(true)
                    ->field("address")
                    ->where('coin_symbol', $info_coin_symbol )
                    ->where('address', 'neq','')
                    ->select()->toArray();
                    foreach ($token_address_data as $key => $value) {
                        $token_address_list[] = $value['address'];
                    }                  
                    //解析合约
                    $ret = $this->rpc_get_transaction_receipt($coin_symbol,$txid);
                    if($ret['code']==0){
                        die($ret['msg']);
                    }
                    //dump('1');
                     //dump($ret['data']['logs']);
                    if(isset($ret['data']['logs'][0])){
                        //dump('2');
                        $info = $ret['data']['logs'][0];

                        if (isset($info['topics'][2])) {
                            $info_from = "0x".substr($info['topics'][1],26);
                            $info_to = "0x".substr($info['topics'][2],26);
                            if($info['data']=='0x'){
                            	continue;
                            }
                            $info_amount  = gmp_init($info['data'],16);
                            $info_amount  = round(gmp_strval($info_amount ,10)/pow(10,$info_decimals),8) ;//转为十进制 
                            //dump($info_to);
                            if(in_array($info_to,$token_address_list)) {
                                //dump('3');dump($from);dump($to);dump($info_amount);dump($fee);
                                $this->add_transfer_log($info_coin_symbol,$info_from,$info_to,$memo,$info_amount,$fee,$blockhash,$txid);
                            } 
                        }
                            
                    }
               
                }
            }

            $this->update_rpc_last_pos($coin_symbol,$pos); 
        }

        if($coin_symbol=='ETC'){
            //地址列表
            $address_list = [];
            $address_data = Db::name('wallet')
            ->Distinct(true)
            ->field("address")
            ->where('coin_symbol', $coin_symbol)
            ->where('address', 'neq','')
            ->select()->toArray();
            foreach ($address_data as $key => $value) {
                $address_list[] = $value['address'];
            }
        
            $ret = $this->rpc_get_transactions($coin_symbol);
            if($ret['code']==0){
                die($ret['msg']);
            }
            $list = $ret['data']['transactions'];
            $pos = $ret['data']['lastblock'];
            foreach ($list as $key => $value) {   
                $from = $value['from'];
                $to = $value['to'];     
                $amount  = gmp_init($value['value'],16);
                $amount  = round(gmp_strval($amount ,10)/pow(10,18),8) ;//转为十进制 
                $blockhash = $value['blockHash'];
                $gas_price =  gmp_init($value['gasPrice'],16);
                $gas_price  = gmp_strval($gas_price ,10)/pow(10,18);//转为十进制 
                $gas =  gmp_init($value['gas'],16);
                $gas  = gmp_strval($gas ,10);//转为十进制 
                $fee = $gas*$gas_price;
                $txid = $value['hash'];
                $memo = "";
                //判断发送方
                if (in_array($from,$address_list)){
                    $this->confirm_transfer($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);                  
                }
                //判断接收方
                if (in_array($to,$address_list)){
                   $this->add_transfer_log($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);
                }
            }
            $this->update_rpc_last_pos($coin_symbol,$pos);         
        }

        if($coin_symbol=='XRP'){
            $ret = $this->rpc_get_transactions($coin_symbol);
            if($ret['code']==0){
                die($ret['msg']);
            }
            var_dump($ret);
            $list = $ret['data']['transactions'];
            $pos = $ret['data']['lastblock'];
            //$pos = $ret['data']['transactions'];
            $addr = "rMVm2PSvCWoQJj2AoYsPATuWXj6HMeGvLe";
            foreach ($list as $key => $value) {
                $from= $value['tx']['Account'];
                $to = $value['tx']['Destination'];
                $amount = floatval($value['tx']['Amount'])/pow(10,6);
                $fee = floatval($value['tx']['Fee'])/pow(10,6);
                $blockhash = "";
                $txid = $value['hash'];
                
                if(isset($value['tx']['DestinationTag'])){
                    $memo = $value['tx']['DestinationTag'];
                    if($to == $addr ){
                       //$this->add_transfer_log($coin_symbol,$addr,$memo,$amount,$blockhash,$txid);
                       $this->add_transfer_log($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);
                    }
                    if($from == $addr){
                       //$this->confirm_transfer($coin_symbol,$addr,$memo,$amount,$blockhash,$txid);
                       $this->confirm_transfer($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid);  
                    }
                    echo  $txid." , ".$from." , ".$to." ,".$amount.", ".$memo."\r\n";
                }
            }
            $this->update_rpc_last_pos($coin_symbol,$pos);      
        }      

        if($coin_symbol=='EOS'){

            $ret = $this->rpc_get_transactions($coin_symbol);
            if($ret['code']==0){
                die($ret['msg']);
            }
            $list = $ret['data']['transactions'];
            $pos = $ret['data']['lastblock'];
            //$pos = $ret['data']['transactions'];
            $addr = "dandanyatou1";

            foreach ($list as $key => $value) {
                var_dump($value);
                $from= $value['data']['from'];
                $to =$value['data']['to'];
                $amount =  floatval(str_replace(" EOS","",$value['data']['quantity']));
                //var_dump($amount);
                $blockhash = $value['block_num'];
                $txid = strtolower($value['id']);   
                if(isset($value['data']['memo'])){
                    if(!empty($value['data']['memo'])){
                        $memo = $value['data']['memo'];
                        if($to == $addr ){
                           //$this->add_transfer_log($coin_symbol,$addr,$memo,$amount,$blockhash,$txid);
                           $this->add_transfer_log($coin_symbol,$from,$to,$memo,$amount,0,$blockhash,$txid);
                        }
                        if($from == $addr){
                           //$this->confirm_transfer($coin_symbol,$addr,$memo,$amount,$blockhash,$txid);
                          var_dump($txid);
                           $this->confirm_transfer($coin_symbol,$from,$to,$memo,$amount,0,$blockhash,$txid); 
                        }                       
                    }else{
                        if($from == $addr){
                          //$this->confirm_transfer($coin_symbol,$addr,$memo,$amount,$blockhash,$txid);
                          $memo = "";
                          var_dump($txid);
                          $this->confirm_transfer($coin_symbol,$from,$to,$memo,$amount,0,$blockhash,$txid); 
                        }   
                    }

                   // echo  $txid." , ".$from." , ".$to." ,".$amount.", ".$memo."\r\n";
                }
            }
            $this->update_rpc_last_pos($coin_symbol,$pos);      
        } 

    }

    function rpc_get_transactions($coin_symbol){

        $coin = strtolower($coin_symbol); 
        $vendor_name = "Chain.".$coin."rpc";
        Vendor($vendor_name);
        $class_name = "\\".$coin."rpc";
        //检测钱包是否存在
        $fieldStr = 'rpc_ip,rpc_port,rpc_user,rpc_pass,rpc_last_pos';
        $coin_data = Db::name('coin')
        ->field($fieldStr)
        ->where('coin_symbol', $coin_symbol)
        ->find();
        if(empty($coin_data)){ //没有该币种
            return array('code'=>0,'msg'=>"coin symbol not exists");  
        }
        //币种rpc配置
        $rpc_ip = $coin_data['rpc_ip'];
        $rpc_port = $coin_data['rpc_port'];
        $rpc_user = $coin_data['rpc_user'];
        $rpc_pass = $coin_data['rpc_pass'];       
        //RPC连接
        $rpc = new $class_name($rpc_ip,$rpc_port,$rpc_user,$rpc_pass);    


        if (!method_exists($rpc,"get_Transactions")) {
            return array('code'=>0,'msg'=>"$class_name method get_Transactions not exists");   
        }
        dump($coin_data['rpc_last_pos']);
        $ret = $rpc->get_Transactions($coin_data['rpc_last_pos']); 
         dump(count($ret));

        if($ret['code'] == 1){
             return array('code'=>1,'msg'=>"$class_name method: get_Transactions ok",'data'=>$ret['data']);     
        }else{
             return array('code'=>0,'msg'=>"$class_name get_Transactions error:".$ret['data'],'data'=>array());              
        }  
                         
    }

    function rpc_get_transaction_receipt($coin_symbol,$txid){
        $coin = strtolower($coin_symbol); 
        $vendor_name = "Chain.".$coin."rpc";
        Vendor($vendor_name);
        $class_name = "\\".$coin."rpc";
        //检测钱包是否存在
        $fieldStr = 'rpc_ip,rpc_port,rpc_user,rpc_pass,rpc_last_pos';
        $coin_data = Db::name('coin')
        ->field($fieldStr)
        ->where('coin_symbol', $coin_symbol)
        ->find();
        if(empty($coin_data)){ //没有该币种
            return array('code'=>0,'msg'=>"coin symbol not exists");  
        }
        //币种rpc配置
        $rpc_ip = $coin_data['rpc_ip'];
        $rpc_port = $coin_data['rpc_port'];
        $rpc_user = $coin_data['rpc_user'];
        $rpc_pass = $coin_data['rpc_pass'];       
        //RPC连接
        $rpc = new $class_name($rpc_ip,$rpc_port,$rpc_user,$rpc_pass);    
        if (!method_exists($rpc,"get_TransactionReceipt")) {
            return array('code'=>0,'msg'=>"$class_name method get_TransactionReceipt not exists");   
        }
        $ret = $rpc->get_TransactionReceipt($txid); 
        if($ret['code'] == 1){
             return array('code'=>1,'msg'=>"$class_name method: get_TransactionReceipt ok",'data'=>$ret['data']);     
        }else{
             return array('code'=>0,'msg'=>"$class_name get_TransactionReceipt error:".$ret['data'],'data'=>array());              
        }  
                         
    }

    function update_rpc_last_pos($coin_symbol,$pos){
        $data['rpc_last_pos'] = $pos;
        Db::name('coin')->where('coin_symbol', $coin_symbol)->update($data);
    }

    public function add_transfer_log($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid){
        //先查找交易是否存在
        $find = Db::name('transfer_log')
        ->field("id")
        ->where('type', 3)
        ->where('tx_id', $txid)
        ->find();

        if($find){
            return;
        }
        //先查找地址
        //获取用户钱包余额    
        $walletData = Db::name('wallet')
        ->field("id,chain_balance")
        ->where('address', $to)
        ->where('memo',$memo)
        ->where('coin_symbol',$coin_symbol)
        ->find();

        if(empty($walletData))
            return;  
        //开始事务处理
        Db::startTrans();
        $result = Db::name('wallet')
        ->where('id', $walletData['id'] )
        ->setInc('chain_balance',$amount);
        if(!$result){
            Db::rollback();
            //$this->error('转账提交失败(#1)！'); 
            return;         
        }          
        $balance =  $walletData['chain_balance'];
        //获取新余额
        $balance_data = Db::name('wallet')
        ->field('chain_balance')
        ->where('id', $walletData['id'] )
        ->find();
        //写入交易日志
        $balance_after =  $balance_data['chain_balance'];
        $insert_data['type'] =  3 ; 
        $insert_data['wallet_id'] = $walletData['id'] ;
        $insert_data['coin_symbol'] =  $coin_symbol;
        $insert_data['from_address'] =  $from;
        $insert_data['to_address'] =  $to;
        $insert_data['amount'] =  $amount;
        $insert_data['amount_before'] =   $balance;
        $insert_data['amount_after'] =   $balance_after;
        $insert_data['fee'] =   $fee;
        $insert_data['log_time'] =  time();
        $insert_data['memo'] ='';
        $insert_data['blockhash'] = $blockhash;
        $insert_data['tx_id'] =$txid;
        $insert_data['transfer_status'] = 1;
        $result = Db::name('transfer_log')->insertGetId($insert_data);  
        if($result){
            //增加任务

            if(($coin_symbol!='XRP')&&($coin_symbol!='EOS')){
                $p = array();  
                $p['wallet_id'] =  $walletData['id'];                 
                $task_data['params'] = json_encode($p);
                $task_data['task_name'] = "update_wallet_balance";
                $task_data['wallet_id'] =$walletData['id'];
                $task_data['schedule_time'] =0;
                Db::name('cron')->insert($task_data);                      
            }else{
                //XRP EOS
            }
            $p['transaction_id'] = $result;
            $p['notify_type'] = "payment";
            $task_data['params'] = json_encode($p);
            $task_data['task_name'] = "notify_url";
            $task_data['wallet_id'] =$walletData['id'];
            $task_data['schedule_time'] =0;
            Db::name('cron')->insert($task_data);   

            Db::commit();
            //$this->success('转账提交成功！');
            return;
        }else{
            Db::rollback();
            //$this->error('转账提交失败(#2)！');
            return;        
        }                      
    
   
          
        
    }

    public function confirm_transfer($coin_symbol,$from,$to,$memo,$amount,$fee,$blockhash,$txid){
        //写入交易日志

        $logs = Db::name('transfer_log')
        ->field('id,wallet_id,to_wallet_id,coin_symbol,type')
        ->where('tx_id',$txid)
        ->where('transfer_status',2)
        ->select()->toArray();  
        foreach ($logs as $key => $value) {
            $log_id = $value['id'];
            $update_data['transfer_status'] = 1;
            $update_data['fee'] = $fee;
            Db::name('transfer_log')->where('id',$log_id)->update($update_data);
            
            $p = array();  
            $p['wallet_id'] =  $value['wallet_id']; 
            $task_data['params'] = json_encode($p);
            $task_data['task_name'] = "update_wallet_balance";
            $task_data['wallet_id'] =$value['wallet_id'];
            $task_data['schedule_time'] =0;
            Db::name('cron')->insert($task_data);
            if($value['to_wallet_id']>0){
                if(($coin_symbol!='XRP')&&($coin_symbol!='EOS')){
                    $p = array();  
                    $p['wallet_id'] =  $value['to_wallet_id']; 
                    $task_data['params'] = json_encode($p);
                    $task_data['task_name'] = "update_wallet_balance";
                    $task_data['wallet_id'] =$value['to_wallet_id'];
                    $task_data['schedule_time'] =0;
                    Db::name('cron')->insert($task_data);
                }else{
                    //XRP EOS
                    //$result = Db::name('wallet')
                    //->where('id',$value['to_wallet_id'])
                    //->setInc('chain_balance',$amount);
                }
            }
            if(($value['type']!=4)&&($value['type']!=5)){
                $p = array();     
                $p['transaction_id'] =  $value['id'];
                $p['notify_type'] = "confirm";
                $task_data['params'] = json_encode($p);
                $task_data['task_name'] = "notify_url";
                $task_data['wallet_id'] =$value['wallet_id'];
                $task_data['schedule_time'] =0;
            }

            Db::name('cron')->insert($task_data);                           
        }

        if(!empty($from)){

            //更新余额
            $wallet = Db::name('wallet')
            ->field('id,address')
            ->where('address',$from)
            ->find();  
            //var_dump($from);
            //var_dump($wallet);
            //增加任务
            if(($coin_symbol!='XRP')&&($coin_symbol!='EOS')){
                $p = array();  
                $p['wallet_id'] =  $wallet['id'];                 
                $task_data['params'] = json_encode($p);
                $task_data['task_name'] = "update_wallet_balance";
                $task_data['wallet_id'] =$wallet['id'];
                $task_data['schedule_time'] =0;
                Db::name('cron')->insert($task_data);  
            }else{
                //XRP EOS
            }
            $insert_data = array();
            $insert_data['tx_id'] = $txid;
            $insert_data['coin_symbol'] =  $coin_symbol;
            $insert_data['from_address'] =  $from;
            $insert_data['to_address'] =  $to;
            $insert_data['memo'] =   $memo;
            $insert_data['amount'] =  $amount;
            $insert_data['fee'] =  $fee;
            $insert_data['block_hash'] = $blockhash;
            $insert_data['log_time'] =  time();
            $result = Db::name('chain_log')->insertGetId($insert_data);  
            

        }
  
    }
    
    private function base58_encode($string)
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        if (is_string($string) === false) {
            return false;
        }
        if (strlen($string) === 0) {
            return '';
        }
        $bytes = array_values(unpack('C*', $string));
        $decimal = $bytes[0];
        for ($i = 1, $l = count($bytes); $i < $l; $i++) {
            $decimal = bcmul($decimal, 256);
            $decimal = bcadd($decimal, $bytes[$i]);
        }
        $output = '';
        while ($decimal >= $base) {
            $div = bcdiv($decimal, $base, 0);
            $mod = bcmod($decimal, $base);
            $output .= $alphabet[$mod];
            $decimal = $div;
        }
        if ($decimal > 0) {
            $output .= $alphabet[$decimal];
        }
        $output = strrev($output);
        foreach ($bytes as $byte) {
            if ($byte === 0) {
                $output = $alphabet[0] . $output;
                continue;
            }
            break;
        }
        return (string) $output;
    }
    private function base58_decode($base58)
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        if (is_string($base58) === false) {
            return false;
        }
        if (strlen($base58) === 0) {
            return '';
        }
        $indexes = array_flip(str_split($alphabet));
        $chars = str_split($base58);
        foreach ($chars as $char) {
            if (isset($indexes[$char]) === false) {
                return false;
            }
        }
        $decimal = $indexes[$chars[0]];
        for ($i = 1, $l = count($chars); $i < $l; $i++) {
            $decimal = bcmul($decimal, $base);
            $decimal = bcadd($decimal, $indexes[$chars[$i]]);
        }
        $output = '';
        while ($decimal > 0) {
            $byte = bcmod($decimal, 256);
            $output = pack('C', $byte) . $output;
            $decimal = bcdiv($decimal, 256, 0);
        }
        foreach ($chars as $char) {
            if ($indexes[$char] === 0) {
                $output = "\x00" . $output;
                continue;
            }
            break;
        }
        return $output;
    }
    
    //encode address from byte[] to base58check string
    private function base58check_en($address)
    {
        $hash0 = hash("sha256", $address);
        $hash1 = hash("sha256", hex2bin($hash0));
        $checksum = substr($hash1, 0, 8);
        $address = $address.hex2bin($checksum);
        $base58add = $this->base58_encode($address);
        return $base58add;
    }
    
    //decode address from base58check string to byte[]
    private function base58check_de($base58add)
    {
        $address = $this->base58_decode($base58add);
        $size = strlen($address);
        if ($size != 25) {
            return false;
        }
        $checksum = substr($address, 21);
        $address = substr($address, 0, 21);     
        $hash0 = hash("sha256", $address);
        $hash1 = hash("sha256", hex2bin($hash0));
        $checksum0 = substr($hash1, 0, 8);
        $checksum1 = bin2hex($checksum);
        if (strcmp($checksum0, $checksum1)) {
            return false;
        }
        return $address;
    }
    
    private function hexString2Base58check($hexString){
        $address = hex2bin($hexString);
        $base58add = $this->base58check_en($address);
        return $base58add;
    }
    
    private function base58check2HexString($base58add){
        $address = $this->base58check_de($base58add);
        $hexString = bin2hex($address);
        return $hexString;
    }
    
    private function hexString2Base64($hexString){
        $address = hex2bin($hexString);
        $base64 = base64_encode($address);
        return $base64;
    }
    
    private function base642HexString($base64){
        $address = base64_decode($base64);
        $hexString = bin2hex($address);
        return $hexString;
    }
    
    private function base58check2Base64($base58add){
        $address = $this->base58check_de($base58add);
        $base64 = base64_encode($address);
        return $base64;
    }
    
    private function base642Base58check($base64){
        $address = base64_decode($base64);
        $base58add = $this->base58check_en($address);
        return $base58add;
    }

    private  function bc_dechex($number)
    {
        if ($number <= 0) {
            return false;
        }
        $conf = ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];
        $char = '';
        do {
            $key = fmod($number, 16);
            $char = $conf[$key].$char;
            $number = floor(($number-$key)/16);
        } while ( $number > 0);
        return $char;
    }

}
