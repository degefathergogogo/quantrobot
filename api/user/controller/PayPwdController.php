<?php
namespace api\user\controller;
use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;
class PayPwdController extends RestBaseController
{   //添加密码
    public function addpwd(){
        //判断用户是否有密码，有就结束
        $validate = new Validate([
            'pwd'        => 'require',
            ]);
        $validate->message([
           'pwd.require'   => '请输入密码!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $regh='';
        $regh= "/^\d{6,}$/";
        if(!preg_match($regh,$data['pwd'])){
            $this->error('密码错误，请检查后重试！');
        }
        $userId    = $this->getUserId();
        $sql=  Db::name("user")->where(['id'=> $userId  ,'user_status'=>['neq',0]])->find();
        if(empty($sql)){
            $this->error('暂无该用户信息！');
        }elseif( $sql['has_paypwd']==1 ){
            $this->error('该用户已有支付密码，无需重新添加！');
        }
        //通过验证后，修改用户的三个字段，修改成功返回信息，修改失败返回信息
        $isup=  Db::name("user")->where(['id'=> $userId  ,'user_status'=>['neq',0]])->update([
            'auth_id' => 2,
            'has_paypwd'=>1,
            //'paypwd' => $data['pwd']
            'paypwd' => cmf_password('pay'.$data['pwd'])
        ]);
        if($isup){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败，请重试！');
        }
    }
    //修改密码 ,  需要先通过原密码或者手机号验证才可以请求到这个页面， 现在是直接跳转，如果考虑安全性的话，后期可以加个密钥验证（通过手机或验证码验证后，返回一个密钥，请求携带密钥来修改支付密码）
    public function uppwd(){
        //判断是否已有密码 ，没有就结束，用户还没密码，请先设置
        $validate = new Validate([
            'pwd'        => 'require',
            ]);
        $validate->message([
           'pwd.require'   => '请输入密码!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $userId    = $this->getUserId();
        $sql=  Db::name("user")->where(['id'=> $userId  ,'user_status'=>['neq',0]])->find();
        if(empty($sql)){
            $this->error('暂无该用户信息！');
        }elseif( $sql['has_paypwd']==0 ){
            $this->error('还没有支付密码，请先添加！');
        }
        $regh='';
        $regh= "/^\d{6,}$/";
        if(!preg_match($regh,$data['pwd'])){
            $this->error('密码格式错误，请检查后重试！');
        }
        if (cmf_compare_password('pay'.$data['pwd'], $sql['paypwd'])) {
            $this->error('与原密码重复');
        }
        $isup=  Db::name("user")->where(['id'=> $userId  ,'user_status'=>['neq',0]])->update([
            'paypwd' => cmf_password('pay'.$data['pwd'])
        ]);
        if($isup){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败，请重试！');
        }
    }
    //手机号验证(忘记支付密码时)
    public function forgetpwd(){
        $validate = new Validate([
            'new_pwd'       => 'require',
            'vcode'        => 'require',
            ]);
        $validate->message([
            'vcode.require'   => '请输入验证码!',
            'new_pwd.require'  => '请输入您的新支付密码!',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $regh= "/^\d{6,}$/";
        if(!preg_match($regh,$data['new_pwd'])){
            $this->error('密码格式错误，请检查后重试！');
        }
        // if (!cmf_check_mobile($data['mobile'])) {
        //     $this->error("请输入正确的手机格式!");
        // } 
        $userId = $this->getUserId();
        $mobile = Db::name("user")->where('id', $userId)->value('mobile');
        // if ($mobile != $data['mobile']) {
        //     $this->error("您绑定的手机号与当前手机号不符合！");
        // }
        $vcode = Db::name("verification_code")->where('account', $mobile )->find();
        if ($vcode['expire_time'] < time() ) {
            $this->error("验证码已过期，请重试！");
        }
        if($vcode['code'] == $data['vcode'] ){

            //可以查询新密码是否与原密码相同
            $isup=  Db::name("user")->where(['id'=> $userId  ,'user_status'=>['neq',0]])->update([
                'paypwd' => cmf_password('pay'.$data['pwd'])
            ]);
            if($isup){
                $this->success('操作成功！');
            }else{
                $this->error('操作失败，请重试！');
            }
        }else{
            $this->error("验证码错误！");
        }
    }

     //原密码验证(修改密码时)
     public function oldpwdva(){
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
        $mobile = Db::name("user")->where('id', $userId)->value('paypwd');
        $paypwd = 'pay'.$data['vcode'];
         if (!cmf_compare_password($paypwd, $mobile)) {
             $this->error("密码不正确!");
         }else{
            $this->success("验证成功！");
        }
    }
}