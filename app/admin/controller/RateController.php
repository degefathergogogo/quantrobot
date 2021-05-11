<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\ExchangeModel;
/**
 * Class RateController 汇率管理
 * @package app\admin\controller
 */
class RateController extends AdminBaseController
{
    public function index(){


        $data=  Db::name('rate')->select()->toArray();

        $this->assign('datas', $data );
     
    
        return $this->fetch();
    }
    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result    = Db::name('rate')->where('id',$id)->delete();
        if ($result === false) {
            $this->error('删除失败，请重试！',url("rate/index"));
        }else{
            $this->success('删除成功！',url("rate/index"));
        }
    }
}