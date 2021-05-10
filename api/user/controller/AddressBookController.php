<?php
namespace api\user\controller;
use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;
class AddressBookController extends RestBaseController
{
    //应验证 当前传进来的币种id是否存在于数据库中
    protected $coin_data;
    protected $ress_type;
    public function _initialize(){
       $coin_data =  Db::name("coin")->column('*','id');
       $this->coin_data  =  $coin_data;
       $this->ress_type  =  [1=>'cloud_status',2=>'hd_status'];
    }
    //地址列表
    public function lists()
    {
        $validate = new Validate([
            'type'          => 'require',
            ]);
        $validate->message([
           'type.require'   => '请选择地址类型!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $userId    = $this->getUserId();
        $obj= Db::name("address_book")->alias('abook')
                ->where(['user_id'=>$userId,'status'=>1,'type'=>$data['type']])
                ->join('et_coin','abook.coin_id = et_coin.id')
                ->field('abook.*,et_coin.coin_name');
        $count=$obj->count();
        $selectdata= Db::name("address_book")->alias('abook')
                        ->where(['user_id'=>$userId,'status'=>1,'type'=>$data['type']])
                        ->join('et_coin','abook.coin_id = et_coin.id')
                        ->field('abook.*,et_coin.coin_name')
                        ->select();
        if ($count<=0) {
            $this->success('操作成功！',['count'=>0,'list'=>[]]);
        }else{
            $this->success('操作成功！',['count'=>$count,'list'=>$selectdata]);
        }
    }
    //添加地址
    public function addAddress(){
        $validate = new Validate([
            'name'          => 'require',
            'host'          => 'require',
            'coin_id'       => 'require',
            'type'          => 'require',
            ]);
        $validate->message([
            'name.require'          => '请输入名称!',
            'host.require'          => '请输入地址!',
            'coin_id.require'       => '请选择币种!',
            'type.require'          => '请选择地址类型!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $userId    = $this->getUserId();
        $coin  = $this->coin_data;
        $rtype = $this->ress_type;
        //判断该币种在当前的钱包中是否启用
        if(($data['type']!=1) && ($data['type']!=2) ){
            $this->error('当前地址类型不正确！');
        }
        if($coin[$data['coin_id']][$rtype[$data['type']]]!=1  ){
            $this->error('当前钱包的该币种未启用！');
        }
        $edit_data=[];
        $edit_data['name'] = $data['name'];
        $edit_data['host'] = $data['host'];
        $edit_data['coin_id'] = $data['coin_id'];
        $edit_data['type'] = $data['type'];
        $edit_data['user_id'] = $userId ;
        $edit_data['create_at'] = time();
        $edit_data['status'] = 1;
        $isadd=  Db::name("address_book")->insert($edit_data);
        if($isadd){
            $this->success('添加成功！');
        }else{
            $this->error('添加失败，请重试！');
        }
    }
    //单条地址详情
    public function getOneAddress(){
        $validate = new Validate([
            'id'          => 'require',
            ]);
        $validate->message([
            'id.require'  => '请输入编号!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $userId    = $this->getUserId();
        $sql=  Db::name("address_book")->where(['id'=>$data['id'],'status'=>1])->find();
        if(!empty($sql)){
            if($sql['user_id']!=$userId){
                $this->error('查询对象不属于当前用户,请重试！');
            }else{
                $this->success('操作成功！',$sql);
            }
        }else{
            $this->error('查询数据不存在,请重试！');
        }
    }
    //修改地址信息
    public function editAddress(){
        $validate = new Validate([
            'id'          => 'require',
        ]);
        $validate->message([
            'id.require'  => '请输入操作对象id!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $userId    = $this->getUserId();
        $sql=  Db::name("address_book")->where(['id'=>$data['id'],'status'=>1])->find();
        if(empty($sql)){
            $this->error('操作数据不存在,请重试！');
        }
        if($sql['user_id']!=$userId){
            $this->error('操作对象不属于当前用户,请重试！');
        }
        $edit_data=[];
        !empty( $data['name'] )?  $edit_data['name'] = $data['name']:'';
        !empty( $data['host'] )?  $edit_data['host'] = $data['host']:'';
        if( !empty( $data['coin_id'] ) ){
            $coin  = $this->coin_data;
            $rtype = $this->ress_type;
            if(empty($coin[$data['coin_id']])){
                $this->error('您选择的币种不存在！');
            }
            if($coin[$data['coin_id']][$rtype[$sql['type']]]!=1  ){
                $this->error('当前钱包的该币种未启用！');
            }
            $edit_data['coin_id'] = $data['coin_id'];
        }
        $isup= Db::name("address_book")->where(['id'=>$data['id'],'status'=>1])->update( $edit_data );
        if($isup){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败，请重试！');
        }
    }
    //删除地址
    public function delAddress(){
        $validate = new Validate([
            'did'          => 'require',
            ]);
        $validate->message([
            'did.require'  => '请输入编号!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $userId    = $this->getUserId();
        $sql=  Db::name("address_book")->where(['id'=>$data['did'],'status'=>1])->find();
        if(empty($sql)){
            $this->error('操作数据不存在,请重试！');
        }
        if($sql['user_id']!=$userId){
            $this->error('操作对象不属于当前用户,请重试！');
        }
        $isup= Db::name("address_book")->where(['id'=>$data['did'],'status'=>1])->update([
            'status'=>0          ]);
        if($isup){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败，请重试！');
        }
    }
}