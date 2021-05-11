<?php
class xrprpc {

  private  $address ;
  private  $port ;
  private  $get_address ;
  private  $get_port ;  
  private  $protocal ;
  public function __construct($host,$port,$user,$pass,$protocal='https')
  {

      $this->address       = "s1.ripple.com";
      $this->port          = "51234";
      $this->get_address   = "data.ripple.com";
      $this->get_port      = "443";      
      $this->protocal      = $protocal;
  }

  public  function get_NewAddress($uid)
  {
      //$path = "wallet_propose";
      //$post_data['passphrase'] = $account;

      //return $this->request($path,$post_data);
      $addr = "rw6bgGT8LQM1kf9tkTfNEZxAnBU4SPwL9u";
      $ret['seed'] = "";
      $ret['memo'] = $uid;
      $ret['address'] =$addr ;
      
      return array('code'=>1,'data'=>$ret);           

  }

  public  function get_Balance($addr)
  {
      $addr = "rw6bgGT8LQM1kf9tkTfNEZxAnBU4SPwL9u";
      $path = "/v2/accounts/".$addr."/balances?currency=XRP";
      $result=  json_decode($this->get_request($path),true);
      if(!isset($result['balances'])){
         return array('code'=>0,'data'=>$result);
      }else{
          $ret['balance'] = $result['balances'][0]['value'];
          return array('code'=>1,'data'=>$ret);  
      }

  }


  public  function get_Transactions($start_pos)
  {
      $addr = "rw6bgGT8LQM1kf9tkTfNEZxAnBU4SPwL9u";
      $start_pos = $start_pos;//1419835465;
      $end_pos = time() - 180;
      $path = "/v2/accounts/".$addr."/transactions"."?start=".$start_pos."&end=".$end_pos."&result=tesSUCCESS&type=Payment&limit=1000";
      $result=  json_decode($this->get_request($path),true);

      if(!isset($result['transactions'])){
         return array('code'=>0,'data'=>json_encode($result));
      }else{
          $ret['transactions'] = $result['transactions'];
          $ret['lastblock'] = $end_pos ;
          //var_dump($result['transactions']);;
          return array('code'=>1,'data'=>$ret);  
      }

  }

  // transfer
  public  function send_Transactions($from, $to, $value, $pwd){

      $path = "sign";
      $tx_json['Account'] = $from;
      $tx_json['Amount'] = $value*100000;
      $tx_json['Destination'] = $to;
      $tx_json['TransactionType'] = "Payment";
      $tx_json['DestinationTag'] = "";
      $param['tx_json'] = $tx_json;
      $param['offline'] = "false";
      $param['secret'] = "zhengbingdan1234";
      $result=  json_decode($this->post_request($path,$param),true);
      if(!isset($result['result'])){
          return array('code'=>0,'msg'=>json_encode($result),'data'=>'');
      }
      if($result['result']['status']=='success'){
        $tx_blob = $result['result']['tx_blob'];
        if(empty($tx_blob)){
           return array('code'=>0,'msg'=>"sign fail : tx_blob is empty",'data'=>'');
        }
        //开始提交
        $path = "submit";
        $param2['tx_blob'] = $tx_blob;
        $result2=  json_decode($this->post_request($path,$param2),true);
        if(!isset($result2['result'])){
          return array('code'=>0,'msg'=>json_encode($result2),'data'=>'');
        }
        if($result['result']['status']=='success'){
           $ret['tx_id']= $result['result']['tx_json']['hash'];
           return array('code'=>0,'msg'=>"",'data'=>$ret);  
        }else{
          return array('code'=>0,'msg'=>json_encode($result2),'data'=>'');
        }
      }else{
         return array('code'=>0,'msg'=>json_encode($result),'data'=>'');       
      }
  }

  private  function get_request($path)
  {

      if (strlen($this->address) <= 0 || $this->port <= 0) {
          echo "eth client address or port error";
          exit();
      }

      $url = $this->protocal."://".$this->get_address.":". $this->get_port.$path;
      //var_dump($url);
      //echo $data;die();
      return $this->get($url);
  }


  private  function post_request($path,$post_data)
  {

      if (strlen($this->address) <= 0 || $this->port <= 0) {
          echo "eth client address or port error";
          exit();
      }
      $url = $this->address.":". $this->port;
      $data['method'] = $path;
      $data['params'][0] = $post_data;

      $data = json_encode($data);
      //echo $data;die();
      return $this->post($url, $data);
  }

  
  // curl for request
  private  function post($url, $post_data = '', $timeout = 10)
  {
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_HTTPHEADER ,array('Content-type: application/json'));
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout ); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        echo 'Errno'.curl_error($curl);//捕抓异常
    }
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据，json格式
  }


  // curl for request
  private  function get($url,$timeout = 10)
  {

    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_HTTPHEADER ,array('Content-type: application/json'));
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout ); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        echo 'Errno'.curl_error($curl);//捕抓异常
    }
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据，json格式
  }
}
?>
