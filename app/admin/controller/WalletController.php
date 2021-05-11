<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\ExchangeModel;
/**
 * Class WalletController 钱包
 * @package app\admin\controller
 */
class WalletController extends AdminBaseController
{
    public function btc(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'BTC'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['address']) ? $where['address'] = $requ['address'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];


        $pay_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>统一转出账户</font>",
        ];

        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->sum('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('pay_status', $pay_status);     
        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('fee_status', $fee_status);
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
        return $this->fetch();
    }


    public function vds(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'VDS'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['address']) ? $where['address'] = $requ['address'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];


        $pay_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>统一转出账户</font>",
        ];


        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->sum('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('pay_status', $pay_status);     
        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('fee_status', $fee_status);
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
        return $this->fetch();
    }

    public function ltc(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'LTC'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['address']) ? $where['address'] = $requ['address'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];


        $pay_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>统一转出账户</font>",
        ];

        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->sum('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('pay_status', $pay_status);     
        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('fee_status', $fee_status);
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
        return $this->fetch();
    }

    public function bch(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'BCH'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['address']) ? $where['address'] = $requ['address'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];


        $pay_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>统一转出账户</font>",
        ];

        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->sum('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('pay_status', $pay_status);     
        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('fee_status', $fee_status);
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
        return $this->fetch();
    }

    public function eth(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'ETH'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['address']) ? $where['address'] = $requ['address'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];


        $pay_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>统一转出账户</font>",
        ];

        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->sum('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('pay_status', $pay_status);     
        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('fee_status', $fee_status);
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
    
        return $this->fetch();
    }
    
    public function trx(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'TRX'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['address']) ? $where['address'] = $requ['address'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];


        $pay_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>统一转出账户</font>",
        ];

        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->sum('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('pay_status', $pay_status);     
        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('fee_status', $fee_status);
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
    
        return $this->fetch();
    }

    public function etz(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'ETZ'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['address']) ? $where['address'] = $requ['address'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];


        $pay_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>统一转出账户</font>",
        ];

        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->sum('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('pay_status', $pay_status);     
        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('fee_status', $fee_status);
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
    
        return $this->fetch();
    }  



   public function etc(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'ETC'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['address']) ? $where['address'] = $requ['address'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];


        $pay_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>统一转出账户</font>",
        ];

        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->sum('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('pay_status', $pay_status);     
        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('fee_status', $fee_status);
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
    
        return $this->fetch();
    }  
    public function usdt(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'USDT'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['address']) ? $where['address'] = $requ['address'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $pay_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>统一转出账户</font>",
        ];

        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->sum('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('pay_status', $pay_status);   
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
   
        return $this->fetch();
    }      


    public function token(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_type'] = 'token'; 
        !empty($requ['coin_symbol']) ? $where['a.coin_symbol'] = $requ['coin_symbol'] : '';
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['address']) ? $where['address'] = $requ['address'] : '';
        !empty($requ['wallet_id']) ? $where['a.id'] = $requ['wallet_id'] : '';

        if(empty($requ['coin_symbol'])){
            $requ['coin_symbol'] = '';
        }
        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];



        $pay_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>统一转出账户</font>",
        ];

        $tokens=  
        Db::name('Coin')
        ->where("coin_type","token")
        ->select();


        $total_balance =  Db::name('Wallet')
        ->alias('a')
        ->join(config('database.prefix').'coin b',"a.coin_symbol = b.coin_symbol","LEFT")
        ->where($where)
        ->where("status",1)
        ->sum('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->alias('a')
        ->join(config('database.prefix').'coin b',"a.coin_symbol = b.coin_symbol","LEFT")
        ->field('a.*,b.coin_type')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('request', $requ ); 
        $this->assign('tokens', $tokens);
        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('pay_status', $pay_status);     
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
   
        return $this->fetch();
    }      


   public function eos(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'EOS'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['memo']) ? $where['memo'] = $requ['memo'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];

        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->max('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('fee_status', $fee_status);
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
    
        return $this->fetch();
    }  

   public function xrp(){
        $size=20;
        $where=[];
        $requ= request()->param();

        $where['coin_symbol'] = 'XRP'; 
        !empty($requ['uuid']) ? $where['uuid'] = $requ['uuid'] : '';
        !empty($requ['memo']) ? $where['memo'] = $requ['memo'] : '';
        !empty($requ['wallet_id']) ? $where['id'] = $requ['wallet_id'] : '';

        $turnout_status = [
            "0"  => "<font color='#ff0000'>禁止</font>",
            "1"  => "<font color='#008B45'>正常</font>",
        ];

        $fee_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>手续费账户</font>",
        ];

        $depot_status = [
            "0" => "<font color='#000000'>否</font>",
            "1"  => "<font color='#008B45'>汇总仓库账号</font>",
        ];

        $total_balance =  Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->max('chain_balance'); 

        $data=  
        Db::name('Wallet')
        ->where($where)
        ->where("status",1)
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $this->assign('turnout_status', $turnout_status);
        $this->assign('depot_status', $depot_status);
        $this->assign('fee_status', $fee_status);
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_balance',$total_balance);
    
        return $this->fetch();
    }  

    public function enable_turnout(){

        $id     = $this->request->param('id');
        $result = Db::name('wallet')->where(['id' => $id])->find();
        if ($result) {
            $update_data = array();
            $update_data['turnout_status'] = 1;
            $res = Db::name('wallet')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("转账状态启用成功！");
            }else{
                $this->error("转账状态启用失败！");  
            }

        }
        
    }


    public function disable_turnout(){

        $id     = $this->request->param('id');
        $result = Db::name('wallet')->where(['id' => $id])->find();
        if ($result) {
            $update_data = array();
            $update_data['turnout_status'] = 0;
            $res = Db::name('wallet')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("转账状态禁用成功！");
            }else{
                $this->error("转账状态禁用失败！");  
            }

        }
        
    }        


 public function enable_depot(){

        $id     = $this->request->param('id');
        $result = Db::name('wallet')->where(['id' => $id])->find();
        if ($result) {
            $coin_symbol = $result['coin_symbol'];
            $update_data = array();
            $update_data['depot_status'] = 0;            
            $res = Db::name('wallet')->where(['depot_status' =>1,'coin_symbol'=>$coin_symbol])->update($update_data);
            $update_data = array();
            $update_data['depot_status'] = 1;
            $res = Db::name('wallet')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("仓库账号设置成功！");
            }else{
                $this->error("仓库账号设置失败！");  
            }

        }
        
    }


    public function disable_depot(){

        $id     = $this->request->param('id');
        $result = Db::name('wallet')->where(['id' => $id])->find();
        if ($result) {
            $coin_symbol = $result['coin_symbol'];            
            $update_data = array();
            $update_data['depot_status'] = 0;
            $res = Db::name('wallet')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("仓库账号禁用成功！");
            }else{
                $this->error("仓库账号禁用失败！");  
            }

        }
        
    }    



 public function enable_fee(){

        $id     = $this->request->param('id');
        $result = Db::name('wallet')->where(['id' => $id])->find();
        if ($result) {
            $coin_symbol = $result['coin_symbol'];            
            $update_data = array();
            $update_data['fee_status'] = 0;            
            $res = Db::name('wallet')->where(['fee_status' =>1,'coin_symbol'=>$coin_symbol])->update($update_data);
            $update_data = array();
            $update_data['fee_status'] = 1;
            $res = Db::name('wallet')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("手续费钱包设置成功！");
            }else{
                $this->error("手续费钱包设置失败！");  
            }

        }
        
    }


    public function disable_fee(){

        $id     = $this->request->param('id');
        $result = Db::name('wallet')->where(['id' => $id])->find();
        if ($result) {
            $coin_symbol = $result['coin_symbol'];  
            $update_data = array();
            $update_data['fee_status'] = 0;
            $res = Db::name('wallet')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("仓手续费钱包禁用成功！");
            }else{
                $this->error("手续费钱包禁用失败！");  
            }

        }
        
    }    


 public function enable_pay(){

        $id     = $this->request->param('id');
        $result = Db::name('wallet')->where(['id' => $id])->find();
        if ($result) {
            $coin_symbol = $result['coin_symbol'];            
            $update_data = array();
            $update_data['pay_status'] = 0;            
            $res = Db::name('wallet')->where(['pay_status' =>1,'coin_symbol'=>$coin_symbol])->update($update_data);
            $update_data = array();
            $update_data['pay_status'] = 1;
            $res = Db::name('wallet')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("统一出款钱包设置成功！");
            }else{
                $this->error("统一出款钱包设置失败！");  
            }

        }
        
    }


    public function disable_pay(){

        $id     = $this->request->param('id');
        $result = Db::name('wallet')->where(['id' => $id])->find();
        if ($result) {
            $coin_symbol = $result['coin_symbol'];  
            $update_data = array();
            $update_data['pay_status'] = 0;
            $res = Db::name('wallet')->where(['id' => $id])->update($update_data);
            if ($res) {
                $this->success("统一出款钱包禁用成功！");
            }else{
                $this->error("统一出款钱包禁用失败！");  
            }

        }
        
    }    

    public function collect(){
        if(isset($this->request->param()['ids'])){
           $ids  = $this->request->param()['ids'];    
        }
        if(empty($ids)){
            $this->error('请选中要汇总的钱包');
        }       
        foreach ($ids as $key => $value) {
            if($value=='on'){
                    $wallet_id = $key; 
                    //获取钱包余额   

                    $walletData = Db::name('wallet')
                    ->field("chain_balance,coin_symbol,address")
                    ->where('id', $wallet_id)
                    ->where('turnout_status', 1)
                    ->find();
                    if(!empty($walletData)){

                        $from_address = $walletData['address'];
                        $coin_symbol = $walletData['coin_symbol'];
                        $balance = floatval($walletData['chain_balance']);
                        $amount = floatval($balance);

                        if($balance<=0){
                            continue;
                        }
                        
                        //检测是否已经有汇总任务没有执行
                        $transfer_log_check =  Db::name('transfer_log')
                        ->where('wallet_id', $wallet_id)
                        ->where('transfer_status',0)
                        ->where('type',4)
                        ->count();
                        if($transfer_log_check>0){
                            continue;
                        }        

                        $to_address_data =  Db::name('wallet')
                        ->field("id,address")
                        ->where('coin_symbol', $coin_symbol)
                        ->where('depot_status',1)
                        ->find();

                        if(empty($to_address_data)){
                            $this->error('没有汇总钱包');
                        }

                        $to_address =  $to_address_data['address'];
                        $transaction_type = 4;
                        $to_wallet_id = $to_address_data['id'];



                        //开始事务处理
                        Db::startTrans();

                        $insert_data = array();
                        $insert_data['wallet_id'] = $wallet_id ;     
                        $insert_data['to_wallet_id'] =  $to_wallet_id;   
                        $insert_data['type'] =  $transaction_type ; 
                        $insert_data['coin_symbol'] =  $coin_symbol;
                        $insert_data['from_address'] =  $from_address;
                        $insert_data['to_address'] =  $to_address;
                        $insert_data['amount'] =  -$amount;
                        $insert_data['amount_before'] =   $balance;
                        $insert_data['log_time'] =  time();
                        $insert_data['memo'] ='';
                        $insert_data['transfer_status'] =  0;
                        $insert_data['audit_status'] = 1;   


                        $result = Db::name('transfer_log')->insertGetId($insert_data);  
                        if($result){
                            Db::commit();
                        }else{
                            Db::rollback();
                            $this->error('tansfer submit failed！');          
                        }                        
                    }
                    
            }
        }   

        $this->success('汇总成功');             
 
    }  

    public function update(){

        $id     = $this->request->param('id');
        $result = Db::name('wallet')->where(['id' => $id])->find();
        if ($result) {
            $p['wallet_id'] = $id;
            $task_data['params'] = json_encode($p);
            $task_data['task_name'] = "update_wallet_balance";
            $task_data['wallet_id'] = $id ;
            $task_data['schedule_time'] =0;
            $res = Db::name('cron')->insert($task_data);   
            if ($res) {
                $this->success("更新余额提交成功！");
            }else{
                $this->error("更新余额提交失败！");  
            }

        }else{
            $this->error("钱包不存在！");      
        }
        
    }             
}