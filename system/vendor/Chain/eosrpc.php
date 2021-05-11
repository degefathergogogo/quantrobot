<?php

use xtype\Eos\Client;


class eosrpc {
  // for product
 // private static $address =  "3.0.48.180";
  //private static $port  = 8545;
 //private static $address =  "http://jungle.cryptolions.io";
 //private static $port  = 18888;
  // for private test
  private  $address ;
  private  $port ;
  private  $get_address ;
  private  $get_port ;  

  private $account = "dandanyatou1";

  public function __construct($host,$port,$user,$pass,$protocal='http')
  {

      //$this->address       = $host;
      //$this->port          = $port;
      $this->address = "https://open-api.eos.blockdog.com";// "api.eosnewyork.io";
      $this->port = 443;          
      $this->get_address   = "https://open-api.eos.blockdog.com";
      $this->get_port      = "443";      
  }

  public  function get_NewAddress($uid)
  {

      $addr = $this->account;
      $ret['seed'] = "";
      $ret['memo'] = $uid;
      $ret['address'] =$addr ;
      
      return array('code'=>1,'data'=>$ret);           

  }

  public  function newAccount($account)
  {    
      $path = "v1/wallet/create";
      $post_data = $account;
      return $this->request($path,json_encode($post_data));
  }

  public  function createKey($account)
  {    
      $path = "v1/wallet/create_key";
      $post_data[0] = $account;
      $post_data[1] = "K1";
      return $this->request($path,json_encode($post_data));
  }


  public  function getKey($account)
  {   
      $path = "v1/wallet/wallet_list_keys";
      $post_data[] = $account;
      return $this->request($path,json_encode($post_data));
  }  

  public  function createAccountBin($username,$pub_key)
  {       
      $data = '{
        "code": "eosio",
        "action": "newaccount",
        "args": {
          "creator": "eosio",
          "newact": "#username#",
          "owner": {
            "threshold": 1,
            "keys": [
              {
                "key": "#pub_key#", 
                "weight": 1
              }
            ],
            "accounts": [],
            "waits": []
          },
          "active": {
            "threshold": 1,
            "keys": [
              {
                "key": "#pub_key#", 
                "weight": 1
              }
            ],
            "accounts": [],
            "waits": []
          }
        }
      }';   
      $data = str_replace("#username#",$username,$data);
      $data = str_replace("#pub_key#",$pub_key,$data);
      $path = "v1/chain/abi_json_to_bin";
      $post_data = $data;
      //echo $post_data;die();
      return $this->request($path,$post_data);
  }
  public  function transferBin($from,$to,$amount)
  {       
      $data = '{
        "code": "eosio.token",
        "action": "transfer",
        "args": {
          "from": "#from#",
          "to": "#to#",
          "quantity": "#amount# EOS",
          "memo": ""
        }
      }';   
      $data = str_replace("#from#",$from,$data);
      $data = str_replace("#to#",$to,$data);
      $data = str_replace("#amount#",$amount,$data);
      $path = "v1/chain/abi_json_to_bin";
      $post_data = $data;
      //echo $post_data;die();
      return $this->request($path,$post_data);
  }

  public  function get_Balance($addr)
  { 
      $account = $addr;
      $path = "v1/chain/get_currency_balance";
      $post_data['code'] = "eosio.token"; 
      $post_data['account'] = $account;   
      $result = $this->request($path,json_encode($post_data));
      $result_arr = json_decode($result,true);
      if(isset($result_arr['error'])){
         return array('code'=>0,'data'=>$result);
      }else{
         $ret['balance'] = floatval(str_replace(" EOS","",$result_arr[0]));
         return array('code'=>1,'data'=>$ret);  
      }  
  }

  public  function getInfo()
  {       
      $path = "v1/chain/get_info";
      return $this->request($path,"");
  }

  public  function getBlock($id)
  {    
      $path = "v1/chain/get_block";
      $post_data['block_num_or_id'] = "$id";
      return $this->request($path,json_encode($post_data));
  }

 public  function send_Transactions($from, $to, $value, $pwd){

    $account = $this->account;
      
    $client = new Client('http://api.eosnewyork.io');
    //
    // 1. set your private key
    $client->addPrivateKeys([
        'eoskey'
    ]);

    // 2. build your transaction

    $amount =  sprintf("%.4f", $value); 

    $tx = $client->transaction([
        'actions' => [
            [
                'account' => 'eosio.token',
                'name' => 'transfer',
                'authorization' => [[
                    'actor' => $account,
                    'permission' => 'active',
                ]],
                'data' => [
                    'from' => $account,
                    'to' => $to,
                    'quantity' => $amount.' EOS',
                    'memo' => $pwd,
                ],
            ]
        ]
    ]);
    // echo "Transaction ID: {$tx->transaction_id}";
    // die();

    $ret['tx_id']= $tx->transaction_id;
    return array('code'=>1,'msg'=>"",'data'=>$ret); 

   //  die();

   //  $private_key = "";
   //  $pub_key = "";
   //  //获取二进制
   //  $bin_data = json_decode($this->transferBin($from,$to,$value));
   //  $bin_args = $bin_data->binargs;
   //  //var_dump($bin_data);
   //  //获取最新区块
   //  $info = json_decode($this->getInfo());
   //  $chain_id = $info->chain_id;
   //  $head_block_num = $info->head_block_num;
   //  //var_dump($head_block_num);
   //  //获取区块详情
   //  $block_info = json_decode($this->getBlock($head_block_num)) ;
   //  $timestamp = $block_info->timestamp;
   //  $ref_block_prefix = $block_info->ref_block_prefix;
   //  //var_dump($timestamp);
   //  $timestamp = str_replace("#","T",date("Y-m-d#H:i:s.000",time()-3600*7-1800)); //标准时间 + 0.5个小时
   //  //签名交易
   //  $sign_result = $this->signTransactionAccount($head_block_num,$ref_block_prefix,$timestamp, $from,$pub_key,$bin_args,$chain_id);
   //  var_dump($sign_result);
   //  $sign_result  = json_decode($sign_result);
   //  $sign = $sign_result->signatures[0];
   //  //die();  
   //  //发送交易
   //  $push_result = $this->pushTransaction($head_block_num,$ref_block_prefix,$timestamp, $account,$bin_args,$sign );
   //  var_dump($push_result);
   //  $push_result  = json_decode($push_result,true);
   //  if(!isset($push_result['transaction_id'])){
   //    return array('code'=>0,'data'=>json_decode($push_result));
   //  }
   // $ret['tx_id']= $push_result['transaction_id'];
   // return array('code'=>1,'msg'=>"",'data'=>$ret);     
 }
  public  function get_Transactions($start_pos)
  {

      $account = $this->account;
      $path = "v2/third/get_account_transfer";
      $post_data['start_block_num'] = intval($start_pos)+1; 
      $post_data['account_name'] = $account;
      $post_data['code'] = "eosio.token";   
      $post_data['size'] = 100;  
      $result = $this->request($path,json_encode($post_data));
      $result_arr = json_decode($result,true);
      if(isset($result_arr['error'])){
         return array('code'=>0,'data'=>$result);
      }else{
         $ret['transactions'] = $result_arr['list'];
         if(count($result_arr['list'])>0){
            $ret['lastblock']= $result_arr['list'][0]['block_num'];
         }else{
            $ret['lastblock']= $start_pos;
         }
         //var_dump($ret);die();
         return array('code'=>1,'data'=>$ret);  
      }            
  }

  public  function signTransactionAccount($ref_block_num,$ref_block_prefix,$expiration,$account,$pub_key,$binStr,$chain_id)
  { 
      $data = '[{
    "ref_block_num": #ref_block_num#,
    "ref_block_prefix":  #ref_block_prefix#,
    "expiration": "#expiration#",
    "actions": [{
      "account": "eosio.token",
      "name": "transfer",
      "authorization": [{
        "actor": "#account#",
        "permission": "active"
      }],
      "data": "#binStr#"
    }],
    "signatures": []
  },
  ["#pub_key#"], "#chain_id#"
]';   
      $data = str_replace("#ref_block_num#",$ref_block_num,$data);
      $data = str_replace("#ref_block_prefix#",$ref_block_prefix,$data);
      $data = str_replace("#expiration#",$expiration,$data);
      $data = str_replace("#account#",$account,$data);
      $data = str_replace("#pub_key#",$pub_key,$data);       
      $data = str_replace("#binStr#",$binStr,$data);            
      $data = str_replace("#chain_id#",$chain_id,$data);            

      $path = "v1/wallet/sign_transaction";
      $post_data = $data;//echo $data;die();
      return $this->request($path,$post_data);
  }

  public  function pushTransaction($ref_block_num,$ref_block_prefix,$expiration,$account,$binStr,$sign)
  {    
        $data = '{
          "compression": "none",
          "transaction": {
            "ref_block_num": #ref_block_num#,
            "ref_block_prefix": #ref_block_prefix#,
            "expiration": "#expiration#",
            "actions": [
              {
                "account": "#account#",
                "name": "transfer",
                "authorization": [
                  {
                    "actor": "#account#",
                    "permission": "active"
                  }
                ],
                "data": "#binStr#"
              }
            ]
          },
          "signatures": ["#sign#"]
        }';   
        $data = str_replace("#ref_block_num#",$ref_block_num,$data);
        $data = str_replace("#ref_block_prefix#",$ref_block_prefix,$data);
        $data = str_replace("#expiration#",$expiration,$data);
        $data = str_replace("#account#",$account,$data);
        $data = str_replace("#binStr#",$binStr,$data);            
        $data = str_replace("#sign#",$sign,$data);            
      
        $path = "v1/chain/push_transaction";
        //echo $data ; die();
        return $this->request($path,$data);
  }

  private  function get_request($path)
  {

      if (strlen($this->address) <= 0 || $this->port <= 0) {
          echo "eth client address or port error";
          exit();
      }

      $url = "https://".$this->get_address.":". $this->get_port.$path;
      //var_dump($url);
      //echo $data;die();
      return $this->get($url);
  }


  private  function request($path,$post_data)
  {

      if (strlen($this->address) <= 0 || $this->port <= 0) {
          echo "eth client address or port error";
          exit();
      }
      $url = $this->address . ":" . $this->port. "/". $path;
      //$data = json_encode($post_data);
      $data = $post_data;
      //echo " $url";
      //echo " $data";die();
      return $this->post($url, $data);
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

  // curl for request
  private  function post($url, $post_data = '', $timeout = 10)
  {
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查

    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($post_data),
        'apikey: a79ab1c5-0be2-47fe-b720-80ce3d48d343'));

    //curl_setopt($curl, CURLOPT_HTTPHEADER ,array('apikey: a79ab1c5-0be2-47fe-b720-80ce3d48d343'));
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
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
}

?>
