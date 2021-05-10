<?php
namespace api\user\controller;
use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;
use api\common\exception\GoogleAuthenticator;
use Endroid\QrCode\QrCode;

class ChecksController extends RestBaseController
{
    // 验证码验证通过后才可以开通谷歌二次验证
    public function mobilecheck(){
        $validate = new Validate([
            'vcode'        => 'require',
            ]);
        $validate->message([
            'vcode.require'   => '请输入验证码!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $userId = $this->getUserId();
        $mobile = Db::name("user")->where('id', $userId)->value('mobile');
        $vcode = Db::name("verification_code")->where('account', $mobile )->find();
        if ($vcode['expire_time'] < time() ) {
            $this->error("验证码已过期，请重试！");
        }
        if($vcode['code'] == $data['vcode'] ){
            return $userId ;   
        }else{
            return false ;   
        }
    }
    public function VcodeCheck(){
        $data = $this->mobilecheck();
        if($data){
            $this->success("验证成功！");
        }else{
            $this->error("验证失败，请重试！");
        }
    }
    //开启验证
    // 返回key和二维码图片 
    public function opencheck(){
        $userid=$this->mobilecheck();
        $u_data = Db::name("user")->where(['id'=> $userid  ,'user_status'=>['neq',0]])->find();
    
        if($u_data['is_google_check'] == 1 ){
            $this->error('已经开启了二次验证，不必重复开启！');
        }
        $up_data['is_google_check'] =1;
      
        if(!empty(  $u_data['google_check_key'] )){
            $vcode = $u_data['google_check_key'];
            $vcode_img_str = $u_data['google_check_key_img']; 
        }
        else{
            $g_auth = new GoogleAuthenticator();
            // 获取随机密钥
            $vcode  = $g_auth->createSecret();
            $vcode_img_str = $g_auth->getQRCodeGoogleUrl(  $u_data['mobile']    , $vcode );
            $up_data['google_check_key'] =  $vcode;
            $up_data['google_check_key_img'] =  $vcode_img_str;
        }
        $isup=  Db::name("user")->where(['id'=> $userid  ,'user_status'=>['neq',0]])->update($up_data);
        if($isup){
            $data['key'] =  $vcode; 
            $data['vcode_img_str'] =  $vcode_img_str ; 
            $this->success('操作成功！',$data);
        }else{
            $this->error('操作失败，请重试！');
        }
    }

    //验证公用函数
    public function checkccommon(){
        $validate = new Validate([
            'check_num'        => 'require',
            ]);
        $validate->message([
            'check_num.require'   => '请输入二次验证码!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $userId = $this->getUserId();
        $u_data =   Db::name("user")->where(['id'=> $userId])->find();
        $g_auth = new GoogleAuthenticator();
        // 获取随机密钥
        $vcode  = $g_auth->getCode(  $u_data['google_check_key'] );
        if($vcode == $data['check_num'] ){
            return 1;
        }else{
            return 2;
        }
    }

    // 日常操作验证
    public function checkcode(){
        $vcode = $this->checkccommon();
        if($vcode ==  1 ){
            $this->success('验证成功！');
        }else if($vcode == 2){
            $this->error('验证失败，请重试！');
        }else{
            $this->error('获取数据错误，请重试！');
        }
    }
    
    //关闭验证
    //输入密码验证
    public function closecheck(){
       $vcode = $this->checkccommon();
       $userId = $this->getUserId();
        if($vcode ==  1 ){
            $isup = Db::name("user")->where(['id'=> $userId])->update([
                'is_google_check' => 0      ]);
            if($isup){
                $this->success('关闭成功！');
            }else{
                $this->error('关闭失败，请重试！');
            }
        }else if($vcode == 2){
            $this->error('验证失败，请重试！');
        }else{
            $this->error('获取数据错误，请重试！');
        }
    }
}