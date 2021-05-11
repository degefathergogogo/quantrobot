<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\CoinModel;
/**
 * Class CoinsController 币种管理
 * @package app\admin\controller
 */
class CoinsController extends AdminBaseController
{

    public function index(){

      $collect_status = [
          "0"  => "<font color='#ff0000'>关闭</font>",
          "1"  => "<font color='#008B45'>开启</font>",
      ];

      $server= $_SERVER['HTTP_HOST'];
      $data=  Db::name('coin')->order('id asc')->select()->toArray();
      $this->assign('server', $server );
      $this->assign('datas', $data );
      $this->assign('collect_status', $collect_status);
      return $this->fetch();
    }

    function uptag(){
      if($_POST){
          $id= $this->request->param('id');
          $tag= $this->request->param('tag');
          $type= $this->request->param('type');

          $isdel= Db::name('coin')->where(['id'=>$id])->update([$tag=>$type]);
          if ($isdel!==false) {
                  echo "修改标签成功！";
              } else {
                  echo "修改标签失败！";
              }
      }
  } 

    public function listorderss() {
        if($_POST){
            foreach ($_POST['sort'] as $k =>$v){
                $status = Db::name('coin')->where(['id'=>$k])->update([
                    'sort'=>$v
                ]);
            }
            $this->success("排序更新成功！");
        }
    }

  public function add(){
    if($_POST){
        $data      = $this->request->param();
        // print_r($data);die;
        $CoinModel = new CoinModel();
        $result    = $CoinModel->allowField(true)->save($data);
        if ($result === false) {
            $this->error($CoinModel->getError());
        }

        $this->success("添加成功！", url("Coins/index"));
    }
    return $this->fetch();
}
public function edits()
{
    $server= $_SERVER['HTTP_HOST'];
    $id    = $this->request->param('id', 0, 'intval');
    $CoinModel = CoinModel::where('id',$id)->find();
    $this->assign('server', $server );
    $this->assign('coin_data', $CoinModel);
    return $this->fetch();
}
public function editpost()
{
    $data      = $this->request->param();

    $CoinModel = new CoinModel();
    $result    = $CoinModel->allowField(true)->isUpdate(true)->save($data);
    if ($result === false) {
        $this->error($CoinModel->getError());
    }
    $this->success("保存成功！", url("Coins/index"));
}


public function rpc_test($id){

    //检测币种是否存在
    $fieldStr = 'coin_symbol,coin_type,rpc_ip,rpc_port,rpc_user,rpc_pass';
    $coin_data = Db::name('coin')
    ->field($fieldStr)
    ->where('id', $id)
    ->find();
    if(empty($coin_data)){ //没有该币种
        $this->error('币种不存在');
    }
    if($coin_data['coin_type']=='token'){ //没有该币种
        $this->error('Token不支持RPC测试');
    }    
    $coin_symbol = $coin_data['coin_symbol'];
    $coin = strtolower($coin_symbol); 
    $vendor_name = "Chain.".$coin."rpc";
    Vendor($vendor_name);
    $class_name = "\\".$coin."rpc";
    //币种rpc配置
    $rpc_ip = $coin_data['rpc_ip'];
    $rpc_port =  $coin_data['rpc_port'];
    $rpc_user = $coin_data['rpc_user'];
    $rpc_pass = $coin_data['rpc_pass'];       
    //RPC连接
    $api_method = "get_BlockNumber";
    $rpc = new $class_name($rpc_ip,$rpc_port,$rpc_user,$rpc_pass); 
    if (!method_exists($rpc,$api_method)) {
        $this->error("$class_name 不存在 $api_method 方法");
    }
    $ret = $rpc->$api_method();    
    if($ret['code'] == 1){
         $this->error("连接成功！$api_method 执行成功：".json_encode($ret['data'])); 
    }else{
         $this->error("连接失败！$api_method 执行错误:".json_encode($ret['data']));          
    }  
                         
}

}