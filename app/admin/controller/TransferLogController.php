<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\ExchangeModel;
/**
 * Class TransferLogController 转账记录
 * @package app\admin\controller
 */
class TransferLogController extends AdminBaseController
{
    public function payment(){
        $size=20;
        $where=[];
        $where2='';
        $where3='';

        $requ= request()->param();
        !empty($requ['wallet_id']) ? $where['wallet_id'] = $requ['wallet_id'] : '';
        !empty($requ['from_address']) ? $where['from_address'] = $requ['from_address'] : '';
        !empty($requ['to_address']) ? $where['to_address'] = $requ['to_address'] : '';
        !empty($requ['id']) ? $where['id'] = $requ['id'] : '';        
        !empty($requ['transfer_status']) ? $where['transfer_status'] = $requ['transfer_status'] :'';
        !empty($requ['audit_status']) ? $where['audit_status'] = $requ['audit_status'] :'';
        !empty($requ['coin_symbol']) ? $where['coin_symbol'] = $requ['coin_symbol'] : '';


        if(!empty($requ['start_time'])){
            $where2 = "log_time >= ".strtotime($requ['start_time']);
        } 

        if(!empty($requ['end_time'])){
            $where3 = "log_time < ".strtotime($requ['end_time']);
        }         

        $transfer_status = [
            "-1" => "<font color='#ff0000'>转账失败</font>",
            "0"  => "",
            "1"  => "<font color='#008B45'>转账成功</font>",
        ];

        if(isset ($requ['transfer_status'] )){
            if(  $requ['transfer_status'] === "0"  ){
                $where['transfer_status'] = 0;
            }
        }
        if(isset ($requ['audit_status'] )){
            if(  $requ['audit_status'] === "0"  ){
                $where['audit_status'] = 0;
            }
        }      
        $transfer_status = [
            "-1" => "<font color='#ff0000'>处理失败</font>",
            "0"  => "等待处理",
            "1"  => "<font color='#008B45'>转账成功</font>",
            "2"  => "转账中",
        ];

        $audit_status = [
            "-1" => "<font color='#ff0000'>审批拒绝</font>",
            "0"  => "等待审批",
            "1"  => "<font color='#008B45'>审批成功</font>",
        ];

        $notify_status = [
            "-1" => "<font color='#ff0000'>通知失败</font>",
            "0"  => "等待通知",
            "1"  => "<font color='#008B45'>通知成功</font>",
        ];


        $data=  
        Db::name('transfer_log')
        ->where($where)
        ->where($where2)
        ->where($where3)                
        ->where("type",3)        
        ->order("id desc")
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $total_recharge = Db::name('transfer_log')
        ->where($where)
        ->where($where2)
        ->where($where3)          
        ->where("type",3)    
        ->sum('amount'); 
        
        $this->assign('total_recharge',$total_recharge);

        $this->assign('request', $requ ); 
        $this->assign('transfer_status_arr', $transfer_status );
        $this->assign('audit_status_arr', $audit_status );    
        $this->assign('notify_status_arr', $notify_status );       
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
    
        return $this->fetch();
    }
 

    public function turnout_in(){
        $size=20;
        $where=[];
        $requ= request()->param();
        !empty($requ['wallet_id']) ? $where['wallet_id'] = $requ['wallet_id'] : '';
        !empty($requ['from_address']) ? $where['from_address'] = $requ['from_address'] : '';
        !empty($requ['to_address']) ? $where['to_address'] = $requ['to_address'] : '';
        !empty($requ['id']) ? $where['id'] = $requ['id'] : '';
        !empty($requ['transaction_no']) ? $where['transaction_no'] = $requ['transaction_no'] : '';
        !empty($requ['transfer_status']) ? $where['transfer_status'] = $requ['transfer_status'] :'';
        !empty($requ['audit_status']) ? $where['audit_status'] = $requ['audit_status'] :'';
        if(isset ($requ['transfer_status'] )){
            if(  $requ['transfer_status'] === "0"  ){
                $where['transfer_status'] = 0;
            }
        }
        if(isset ($requ['audit_status'] )){
            if(  $requ['audit_status'] === "0"  ){
                $where['audit_status'] = 0;
            }
        }      
        $transfer_status = [
            "-1" => "<font color='#ff0000'>处理失败</font>",
            "0"  => "等待处理",
            "1"  => "<font color='#008B45'>转账成功</font>",
            "2"  => "转账中",
        ];

        $audit_status = [
            "-1" => "<font color='#ff0000'>审批拒绝</font>",
            "0"  => "等待审批",
            "1"  => "<font color='#008B45'>审批成功</font>",
        ];

        $notify_status = [
            "-1" => "<font color='#ff0000'>通知失败</font>",
            "0"  => "等待通知",
            "1"  => "<font color='#008B45'>通知成功</font>",
        ];


        $data=  
        Db::name('transfer_log')
        ->where($where)
        ->where("type",2)        
        ->order("id desc")
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('request', $requ ); 
        $this->assign('transfer_status_arr', $transfer_status );
        $this->assign('audit_status_arr', $audit_status );    
        $this->assign('notify_status_arr', $notify_status );     
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
    
        return $this->fetch();
    }

    public function turnout_out(){
        $size=20;
        $where=[];
        $requ= request()->param();
        !empty($requ['wallet_id']) ? $where['wallet_id'] = $requ['wallet_id'] : '';
        !empty($requ['from_address']) ? $where['from_address'] = $requ['from_address'] : '';
        !empty($requ['to_address']) ? $where['to_address'] = $requ['to_address'] : '';
        !empty($requ['id']) ? $where['id'] = $requ['id'] : '';
        !empty($requ['transaction_no']) ? $where['transaction_no'] = $requ['transaction_no'] : '';
        !empty($requ['transfer_status']) ? $where['transfer_status'] = $requ['transfer_status'] :'';
        !empty($requ['audit_status']) ? $where['audit_status'] = $requ['audit_status'] :'';
        if(isset ($requ['transfer_status'] )){
            if(  $requ['transfer_status'] === "0"  ){
                $where['transfer_status'] = 0;
            }
        }
        if(isset ($requ['audit_status'] )){
            if(  $requ['audit_status'] === "0"  ){
                $where['audit_status'] = 0;
            }
        }      
        $transfer_status = [
            "-1" => "<font color='#ff0000'>处理失败</font>",
            "0"  => "等待处理",
            "1"  => "<font color='#008B45'>转账成功</font>",
            "2"  => "转账中",
        ];

        $audit_status = [
            "-1" => "<font color='#ff0000'>审批拒绝</font>",
            "0"  => "等待审批",
            "1"  => "<font color='#008B45'>审批成功</font>",
        ];

        $notify_status = [
            "-1" => "<font color='#ff0000'>通知失败</font>",
            "0"  => "等待通知",
            "1"  => "<font color='#008B45'>通知成功</font>",
        ];

        $data=  
        Db::name('transfer_log')
        ->where($where)
        ->where("type",1)        
        ->order("id desc")
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('request', $requ ); 
        $this->assign('transfer_status_arr', $transfer_status );
        $this->assign('audit_status_arr', $audit_status );  
        $this->assign('notify_status_arr', $notify_status );         
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
    
        return $this->fetch();
    }    


    public function huizong(){
        $size=20;
        $where=[];
        $requ= request()->param();
        !empty($requ['wallet_id']) ? $where['wallet_id'] = $requ['wallet_id'] : '';
        !empty($requ['from_address']) ? $where['from_address'] = $requ['from_address'] : '';
        !empty($requ['to_address']) ? $where['to_address'] = $requ['to_address'] : '';
        !empty($requ['id']) ? $where['id'] = $requ['id'] : '';        
        !empty($requ['transfer_status']) ? $where['transfer_status'] = $requ['transfer_status'] :'';
        !empty($requ['audit_status']) ? $where['audit_status'] = $requ['audit_status'] :'';
        if(isset ($requ['transfer_status'] )){
            if(  $requ['transfer_status'] === "0"  ){
                $where['transfer_status'] = 0;
            }
        }
        if(isset ($requ['audit_status'] )){
            if(  $requ['audit_status'] === "0"  ){
                $where['audit_status'] = 0;
            }
        }      
        $transfer_status = [
            "-1" => "<font color='#ff0000'>处理失败</font>",
            "0"  => "等待处理",
            "1"  => "<font color='#008B45'>转账成功</font>",
            "2"  => "转账中",
        ];

        $audit_status = [
            "-1" => "<font color='#ff0000'>审批拒绝</font>",
            "0"  => "等待审批",
            "1"  => "<font color='#008B45'>审批成功</font>",
        ];

        $notify_status = [
            "-1" => "<font color='#ff0000'>通知失败</font>",
            "0"  => "等待通知",
            "1"  => "<font color='#008B45'>通知成功</font>",
        ];

        $data=  
        Db::name('transfer_log')
        ->where($where)
        ->where("type",4)        
        ->order("id desc")
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('request', $requ ); 
        $this->assign('transfer_status_arr', $transfer_status );
        $this->assign('audit_status_arr', $audit_status );   
        $this->assign('notify_status_arr', $notify_status );        
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
    
        return $this->fetch();
    }      



 public function fee(){
        $size=20;
        $where=[];
        $requ= request()->param();
        !empty($requ['wallet_id']) ? $where['wallet_id'] = $requ['wallet_id'] : '';
        !empty($requ['from_address']) ? $where['from_address'] = $requ['from_address'] : '';
        !empty($requ['to_address']) ? $where['to_address'] = $requ['to_address'] : '';
        !empty($requ['id']) ? $where['id'] = $requ['id'] : '';
        !empty($requ['transaction_no']) ? $where['transaction_no'] = $requ['transaction_no'] : '';
        !empty($requ['transfer_status']) ? $where['transfer_status'] = $requ['transfer_status'] :'';
        !empty($requ['audit_status']) ? $where['audit_status'] = $requ['audit_status'] :'';
        if(isset ($requ['transfer_status'] )){
            if(  $requ['transfer_status'] === "0"  ){
                $where['transfer_status'] = 0;
            }
        }
        if(isset ($requ['audit_status'] )){
            if(  $requ['audit_status'] === "0"  ){
                $where['audit_status'] = 0;
            }
        }      
        $transfer_status = [
            "-1" => "<font color='#ff0000'>处理失败</font>",
            "0"  => "等待处理",
            "1"  => "<font color='#008B45'>转账成功</font>",
            "2"  => "转账中",
        ];

        $audit_status = [
            "-1" => "<font color='#ff0000'>审批拒绝</font>",
            "0"  => "等待审批",
            "1"  => "<font color='#008B45'>审批成功</font>",
        ];

        $notify_status = [
            "-1" => "<font color='#ff0000'>通知失败</font>",
            "0"  => "等待通知",
            "1"  => "<font color='#008B45'>通知成功</font>",
        ];

        $data=  
        Db::name('transfer_log')
        ->where($where)
        ->where("type",5)        
        ->order("id desc")
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('request', $requ ); 
        $this->assign('transfer_status_arr', $transfer_status );
        $this->assign('audit_status_arr', $audit_status );  
        $this->assign('notify_status_arr', $notify_status );         
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
    
        return $this->fetch();
    }    



     public function summary(){

    
        return $this->fetch();
    }    

    public function approve(){

        $id     = $this->request->param('id');
        $result = Db::name('transfer_log')->where(['id' => $id])->find();
        if ($result) {
            $update_data = array();
            $update_data['audit_status'] = 1;
            $res = Db::name('transfer_log')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("审批通过成功！");
            }else{
                $this->error("审批通过失败！");  
            }

        }
        
    }


    public function transfer_btc_fee(){

        //手续费查询
        $fieldStr = 'coin_type,parent_coin,min_fee';
        $coin_data = Db::name('coin')
        ->field($fieldStr)
        ->where('coin_symbol', 'USDT')
        ->find();

        $fee = $coin_data['min_fee'];

        //找到手续费账户
        $fee_wallet_data = Db::name('wallet')
        ->field("id,chain_balance,address,memo,seed")
        ->where('coin_symbol',"BTC")
        ->where('fee_status',1)
        ->find();       

        if(!$fee_wallet_data){
            $this->error('BTC手续费账户不存在');
        }else{
            $fee_from_address = $fee_wallet_data['address'];
            $fee_wallet_id = $fee_wallet_data['id'];
            $fee_from_balance =$fee_wallet_data['chain_balance'];
        } 

        if($fee_from_balance<$fee ){
           $this->error('BTC手续费账户额度不足');
        }

        //找到汇总订单中手续费不足的订单
        $where['fee_status'] = 0;
        $where['transfer_status'] = 0;
        $where['audit_status'] = 1;
        $where['coin_symbol'] = 'USDT';

        $wallets = Db::name('transfer_log')
        ->where($where)
        ->where("type",4)        
        ->select()->toArray();

        foreach ($wallets as $key => $value) {
            $log_id =  $value['id'];
            $wallet_id = $value['wallet_id'];
            //获取转出钱包余额和手续费余额   
            $walletData = Db::name('wallet')
            ->field("chain_balance,chain_balance_fee,coin_symbol,address")
            ->where('id', $wallet_id)
            ->find();

            if(!empty($walletData)){

                $balance_fee = floatval($walletData['chain_balance_fee']);
                $balance = floatval($walletData['chain_balance']);
                $amount = $fee;
                $to_address = $walletData['address'];

                if($balance_fee < $fee){

                    $amount = $fee - $balance_fee;
                            
                    //开始事务处理
                    Db::startTrans();

                    $insert_data = array();
                    $insert_data['wallet_id'] = $fee_wallet_id ;     
                    $insert_data['to_wallet_id'] =  $wallet_id;   
                    $insert_data['type'] =  5 ; 
                    $insert_data['coin_symbol'] =  "BTC";
                    $insert_data['from_address'] =  $fee_from_address;
                    $insert_data['to_address'] =  $to_address;
                    $insert_data['amount'] =  -$amount;
                    $insert_data['amount_before'] =   $fee_from_balance;
                    $insert_data['log_time'] =  time();
                    $insert_data['memo'] ='';
                    $insert_data['transfer_status'] =  0;
                    $insert_data['audit_status'] = 1;

                    $result = Db::name('transfer_log')->insertGetId($insert_data);  
                    if($result){
                        $update_data = array();
                        $update_data['fee_status'] = 2;
                        Db::name('transfer_log')
                        ->where("id",$log_id) 
                        ->update($update_data);                          
                        Db::commit();
                    }else{
                        Db::rollback();
                        $this->error('tansfer fee submit failed！');          
                    }   

                }

            }
                    
           
        }   

        $this->success('手续费交易提交成功');             
 
    }  



    public function transfer_eth_fee(){

        //手续费查询
        $fieldStr = 'coin_type,parent_coin,min_fee,coin_symbol';
        $coin_data = Db::name('coin')
        ->field($fieldStr)
        ->where('parent_coin', 'ETH')
        ->where('coin_type', 'token')
        ->select();

        foreach ($coin_data as $key => $value) {
            $fee[$value['coin_symbol']] = $value['min_fee'];
        }
        
        //找到手续费账户
        $fee_wallet_data = Db::name('wallet')
        ->field("id,chain_balance,address,memo,seed")
        ->where('coin_symbol',"ETH")
        ->where('fee_status',1)
        ->find();       

        if(!$fee_wallet_data){
            $this->error('ETH手续费账户不存在');
        }else{
            $fee_from_address = $fee_wallet_data['address'];
            $fee_wallet_id = $fee_wallet_data['id'];
            $fee_from_balance =$fee_wallet_data['chain_balance'];
        } 

        if($fee_from_balance < max($fee)){
           $this->error('ETH手续费账户额度不足'.$fee_from_balance.'<'.max($fee));
        }

        //找到汇总订单中手续费不足的订单
        $where['fee_status'] = 0;
        $where['transfer_status'] = 0;
        $where['audit_status'] = 1;

        $token_list = [];
        $token_data = Db::name('coin')
        ->field("coin_symbol")
        ->where('parent_coin', "ETH")
        ->where('contract', 'neq','')
        ->select()->toArray();

        foreach ($token_data as $key => $value) {
            $token_list[] = $value['coin_symbol'];
        }


        $wallets = Db::name('transfer_log')
        ->where($where)
        ->where('coin_symbol','in',$token_list) //外部
        ->where("type",4)        
        ->select()->toArray();
        
        foreach ($wallets as $key => $value) {
            $log_id =  $value['id'];
            $wallet_id = $value['wallet_id'];
            //获取转出钱包余额和手续费余额   
            $walletData = Db::name('wallet')
            ->field("chain_balance,chain_balance_fee,coin_symbol,address")
            ->where('id', $wallet_id)
            ->find();

            if(!empty($walletData)){

                $balance_fee = floatval($walletData['chain_balance_fee']);
                $balance = floatval($walletData['chain_balance']);
                $amount = $fee[$walletData['coin_symbol']];
                $to_address = $walletData['address'];

                if($balance_fee < $fee[$walletData['coin_symbol']]){
                            
                    $amount = $fee[$walletData['coin_symbol']] - $balance_fee;
                    //开始事务处理
                    Db::startTrans();

                    $insert_data = array();
                    $insert_data['wallet_id'] = $fee_wallet_id ;     
                    $insert_data['to_wallet_id'] =  $wallet_id;   
                    $insert_data['type'] =  5 ; 
                    $insert_data['coin_symbol'] =  "ETH";
                    $insert_data['from_address'] =  $fee_from_address;
                    $insert_data['to_address'] =  $to_address;
                    $insert_data['amount'] =  -$amount;
                    $insert_data['amount_before'] =   $fee_from_balance;
                    $insert_data['log_time'] =  time();
                    $insert_data['memo'] ='';
                    $insert_data['transfer_status'] =  0;
                    $insert_data['audit_status'] = 1;

                    $result = Db::name('transfer_log')->insertGetId($insert_data);  
                    if($result){
                        $update_data = array();
                        $update_data['fee_status'] = 2;
                        Db::name('transfer_log')
                        ->where("id",$log_id) 
                        ->update($update_data);                                                  
                        Db::commit();
                    }else{
                        Db::rollback();
                        $this->error('tansfer fee submit failed！');          
                    }   

                }

            }
                    
           
        }   

        $this->success('手续费交易提交成功');
    }

    public function transfer_trx_fee(){

        //手续费查询
        $fieldStr = 'coin_type,parent_coin,min_fee,coin_symbol';
        $coin_data = Db::name('coin')
        ->field($fieldStr)
        ->where('parent_coin', 'TRX')
        ->where('coin_type', 'token')
        ->select();

        foreach ($coin_data as $key => $value) {
            $fee[$value['coin_symbol']] = $value['min_fee'];
        }
        
        //找到手续费账户
        $fee_wallet_data = Db::name('wallet')
        ->field("id,chain_balance,address,memo,seed")
        ->where('coin_symbol',"TRX")
        ->where('fee_status',1)
        ->find();       

        if(!$fee_wallet_data){
            $this->error('TRX手续费账户不存在');
        }else{
            $fee_from_address = $fee_wallet_data['address'];
            $fee_wallet_id = $fee_wallet_data['id'];
            $fee_from_balance =$fee_wallet_data['chain_balance'];
        } 

        if($fee_from_balance < max($fee)){
           $this->error('TRX手续费账户额度不足'.$fee_from_balance.'<'.max($fee));
        }

        //找到汇总订单中手续费不足的订单
        $where['fee_status'] = 0;
        $where['transfer_status'] = 0;
        $where['audit_status'] = 1;

        $token_list = [];
        $token_data = Db::name('coin')
        ->field("coin_symbol")
        ->where('parent_coin', "TRX")
        ->where('contract', 'neq','')
        ->select()->toArray();

        foreach ($token_data as $key => $value) {
            $token_list[] = $value['coin_symbol'];
        }


        $wallets = Db::name('transfer_log')
        ->where($where)
        ->where('coin_symbol','in',$token_list) //外部
        ->where("type",4)        
        ->select()->toArray();
        
        foreach ($wallets as $key => $value) {
            $log_id =  $value['id'];
            $wallet_id = $value['wallet_id'];
            //获取转出钱包余额和手续费余额   
            $walletData = Db::name('wallet')
            ->field("chain_balance,chain_balance_fee,coin_symbol,address")
            ->where('id', $wallet_id)
            ->find();

            if(!empty($walletData)){

                $balance_fee = floatval($walletData['chain_balance_fee']);
                $balance = floatval($walletData['chain_balance']);
                $amount = $fee[$walletData['coin_symbol']];
                $to_address = $walletData['address'];

                if($balance_fee < $fee[$walletData['coin_symbol']]){
                            
                    $amount = $fee[$walletData['coin_symbol']] - $balance_fee;
                    //开始事务处理
                    Db::startTrans();

                    $insert_data = array();
                    $insert_data['wallet_id'] = $fee_wallet_id ;     
                    $insert_data['to_wallet_id'] =  $wallet_id;   
                    $insert_data['type'] =  5 ; 
                    $insert_data['coin_symbol'] =  "TRX";
                    $insert_data['from_address'] =  $fee_from_address;
                    $insert_data['to_address'] =  $to_address;
                    $insert_data['amount'] =  -$amount;
                    $insert_data['amount_before'] =   $fee_from_balance;
                    $insert_data['log_time'] =  time();
                    $insert_data['memo'] ='';
                    $insert_data['transfer_status'] =  0;
                    $insert_data['audit_status'] = 1;

                    $result = Db::name('transfer_log')->insertGetId($insert_data);  
                    if($result){
                        $update_data = array();
                        $update_data['fee_status'] = 2;
                        Db::name('transfer_log')
                        ->where("id",$log_id) 
                        ->update($update_data);                                                  
                        Db::commit();
                    }else{
                        Db::rollback();
                        $this->error('tansfer fee submit failed！');          
                    }   

                }

            }
                    
           
        }   

        $this->success('手续费交易提交成功');             
 
    }

    public function reset(){

        $id     = $this->request->param('id');
        $result = Db::name('transfer_log')->where(['id' => $id,'transfer_status' => -1])->find();
        if ($result) {
            $update_data = array();
            $update_data['transfer_status'] = 0;
            $res = Db::name('transfer_log')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("重置交易状态成功！");
            }else{
                $this->error("重置交易状态失败！");  
            }

        }else{
            $this->error("error"); 
        }
        
    }

    public function refuse(){

        $id     = $this->request->param('id');
        $result = Db::name('transfer_log')->where(['id' => $id])->find();
        if ($result) {
            $update_data = array();
            $update_data['audit_status'] = -1;
            $res = Db::name('transfer_log')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("审批拒绝成功！");
            }else{
                $this->error("审批拒绝失败！");  
            }

        }
        
    }    

    public function notify(){

        $id     = $this->request->param('id');
        $result = Db::name('transfer_log')->where(['id' => $id])->find();
        if ($result) {

            if($result['type'] == 3){
                $notify_type = "payment";
            }elseif(($result['type'] == 1) || ($result['type'] == 2) ){
                $notify_type = "confirm";
            }elseif($result['type'] == 4){
                $notify_type = "collect";
            }else{
                $this->error("unkown transaction type ".$result['type']);    
            }
            $p['notify_type'] =$notify_type;
            $p['transaction_id'] = $id;
            $task_data['params'] = json_encode($p);
            $task_data['task_name'] = "notify_url";
            $task_data['wallet_id'] =$result['wallet_id'];
            $task_data['schedule_time'] =0;
            $res = Db::name('cron')->insert($task_data);   
            if ($res) {
                $this->success("补发通知任务成功！");
            }else{
                $this->error("补发通知任务失败！");  
            }

        }
        
    }       
}