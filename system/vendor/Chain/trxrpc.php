<?php

class trxrpc {

  private  $address ;
  private  $port ;


  public function __construct($host,$port,$user,$pass,$protocal='http')
  {
      $this->address = "http://47.74.229.70";
      $this->address_wallet = "http://47.74.229.70";// "http://47.241.24.41";
      $this->port = 8090;//8091    
      $this->port_wallet = 8090;// 8090;          

  }

  public  function get_NewAddress($uid)
  {
      //echo $this->base58check2HexString('TVCQmmQ1aoByZHaCdHCZVnqKQKaspTnYHV');
      //echo $this->hexString2Base58check('41d2e99f8881ef56b211ae7afacff00969c86b983c');
      //die();
      $path = "wallet/generateaddress";
      $post_data = array();
      $result =  json_decode($this->request2($path,$post_data),true);
      $ret = array();
      if(isset($result['address'])){
        $ret['seed'] = $result['privateKey'] ;
        $ret['memo'] = "";
        $ret['address'] = $result['address'] ;        
        return array('code'=>1,'data'=>$ret);    
      }else{
        return array('code'=>0,'data'=>json_encode($result)); 
      }
  }


  public  function get_Balance($addr)
  { 

    $hex_addr = $this->base58check2HexString($addr);
    //$path = "walletsolidity/getaccount";
    $path = "wallet/getaccount";
    $post_data = array('address'=>$hex_addr);
    $result =  json_decode($this->request($path,$post_data),true);
    $ret = array();
    //var_dump($result);die();
    if(isset($result['balance'])){
      $ret['balance'] = $result['balance']/pow(10,6) ;
      return array('code'=>1,'data'=>$ret);    
    }else{
      return array('code'=>0,'data'=>json_encode($result)); 
    }
  }

  
  //eth的token余额
  public  function get_TokenBalance($addr,$token_contract,$decimals=6)
  {   
      $hex_addr = $this->base58check2HexString($addr);
      $hex_token = $this->base58check2HexString($token_contract);

      $path = "wallet/triggerconstantcontract";//"wallet/triggersmartcontract";

      $post_data = array(
          'contract_address'=>$hex_token,
          'function_selector'=>"balanceOf(address)",
          'parameter'=> "000000000000000000000000".substr($hex_addr,2),
          'owner_address'=>$hex_addr
        );
      $result =  json_decode($this->request($path,$post_data),true);
      $ret = array();
      if($result['result']['result']===true){
        $hex_number = preg_replace('/^0+/','',$result['constant_result'][0]);
        //var_dump($result['constant_result']);
        //var_dump($hex_number);
        //die();
        if(empty($hex_number)){
             $num = 0;
        }else{
             $num = gmp_init($hex_number,16);
            $num = round(gmp_strval($num,10)/pow(10,$decimals),8) ;//转为十进制          
        }
        $ret['balance'] = $num;
        return array('code'=>1,'data'=>$ret);    
      }else{
        return array('code'=>0,'data'=>$result['msg']); 
      }
  }

  public  function get_BlockNumber()
  {
    //$path = "walletsolidity/getnowblock";
    $path = "wallet/getnowblock";
    $post_data = array();

    $result =  json_decode($this->request($path,$post_data),true);
    $ret = array();
    if(isset($result['blockID'])&&isset($result['block_header']['raw_data']['number'])){
      $ret['block_num'] = $result['block_header']['raw_data']['number'];
      return array('code'=>1,'data'=>$ret);    
    }else{
      return array('code'=>0,'data'=>json_encode($result)); 
    } 
  }

  public  function get_Transactions($id)
  {    
    //先获取当前区块高度
    $ret = $this->get_BlockNumber();
    if($ret['code'] == 0){
      return array('code'=>0,'data'=>"get_BlockNumber fail");
    }    
    $max_block = $ret['data']['block_num'];
    $ret = array();
    if(empty($id)){
      $id = $max_block -2 ;
    }
    if($id >= $max_block){
      $ret['transactions']  = array();
      $ret['lastblock']= $id;
      return array('code'=>1,'data'=>$ret);          
    }else{
      $max_block = min($max_block,$id+5);
      //$path = "walletsolidity/getblockbynum";
      $path = "wallet/getblockbynum";
      $transactions = array();
      for($i=$id+1;$i<=$max_block;$i++){
        $post_data = array('num'=>$i);
        $result =  json_decode($this->request($path,$post_data),true);
        //var_dump($result);die();
        if(isset($result['blockID'])){
            if(isset($result['transactions'])){
              $transaction_list =  $result['transactions'];
              foreach($transaction_list  as $value){
                array_push($transactions, $value);          
              }
            }
        }else{
          return array('code'=>0,'data'=>json_encode($result,true)); 
        }   
      }
      $ret['transactions']  = $transactions;
      $ret['lastblock']= $max_block;
      return array('code'=>1,'data'=>$ret);    
    }
   
  }

  // transfer
  public  function send_Transactions($from, $to, $value, $pwd){
      //创建交易
      $path = "wallet/createtransaction";
      $hex_from =  $this->base58check2HexString($from);
      $hex_to =  $this->base58check2HexString($to);
      $post_data = array('owner_address'=>$hex_from,'to_address'=>$hex_to,'amount'=>intval(bcmul($value,pow(10,6))));
      $result =  json_decode($this->request($path,$post_data),true);
      if(isset($result['txID'])){
        $txId  = $result['txID'];
        $raw_data  = $result['raw_data'];
        //var_dump($txId);
      }else{
        return array('code'=>0,'data'=>json_encode($result)); 
      } 
      //var_dump($result);
      //签名交易
      $path = "wallet/gettransactionsign";
      $transaction = array('txId'=>$txId,'raw_data'=>$raw_data);
      //$post_data = array('transaction'=>$transaction,'privateKey'=>$pwd);
      $post_data = array('transaction'=>$result,'privateKey'=>$pwd);
      $result2 =  json_decode($this->request($path,$post_data),true);
      if(!isset($result2['signature'])){
        return array('code'=>0,'data'=>json_encode($result2)); 
      } 
      //var_dump($result2);
      //广播交易
      $path = "wallet/broadcasttransaction";
      $post_data = $result2;
      $result3 =  json_decode($this->request($path,$post_data),true);
      if(isset($result3['result'])&&$result3['result']==true){
        $ret = array();
        $ret['tx_id']  = $txId;         
        return array('code'=>1,'data'=>$ret);    
      }else{
        return array('code'=>0,'data'=>json_encode($result3)); 
      } 
  }

  //transfer token
  public  function send_TokenTransactions($from, $to, $value, $pwd,$token_contract,$decimals=6){
      //创建交易
      $path = "wallet/triggersmartcontract";
      $hex_from =  $this->base58check2HexString($from);
      $hex_to =  $this->base58check2HexString($to);
      $hex_token =  $this->base58check2HexString($token_contract);

      $value_hex =  $this->bc_dechex($value*pow(10,$decimals));
      $format_str = $value_hex;
      for ($i=0; $i < (64-strlen($value_hex)); $i++) {
          $format_str = '0'.$format_str;
      }

      $post_data = array(
        'owner_address'=>$hex_from,
        'contract_address'=>$hex_token,
        'function_selector'=>"transfer(address,uint256)",
        'parameter'=> "000000000000000000000000".substr($hex_to,2).$format_str,
        'call_value'=>0,
        'fee_limit'=>100000000
      );
      $result =  json_decode($this->request($path,$post_data),true);
      if(isset($result['transaction'])){
          $transaction  = $result['transaction'];
          $txId  = $transaction['txID'];
          //var_dump($txId);
      }else{
        return array('code'=>0,'data'=>json_encode($result)); 
      } 
      //var_dump($result);
      //签名交易
      $path = "wallet/gettransactionsign";
      $post_data = array('transaction'=>$transaction,'privateKey'=>$pwd);
      $result2 =  json_decode($this->request($path,$post_data),true);
      if(!isset($result2['signature'])){
        return array('code'=>0,'data'=>json_encode($result2)); 
      } 
      //var_dump($result2);
      //广播交易
      $path = "wallet/broadcasttransaction";
      $post_data = $result2;
      $result3 =  json_decode($this->request($path,$post_data),true);
      if(isset($result3['result'])&&$result3['result']==true){
        $ret = array();
        $ret['tx_id']  = $txId;         
        return array('code'=>1,'data'=>$ret);    
      }else{
        return array('code'=>0,'data'=>json_encode($result3)); 
      } 
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
      return $this->post($url, json_encode($data));
  }

  private  function request2($path,$post_data)
  {

      if (strlen($this->address) <= 0 || $this->port <= 0) {
          echo "eth client address or port error";
          exit();
      }
      $url = $this->address_wallet . ":" . $this->port_wallet. "/". $path;
      //$data = json_encode($post_data);
      $data = $post_data;
      //echo " $url";
      //echo " $data";die();
      return $this->post($url, json_encode($data));
  }



  // curl for request
  private  function post($url, $post_data = array(), $timeout = 10)
  {
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    //curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
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



  private function base58_encode($string)
  {
      $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
      $base = strlen($alphabet);
      if (is_string($string) === false) {
          return false;
      }
      if (strlen($string) === 0) {
          return '';
      }
      $bytes = array_values(unpack('C*', $string));
      $decimal = $bytes[0];
      for ($i = 1, $l = count($bytes); $i < $l; $i++) {
          $decimal = bcmul($decimal, 256);
          $decimal = bcadd($decimal, $bytes[$i]);
      }
      $output = '';
      while ($decimal >= $base) {
          $div = bcdiv($decimal, $base, 0);
          $mod = bcmod($decimal, $base);
          $output .= $alphabet[$mod];
          $decimal = $div;
      }
      if ($decimal > 0) {
          $output .= $alphabet[$decimal];
      }
      $output = strrev($output);
      foreach ($bytes as $byte) {
          if ($byte === 0) {
              $output = $alphabet[0] . $output;
              continue;
          }
          break;
      }
      return (string) $output;
  }
  private function base58_decode($base58)
  {
      $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
      $base = strlen($alphabet);
      if (is_string($base58) === false) {
          return false;
      }
      if (strlen($base58) === 0) {
          return '';
      }
      $indexes = array_flip(str_split($alphabet));
      $chars = str_split($base58);
      foreach ($chars as $char) {
          if (isset($indexes[$char]) === false) {
              return false;
          }
      }
      $decimal = $indexes[$chars[0]];
      for ($i = 1, $l = count($chars); $i < $l; $i++) {
          $decimal = bcmul($decimal, $base);
          $decimal = bcadd($decimal, $indexes[$chars[$i]]);
      }
      $output = '';
      while ($decimal > 0) {
          $byte = bcmod($decimal, 256);
          $output = pack('C', $byte) . $output;
          $decimal = bcdiv($decimal, 256, 0);
      }
      foreach ($chars as $char) {
          if ($indexes[$char] === 0) {
              $output = "\x00" . $output;
              continue;
          }
          break;
      }
      return $output;
  }
  
  //encode address from byte[] to base58check string
  private function base58check_en($address)
  {
      $hash0 = hash("sha256", $address);
      $hash1 = hash("sha256", hex2bin($hash0));
      $checksum = substr($hash1, 0, 8);
      $address = $address.hex2bin($checksum);
      $base58add = $this->base58_encode($address);
      return $base58add;
  }
  
  //decode address from base58check string to byte[]
  private function base58check_de($base58add)
  {
      $address = $this->base58_decode($base58add);
      $size = strlen($address);
      if ($size != 25) {
          return false;
      }
      $checksum = substr($address, 21);
      $address = substr($address, 0, 21);     
      $hash0 = hash("sha256", $address);
      $hash1 = hash("sha256", hex2bin($hash0));
      $checksum0 = substr($hash1, 0, 8);
      $checksum1 = bin2hex($checksum);
      if (strcmp($checksum0, $checksum1)) {
          return false;
      }
      return $address;
  }
  
  private function hexString2Base58check($hexString){
      $address = hex2bin($hexString);
      $base58add = $this->base58check_en($address);
      return $base58add;
  }
  
  private function base58check2HexString($base58add){
      $address = $this->base58check_de($base58add);
      $hexString = bin2hex($address);
      return $hexString;
  }
  
  private function hexString2Base64($hexString){
      $address = hex2bin($hexString);
      $base64 = base64_encode($address);
      return $base64;
  }
  
  private function base642HexString($base64){
      $address = base64_decode($base64);
      $hexString = bin2hex($address);
      return $hexString;
  }
  
  private function base58check2Base64($base58add){
      $address = $this->base58check_de($base58add);
      $base64 = base64_encode($address);
      return $base64;
  }
  
  private function base642Base58check($base64){
      $address = base64_decode($base64);
      $base58add = $this->base58check_en($address);
      return $base58add;
  }

  private  function bc_dechex($number)
  {
      if ($number <= 0) {
          return false;
      }
      $conf = ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];
      $char = '';
      do {
          $key = fmod($number, 16);
          $char = $conf[$key].$char;
          $number = floor(($number-$key)/16);
      } while ( $number > 0);
      return $char;
  }
}

?>
