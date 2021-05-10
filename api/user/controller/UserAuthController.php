<?php
namespace api\user\controller;
use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;
use think\exception\HttpResponseException;
use think\Response;
class UserAuthController extends RestBaseController
{   //添加认证信息
    public function addauth(){
        $validate = new Validate([
            'country'      => 'require',
            'u_name'       => 'require',
            'birthday'     => 'require',
            'photo_id1'    => 'require',
            'photo_id'     => 'require',
            'address'      => 'require',
            ]);
        $validate->message([
           'country.require'   => '请输入国家!',
           'u_name.require'  => '请输入用户名！',
           'birthday.require'   => '请输入生日!',
           'photo_id1.require'  => '请添加证件照！',
           'photo_id.require'  => '请输入证件照号码！',
           'address.require'   => '请输入现居住地!',
        ]);
         $userId    = $this->getUserId();
         if ($this->user['auth_id'] < 2){
             $this->error('请先设置支付密码，完成等级 2验证');
         }
         //查询当前数据库中是否有该用户提交的待审核的申请，及用户是否已经申请过，有的话就结束吧
         $sql1=Db::name("auth")->where(['user_id'=>$userId,'status'=>2])->find();
         $sql2=Db::name("auth")->where(['user_id'=>$userId,'status'=>1])->find();
         $sql_u= Db::name("user")->where(['id'=> $userId  ,'user_status'=>['neq',0]])->find();
        if(!empty(  $sql1 )){
            $this->error('您已经提交了申请，请不要重复提交了！');
        }
        if( !empty(  $sql2 )  ||  $sql_u['auth_id']>2 ){
            $this->error('您的申请已通过了，不需要再提交！');
        }
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        !empty($data['country'])? $inst_data['country']=$data['country']  : '';
        !empty($data['u_name'])? $inst_data['u_name']=$data['u_name']: '';
        !empty($data['birthday'])? $inst_data['birthday']=$data['birthday']: '';
        !empty($data['photo_id1'])? $inst_data['photo_id1']=$data['photo_id1']: '';
        !empty($data['photo_id'])? $inst_data['photo_id']=$data['photo_id']: '';
        !empty($data['photo_id2'])? $inst_data['photo_id2']=$data['photo_id2']: '';
        !empty($data['address'])? $inst_data['address']=$data['address']: '';
        $inst_data['user_id'] =  $userId    ;
        $inst_data['status'] = 2  ;
        $inst_data['type'] = 1  ;
        $inst_data['create_at'] = time()  ;
        $inst_data['refuse_at'] = ''  ;
        $inst_data['refuse_content'] = ''  ;
        $isint=Db::name("auth")->insert(  $inst_data  );
        if($isint){
            $this->success('申请成功，请等待审核！');
        }else{
            $this->error('添加失败，请重试！');
        }
    }
    //展示认证信息
    public function showauth(){
        $userId  = $this->getUserId();
        $sql=Db::name("auth")->where(['user_id'=>$userId,'status'=>2])->find();
        if(!empty(  $sql )){
            $this->success('查询成功！', $sql);
        }else{
            $authok=Db::name("auth")->where(['user_id'=>$userId,'status'=>1])->find();
            $sql_u= Db::name("user")->where(['id'=> $userId  ,'user_status'=>['neq',0]])->find();
            if(!empty(  $authok ) || $sql_u['auth_id']>2){
                $this->success('您的申请已经通过了!');
            }else{
                $this->error('暂无认证信息!');
            }
        }
    }
    //获取认证等级列表
    public function getAuthGrade(){
        $sql=Db::name("auth_grade")->where(['status'=>1])->order('id asc')->select();
        if(!empty(  $sql )){
            $this->success('查询成功!',$sql);
        }
    }

    //获取旷世身份证识别信息
    public function addKSImageAuth(){
    //   echo "<pre>";  print_r($_POST);die;
        $validate = new Validate([
            'token'      => 'require',
            'userInfo'       => 'require',
            ]);
        $validate->message([
           'token.require'   => '请填写用户token!',
           'userInfo.require'  => '请填写用户验证信息！',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $user = Db::name('user_token')->where(['token' =>  $data['token'] ])->find();
        if(!empty($user)) {
           $userId   = $user['user_id'];
        }else{
            $this->error(['code' => 10001, 'msg' => '用户未登录']);
        }

        $sql1=Db::name("auth")->where(['user_id'=>$userId,'status'=>2])->find();
        $sql2=Db::name("auth")->where(['user_id'=>$userId,'status'=>1])->find();
        $sql_u= Db::name("user")->where(['id'=> $userId  ,'user_status'=>['neq',0]])->find();
       if(!empty(  $sql1 )){
           $this->error('您已经提交了申请，请不要重复提交了！');
       }
       if( !empty(  $sql2 )  ||  $sql_u['auth_id']>2 ){
           $this->error('您的申请已通过了，不需要再提交！');
       }
        // define('_ROOT', str_replace("\\", '/', dirname(__FILE__)));
        // $url =_ROOT.'/../../../public/999.html';
        // $file=fopen($url,'rd+');
        // fwrite($file,json_encode($data),'a');
        $user_info_json = htmlspecialchars_decode($data['userInfo']);
        $user_infos= json_decode($user_info_json ,true) ;       
        if(!empty($user_infos)){
            $user_info =array_change_key_case($user_infos);
            $inst_data= [];
            $inst_data['u_name'] = $user_info['useridcardname'];
            $inst_data['photo_id'] = $user_info['useridcardnumber'];
            $inst_data['birthday']  = $user_info['userbirthyear']."-".$user_info['userbirthmonth']."-".$user_info['userbirthday'];
            $inst_data['address']  = $user_info['useraddress'];
            $inst_data['country']  = "中国";
            $inst_data['user_id']  = $userId; 
                // $renzheng_data['zm_data']['status'] = $user_info['idcardportraitcompletetype'];
                $renzheng_data['zm_data']['data'] = array_change_key_case($user_info['idcardportraitlegalityitem']);
                // $renzheng_data['fm_data']['status'] = $user_info['idcardemblemcompletetype'];
                $renzheng_data['fm_data']['data'] = array_change_key_case($user_info['idcardemblemlegalityitem']);
            $inst_data['rz_data'] = json_encode( $renzheng_data );
            $inst_data['type']  =2 ;
            $inst_data['status'] = 2  ;
            $inst_data['create_at'] = time()  ;
            $inst_data['refuse_at'] = ''  ;
            $inst_data['refuse_content'] = ''  ;
            $isint=Db::name("auth")->insert(  $inst_data  );
            if($isint){
                $this->success('申请成功，请等待审核！');
            }else{
                $this->error('添加失败，请重试！');
            }
        }else{
            $this->error('用户验证信息不能为空！');
        }
    }
    //获取旷世视频识别信息
    public function addKSVideoAuth(){
        // $data = $this->request->param();
        // echo "<pre>"; print_r($data);
        $validate = new Validate([
            'token'      => 'require',
            'code'       => 'require',
            'message'       => 'require',
            ]);
        $validate->message([
           'token.require'   => '请填写用户token!',
           'code.require'  => '请填写用户视频认证状态码！',
           'message.require'  => '请填写用户视频认证信息！',
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $user = Db::name('user_token')->where(['token' =>  $data['token'] ])->find();
        if(!empty($user)) {
           $userId   = $user['user_id'];
        }else{
            $this->error(['code' => 10001, 'msg' => '用户未登录']);
        }

        $int_data['user_id'] =  $userId;
        $int_data['return_id'] = $data['code'];
        $int_data['return_str'] = $data['message'];
        $int_data['create_at'] = time();
        $isint=Db::name("auth_video_log")->insert(  $int_data  );
        if($data['code'] == 51000){
            Db::name("user")->where('id', $userId)->update(  [ 'auth_id' => 4]  );
            $this->success('认证成功！');
        }else{
            $this->error('认证失败！');
        }
    }

    //获取旷世视频识别信息
    public function kSVideoAuth(){

        $userId  = $this->getUserId();
        $sql = Db::name("auth")->where('user_id',$userId)->whereNotIn('status','0,3')->order('create_at desc')->find();
        if(empty($sql)){
            $this->error('暂无该用户实名认证信息，不能进行下一步!');
        }
        $api_key="HOQ_LJr8C3Kc4JRGAfGwDod3cwqiKn6A";
        $api_secret = "DgEnrftcxINW4DqsA7S_zdRtZwx6vwEQ";
        $sign = $this->gen_sign($api_key, $api_secret);

        $url = "https://openapi.faceid.com/face/v1.2/sdk/get_biz_token";
        $data['sign']= $sign  ;
        $data['sign_version']= 'hmac_sha1'  ;
        $data['liveness_type']= 'meglive'  ;
        $data['comparison_type']= 1  ;
        $data['idcard_name']= $sql['u_name']  ;
        $data['idcard_number']=  $sql['photo_id'] ;
        $return_data=  json_decode( $this->get_data_post( $url ,$data) ,true) ;
        if( array_key_exists('error' ,$return_data )){
            $this->error(  $return_data['error']);
        }else{
            $this->success('请求成功', $return_data);
        }
       
        // $type        = $this->getResponseType();
        // $response    = Response::create($return_data, $type);
        // throw new HttpResponseException($response);
    }
    //生产旷视签名函数
    public function gen_sign($apiKey, $apiSecret){
        $rdm =rand(1000000000,9999999999);
        $current_time = time();
        $expired_time =0;  // $current_time + $expired;
        $srcStr = "a=%s&b=%d&c=%d&d=%d";
        $srcStr = sprintf($srcStr, $apiKey, $expired_time, $current_time, $rdm);
        $sign = base64_encode(hash_hmac('SHA1', $srcStr, $apiSecret, true).$srcStr);
        return $sign;
    }
    //  请求接口
    public function get_data_post( $url ,$data=[]){
        $timeout = 3000;
        $ch = curl_init();                               // 初始化一个cURL会话
        curl_setopt($ch, CURLOPT_URL, $url);            // 所请求api的url
        curl_setopt($ch, CURLOPT_POST, true);            // 使用post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);     // 请求的数据，使用数组
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // 将返回的内容作为变量储存
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // 如果服务器300豪秒内没有响应，脚本就会断开连接
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //php curl调用https出错,服务器所在机房无法验证SSL证书,跳过SSL证书检查。
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);   //表示不检查证书
        $handles = curl_exec($ch);                          // 执行一个curl回话 并获取返回数据
            $error= curl_error ( $ch );
            if(!empty($error)){
                print_r($error);
            }
        curl_close($ch);                                    // 关闭一个CURL会话
        return $handles;
    }
} 
