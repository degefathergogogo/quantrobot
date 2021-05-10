<?php

namespace api\wallet\controller;

use api\wallet\service\ColumnName;
use cmf\controller\RestUserBaseController;
use think\Db;
use think\Validate;

class AccountController extends RestUserBaseController
{

    public function create(){

        $validate = new Validate([
            'coin_symbol'     => 'require',
            'uuid'     => 'require',
        ]);

        $validate->message([
            'coin_symbol.require'  => 'coin_symbol不能为空!',
            'uuid.require'  => 'uuid不能为空!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $coin_symbol = $data['coin_symbol'];     
        $uuid = $data['uuid'];      
        //检测钱包是否存在
        $fieldStr = 'id as wallet_id,uuid,coin_symbol,chain_balance,address,memo,add_time';
        $check_wallet = Db::name('wallet')->where('coin_symbol',$coin_symbol)->where('uuid',$uuid)->field($fieldStr)->find();
        if(($check_wallet) && (!empty($check_wallet["address"]))){

            //$this->error("this coin wallet already exists");  
            $this->success('success',$check_wallet); 
        }

        
        //检测币种是否存在
        $fieldStr = 'coin_type,parent_coin,rpc_ip,rpc_port,rpc_user,rpc_pass';
        $coin_data = Db::name('coin')
        ->field($fieldStr)
        ->where('coin_symbol', $coin_symbol)
        ->find();
        if(empty($coin_data)){ //没有该币种
            $this->error("coin symbol not exists");  
        }
        //判断是否是主链币
        if($coin_data['coin_type'] == 'coin'){
            $this->create_fun($coin_symbol,$uuid,0);//直接创建然后输出,不往下执行,token继续执行
        }
        //查找父币种信息
        if(empty($coin_data['parent_coin'])){
            $this->error("given coin symbol is token but without parent coin");   
        }
        $parent_coin_data = Db::name('coin')
        ->field($fieldStr)
        ->where('coin_symbol',$coin_data['parent_coin'])
        ->find(); 
        if(empty($parent_coin_data)){
            $this->error("given token's parent coin not exists "); 
        }
        
        //检测钱包是否存在,不存在插入一条
        $fieldStr = 'id as wallet_id,uuid,coin_symbol,chain_balance,address,memo,add_time';
        $check_wallet = Db::name('wallet')->where('coin_symbol',$coin_symbol)->where('uuid',$uuid)->field($fieldStr)->find();
        if($check_wallet){
			$wallet_id = $check_wallet['wallet_id'];
        }else{
	        $insert_data = array();
	        $insert_data['uuid'] =  $uuid;
	        $insert_data['coin_symbol'] =  $coin_symbol;
	        $insert_data['add_time'] =  time();
	        $insert_data['status'] =  0;
	        $result = Db::name('wallet')->insertGetId($insert_data);
	        if(!$result){
	            $this->error('user wallet create failed');          
	        }
	        $wallet_id = $result;	
        }

        //检测父币种钱包是否存在
        $parent_coin_wallet = Db::name('wallet')->where('coin_symbol',$coin_data['parent_coin'])->where('uuid',$uuid)->find();
        if($parent_coin_wallet){
            $ret = array();
            $ret['seed'] = $parent_coin_wallet['seed'];
            $ret['address'] = $parent_coin_wallet['address'];
            $ret['memo'] = $parent_coin_wallet['memo'];
        }else{
            $ret = $this->create_fun($coin_data['parent_coin'],$uuid,1);          
        }
        if(!empty($ret['address'])){

            $update_data = array();
            $update_data['seed'] = $ret['seed'];
            $update_data['address'] = $ret['address'] ;
            $update_data['memo'] = $ret['memo'] ;
            $update_data['status'] = 1;
            $result = Db::name('wallet')->where('id',$wallet_id)->update($update_data);
            $return_data['wallet_id'] =  intval($wallet_id);
            $return_data['uuid'] =  $uuid;
            $return_data['coin_symbol'] =  $coin_symbol;
            $return_data['address'] =  $ret['address'];
            $return_data['memo'] =  $ret['memo'];
            $this->success('success',$return_data);         
        }else{
            $this->error('parent coin wallet create failed');         
        }          
    }

  //创建coin 钱包,token直接返回了,不创建
  private function create_fun($coin_symbol,$uuid,$nRet=0){
        //检测币种是否存在
        $fieldStr = 'coin_type,parent_coin,rpc_ip,rpc_port,rpc_user,rpc_pass';
        $coin_data = Db::name('coin')
        ->field($fieldStr)
        ->where('coin_symbol', $coin_symbol)
        ->find();
        if(empty($coin_data)){ //没有该币种
            $this->error("coin symbol not exists");  
        }
        if($coin_data['coin_type'] != 'coin'){
            $this->error("given coin symbol is token");              
        }else{
            $coin_symbol_rpc = $coin_symbol;
        }


        $coin = strtolower($coin_symbol_rpc);
        $vendor_name = "Chain.".$coin."rpc";
        Vendor($vendor_name);
        $class_name = "\\".$coin."rpc";
        if(!class_exists($class_name)){
            $this->error("$class_name not exists");
        }             
        $rpc_ip = $coin_data['rpc_ip'];
        $rpc_port = $coin_data['rpc_port'];
        $rpc_user = $coin_data['rpc_user'];
        $rpc_pass = $coin_data['rpc_pass'];           
        if(empty($rpc_ip)||empty($rpc_port)){
            $this->error("rpc ip or port not set");         
        }        
        //RPC连接
        $rpc = new $class_name($rpc_ip,$rpc_port,$rpc_user,$rpc_pass);    
        //生成钱包地址钱包  
        if (!method_exists($rpc,"get_NewAddress")) {
            $this->error("$class_name method get_NewAddress not exists");  
        }        
        $ret = $rpc->get_NewAddress($uuid);
        if($ret['code']==1){
            $insert_data = array();
            $insert_data['uuid'] =  $uuid;
            $insert_data['coin_symbol'] =  $coin_symbol;
            $insert_data['add_time'] =  time();
            $insert_data['status'] =  1;
            $insert_data['seed'] = $ret['data']['seed'];
            $insert_data['address'] = $ret['data']['address'] ;
            $insert_data['memo'] = $ret['data']['memo'] ;            
            $result = Db::name('wallet')->insertGetId($insert_data);
            if(!$result){
                $this->error('user wallet create failed');          
            }

            $wallet_id = $result;
            $return_data['wallet_id'] =  intval($wallet_id);
            $return_data['uuid'] =  $uuid;
            $return_data['coin_symbol'] =  $coin_symbol;
            $return_data['address'] =  $ret['data']['address'];
            $return_data['memo'] =  $ret['data']['memo'];
            if($nRet==0){
                $this->success('success',$return_data); 
            }else{
                $return_data['seed'] =  $ret['data']['seed'];
                return $return_data;
            }                    
        }else{
            $this->error($ret['data']);         
        }
    }

    public function info(){

        $validate = new Validate([
            'wallet_id'     => 'require',
        ]);
        $validate->message([
            'wallet_id.require'  => 'wallet_id不能为空!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $wallet_id = $data['wallet_id'];
        $fieldStr = 'id as wallet_id,uuid,coin_symbol,chain_balance,address,memo,add_time';
        $wallet_data = Db::name('wallet')
        ->field($fieldStr)
        ->where('id', $wallet_id)
        ->where('status', 1)
        ->find();
        if(empty($wallet_data)){ //没有该币种
            $this->error2("wallet not exists");  
        }else{
            $this->success('success',$wallet_data); 
        }
    }

    public function info2(){

        $validate = new Validate([
            'address'     => 'require',
        ]);
        $validate->message([
            'address.require'  => 'address不能为空!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $address = $data['address'];
        $fieldStr = 'id as wallet_id,uuid,coin_symbol,chain_balance,address,memo,add_time';
        $wallet_data = Db::name('wallet')
        ->field($fieldStr)
        ->where('address', $address)
        ->where('status', 1)
        ->find();
        if(empty($wallet_data)){ //没有该币种
            $this->error2("wallet not exists");  
        }else{
            $this->success('success',$wallet_data); 
        }
    }

    //转账
   public function transfer()
    {

        $validate = new Validate([
            'wallet_id'     => 'require',
            'address'     => 'require',
            'amount'    => 'require|number|between:0.00000001,1000000',
            'to_address'     => 'require',
            'transaction_no'     => 'require',
        ]);

        $validate->message([
            'wallet_id.require'  => 'wallet_id不能为空!',
            'address.require'  => 'address不能为空!',
            'amount.require'  => '转账数量不能为空!',    
            'amount.number'  => '数量必须为数值!',
            'amount.between'  => '数量必须大于0.00000001小于1000000!',
            'to_address.require'  => '转账地址不能为空!',
            'transaction_no.require'  => 'tx_no不能为空!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $wallet_id = $data['wallet_id']; 
        $address = $data['address']; 
        $amount =  $data['amount']; 
        $transaction_no =  $data['transaction_no']; 
        if(isset($data['memo'])&&!empty($data['memo'])){
            $memo =  $data['memo'];
            $where_memo = "memo = $memo";
        }else{
            $memo = "";
            $where_memo = "";
        }
        
        //检测transaction_no是否重复
        $count = Db::name('transfer_log')->where('transaction_no', $transaction_no)->count();
        if($count > 0){
             $this->error('transaction_no is existed');           
        }
        //获取钱包余额    
        $walletData = Db::name('wallet')
        ->field("chain_balance,coin_symbol,address,turnout_status")
        ->where('id', $wallet_id)
        ->where('address', $address)
        ->find();
        if(empty($walletData)){
            $this->error2('wallet not exists');
        }
        if($walletData['turnout_status']!=1){
            $this->error('wallet is locked');
        }        
        $from_address = $walletData['address'];
        $coin_symbol = $walletData['coin_symbol'];
        $balance = floatval($walletData['chain_balance']);
        $amount = floatval($amount);
        $to_address =  $data['to_address'];

        $to_address_data =  Db::name('wallet')
        ->field("id")
        ->where('coin_symbol', $coin_symbol)
        ->where('address', $to_address)
        ->where($where_memo)
        ->find();

        if($to_address_data ){
            $transaction_type = 2;
            $to_wallet_id = $to_address_data['id'];
        }
        else{
            $transaction_type = 1;
            $to_wallet_id = 0;
        } 
        if($wallet_id == $to_wallet_id ){//if($address == $to_address){
            $this->error("transfer wallet can't be same");
        }   
        //写入转账记录
        if($balance < $amount){      
           //$this->error("balance is insufficient");
        }
        //开始事务处理
        Db::startTrans();

        //先扣掉余额
        //$result = Db::name('wallet')
        //->where('id', $wallet_id)
        //->setDec('chain_balance',$amount);
        //if(!$result){
        //    Db::rollback();
        //    $this->error('tansfer submit failed(#1)');          
        //}        

        $insert_data = array();
        $insert_data['wallet_id'] = $wallet_id ;
        $insert_data['transaction_no'] = $transaction_no ;     
        $insert_data['to_wallet_id'] =  $to_wallet_id;   
        $insert_data['type'] =  $transaction_type ; 
        $insert_data['coin_symbol'] =  $coin_symbol;
        $insert_data['from_address'] =  $from_address;
        $insert_data['to_address'] =  $to_address;
        $insert_data['to_memo'] =  $memo;

        $insert_data['amount'] =  -$amount;
        $insert_data['amount_before'] =   $balance;
        $insert_data['log_time'] =  time();
        $insert_data['memo'] ='';
        $insert_data['transfer_status'] =  0;

        //读取系统配置 是否自动审批
        $sys_config = cmf_get_option("sys_config");
        if($transaction_type == 1){
            if(!empty($sys_config['turnout_audit1'])) 
                $insert_data['audit_status'] = 0;
            else
                $insert_data['audit_status'] = 1;            
        }
        if($transaction_type == 2){
            if(!empty($sys_config['turnout_audit2'])){
               // $insert_data['audit_status'] = 0;    //强制成功
                $insert_data['audit_status'] = 1;
                $insert_data['transfer_status'] =  1;    
                $insert_data['tx_id'] =  time();            
            }else{
                $insert_data['audit_status'] = 1;
                $insert_data['transfer_status'] =  1;  
                $insert_data['tx_id'] = time();        
            }
        }    
        $result = Db::name('transfer_log')->insertGetId($insert_data);  
        if($result){
            Db::commit();

            //回调
            if($insert_data['transfer_status'] == 1){
   

                $p = array();     
                $p['transaction_id'] =  intval($result);
                $p['notify_type'] = "payment";
                $task_data['params'] = json_encode($p);
                $task_data['task_name'] = "notify_url";
                $task_data['wallet_id'] = $to_wallet_id;
                $task_data['schedule_time'] =0;
                Db::name('cron')->insert($task_data);   

                $p = array();     
                $p['transaction_id'] =  intval($result);
                $p['notify_type'] = "confirm";
                $task_data['params'] = json_encode($p);
                $task_data['task_name'] = "notify_url";
                $task_data['wallet_id'] = $wallet_id;
                $task_data['schedule_time'] =1;
                Db::name('cron')->insert($task_data);   
            }

            $return_data['transaction_id'] =  intval($result);
            $return_data['transaction_no'] = $transaction_no;
            $return_data['wallet_id'] =  intval($wallet_id);
            $return_data['transaction_type'] =  $transaction_type;
            $return_data['to_wallet_id'] =  $to_wallet_id;
            $this->success('success',$return_data);             
        }else{
            Db::rollback();
            $this->error('tansfer submit failed(#2)！');          
        }
   
    }   

  //单条转账记录
   public function transaction_info()
    {

        $validate = new Validate([
            'transaction_id'     => 'require',
        ]);

        $validate->message([
            'transaction_id.require'  => 'transaction_id不能为空!',
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $transaction_id = $data['transaction_id'];
      
        //获取记录    
        $fieldStr = "id as transaction_id,type as transaction_type,transaction_no,coin_symbol,wallet_id,to_wallet_id,from_address,to_address,amount,log_time,tx_id,transfer_status,audit_status";

        $transaction = Db::name('transfer_log')
        ->field($fieldStr)
        ->where('id', $transaction_id)
        ->find();

        if(empty($transaction)){
            $this->error2("transaction not exists");
        }

        $transaction['amount'] =  sprintf("%01.5f",$transaction['amount']);
    
        $this->success('success',$transaction);
    }   
  //单条转账记录
   public function transaction_info2()
    {

        $validate = new Validate([
            'transaction_no'     => 'require',
        ]);

        $validate->message([
            'transaction_no.require'  => 'transaction_no不能为空!',
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $transaction_no = $data['transaction_no'];
      
        //获取记录    
        $fieldStr = "id as transaction_id,type as transaction_type,transaction_no,coin_symbol,wallet_id,to_wallet_id,from_address,to_address,amount,log_time,tx_id,transfer_status,audit_status";

        $transaction = Db::name('transfer_log')
        ->field($fieldStr)
        ->where('transaction_no', $transaction_no)
        ->find();

        if(empty($transaction)){
            $this->error2("transaction not exists");
        }
        $transaction['amount'] =  sprintf("%01.5f",$transaction['amount']);
    
        $this->success('success',$transaction);
    }   
    //转账记录
   public function transactions()
    {

        $validate = new Validate([
            'wallet_id'     => 'require',
            'limit_begin'    => 'integer',
            'limit_end'     => 'integer',
        ]);

        $validate->message([
            'wallet_id.require'  => 'wallet_id不能为空!',
            'transaction_type.integer'  => 'transaction_type必须为整数!',
            'limit_begin.integer'  => 'limit_begin必须为整数!',
            'limit_end.integer'  => 'limit_end必须为整数!',
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $wallet_id = $data['wallet_id'];
        if(!empty($data['limit_begin'])){
            $limit_begin = $data['limit_begin'];
        }else{
            $limit_begin = 0;       
        }

        if(!empty($data['limit_end'])){
            $limit_end = $data['limit_end'];
        }else{
            $limit_end = 20;       
        }

        if(!empty($data['transaction_type'])){
            $ids = explode(",",$data['transaction_type']);
        }else{
            $ids = ['1','2','3','4'];
        }

        //获取记录    
        $fieldStr = "id as transaction_id,type as transaction_type,transaction_no,coin_symbol,wallet_id,to_wallet_id,from_address,to_address,amount,log_time,tx_id,transfer_status,audit_status";

        $total_count = Db::name('transfer_log')
        ->where('type','in',$ids)
        ->where('wallet_id', $wallet_id)
        ->order('id desc')
        ->count();

        $transactions = Db::name('transfer_log')
        ->field($fieldStr)
        ->where('type','in',$ids)
        ->where('wallet_id', $wallet_id)
        ->order('id desc')
        ->limit($limit_begin,$limit_end)
        ->select()->toArray();
        foreach ($transactions as $key => &$value) {
            $value['amount'] =  sprintf("%01.5f",$value['amount']);
        }     
        $response['total_count'] = $total_count;   
        $response['count'] = count($transactions);
        $response['list'] = $transactions;
        $this->success('success',$response);
    }   
}
