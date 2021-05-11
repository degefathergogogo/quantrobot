<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\ExchangeModel;
/**
 * Class TransferLogController 转账记录
 * @package app\admin\controller
 */
class ChainLogController extends AdminBaseController
{
    public function index(){
        $size=20;
        $where=[];
        $where2='';
        $where3='';

        $requ= request()->param();
        !empty($requ['tx_id']) ? $where['a.tx_id'] = $requ['tx_id'] : '';        
        !empty($requ['transfer_id']) ? $where['b.id'] = $requ['transfer_id'] : '';  
        !empty($requ['coin_symbol']) ? $where['a.coin_symbol'] = $requ['coin_symbol'] : '';
        
        if(!empty($requ['start_time'])){
            $where2 = "a.log_time >= ".strtotime($requ['start_time']);
        } 

        if(!empty($requ['end_time'])){
            $where3 = "a.log_time < ".strtotime($requ['end_time']);
        }         

        $transfer_status = [
            "-1" => "<font color='#ff0000'>转账失败</font>",
            "0"  => "",
            "1"  => "<font color='#008B45'>转账成功</font>",
        ];

        $data=  
        Db::name('chain_log')
        ->alias('a')
        ->join(config('database.prefix').'transfer_log b',"a.tx_id = b.tx_id")
        ->field('a.*,b.id as transfer_id')
        ->where($where)
        ->where($where2)
        ->where($where3)
        ->order("id desc")
        ->paginate($size , false, [  'query' =>request()->param()  ]    );

        $total_fee =  Db::name('chain_log')
        ->alias('a')
        ->join(config('database.prefix').'transfer_log b',"a.tx_id = b.tx_id")
        ->field('a.*,b.id as transfer_id')
        ->where($where)
        ->where($where2)
        ->where($where3)
        ->sum('a.fee'); 

        $this->assign('request', $requ ); 
        $this->assign('transfer_status', $transfer_status );
        $this->assign('datas', $data->items() );
        $this->assign('num', $data->total());
        $this->assign('page',$data->render() );
        $this->assign('total_fee',$total_fee);

        return $this->fetch();
    }
}