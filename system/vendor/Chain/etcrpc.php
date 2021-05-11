<?php
class etcrpc {
  // for product
    private  $address ;
    private  $port;

  // for private test
  // private static $address = "http://localhost";
  // private static $port = 8545;

  // Warning! don't modify this parmeretes.
  private  $v = "2.0";
  private  $h = 1e18;
  private  $hs = "1000000000000000000";

  public function __construct($host,$port,$user,$pass,$protocal='http')
  {
      $this->address       = $host;
      $this->port          = $port;
  }

  // get net version
  public  function getNetVersion()
  {
      $data = $this->generateRequestData("net_version");
      return $this->request(json_encode($data));
  }

  public  function get_BlockNumber()
  {
      $data = $this->generateRequestData("eth_blockNumber");
      $result = $this->request(json_encode($data));
      $result_arr = json_decode($result,true);
      if(!isset($result_arr['result'])){
        return array('code'=>0,'data'=>$result);
      }
      $num = gmp_init( $result_arr['result'],16);
      $num = gmp_strval($num,10);//转为十进制            
      $ret['block_num'] = $num;
      return array('code'=>1,'data'=>$ret);        
  }

  public  function get_NewAddress($uid)
  {   
      $seed = md5($uid);
      $params = [$seed];
      $data = $this->generateRequestData("personal_newAccount", $params);
      $result =  $this->request(json_encode($data));
      $result_arr = json_decode($result,true);
      
      if(!isset($result_arr['result'])){
         return array('code'=>0,'data'=>$result);
      }
      
      $addr = $result_arr['result'];
      $ret['seed'] = $seed;
      $ret['memo'] = "";
      $ret['address'] = $addr;

      if(strlen($addr) < 10 || substr($addr, 0, 2) != "0x"){
        return array('code'=>0,'data'=>$result);             
      }else{
        return array('code'=>1,'data'=>$ret);           
      }
  }

  //eth的账号余额
  public  function get_Balance($addr)
  {    
      if(!$this->validateEthAddress($addr)){
          return array('code'=>0,'data'=>'address not valid');
      }
      $tag = "latest";
      $params = [$addr, $tag];
      $data = $this->generateRequestData("eth_getBalance", $params);
      $result = $this->request(json_encode($data));
      $result_arr = json_decode($result,true);
      if(!isset($result_arr['result'])){
        return array('code'=>0,'data'=>$result);
      }      
      $num = gmp_init( $result_arr['result'],16);
      $num = round(gmp_strval($num,10)/pow(10,18),8) ;//转为十进制         
      $ret['balance'] = $num;
      return array('code'=>1,'data'=>$ret);      
  }

  //eth的token余额
  public  function get_TokenBalance($addr,$contract,$decimals=18)
  {    
      if(!$this->validateEthAddress($addr)){
          return array('code'=>0,'data'=>'address not valid');
      }
      $tag = "latest";
      $data['from'] = $addr ;
      $data['to'] = $contract;
      $data['data'] = "0x70a08231000000000000000000000000".substr($addr,2);
      $params = [$data,$tag];
      //var_dump($params);
      $data = $this->generateRequestData("eth_call", $params);
      //var_dump($data);
      $result = $this->request(json_encode($data));
      $result_arr = json_decode($result,true);
      if(!isset($result_arr['result'])){
        return array('code'=>0,'data'=>$result);
      }  
      if($result_arr['result']=='0x'){
        $result_arr['result']= '0x0';
      }          
      $num = gmp_init( $result_arr['result'],16);
      $num = round(gmp_strval($num,10)/pow(10,$decimals),8) ;//转为十进制         
      $ret['balance'] = $num;
      return array('code'=>1,'data'=>$ret);      
  }

  // transfer token 
  public  function send_TokenTransactions($from, $to, $value, $pwd,$contract,$decimals=18){

      $unlockInfo = json_decode($this->unlock_Accounts($from, $pwd));
      if (!isset($unlockInfo->result) || !$unlockInfo->result ){
          return $unlockInfo;
      } 
      $value_hex =  $this->bc_dechex($value*pow(10,$decimals));
      $format_str = $value_hex;
      for ($i=0; $i < (64-strlen($value_hex)); $i++) {
          $format_str = '0'.$format_str;
      }      
      $params = [
          "from" =>  $from,
          "to" => $contract,
          "value" => '0x0',
          "gas" => "0xcb20",
          "gasPrice" => "0x218711a00",
          "data" => "0xa9059cbb000000000000000000000000".substr($to,2).$format_str,
      ];

      $data = $this->generateRequestData("eth_sendTransaction", [(object) $params]);

      $result = $this->request(json_encode($data));

      //var_dump($result);
      
      $result_arr = json_decode($result,true);
      
      $this->lock_Accounts($from);

      var_dump($result_arr);

      if(!isset($result_arr['result'])){
          return array('code'=>0,'msg'=>$result,'data'=>'');
      }
      $ret['tx_id'] = $result_arr['result'];
      return array('code'=>1,'msg'=>'','data'=>$ret);
  }

  //查询所有地址
  public function get_Block($block_height)
  {      
      $block_height_hex = "0x".dechex($block_height);
      $params = [$block_height_hex,true];
      $data = $this->generateRequestData("eth_getBlockByNumber", $params);
      $result =  $this->request(json_encode($data));
      $result_arr = json_decode($result,true);

      if(!isset($result_arr['result'])){
        return array('code'=>0,'data'=>$result);
      }else{
        return array('code'=>1,'data'=>$result_arr);        
      }
  }


  //查询所有地址
  public  function get_Accounts()
  {       
      $data = $this->generateRequestData("eth_accounts");
      return $this->request(json_encode($data));
  }

    /**
     * 获取交易列表
     * @return
     */
    public function get_Transactions($start_block)
    {
      //先获取当前区块高度
      $ret = $this->get_BlockNumber();
      if($ret['code'] == 0){
        return array('code'=>0,'data'=>"get_BlockNumber fail");
      }
      $end_block = $ret['data']['block_num']-2; //减去两个区块 保证已经交易确认
      if(empty($start_block)){
         $start_block = $end_block - 1;
      }
      $transactions = array();
      $lastblock = $start_block;
      $end_block = min($start_block+2,$end_block);
      for($i=$start_block+1;$i<=$end_block;$i++){
        $ret = $this->get_Block($i);
        if($ret['code']==1){
          //var_dump( $ret['data']['result']['transactions']);
          if(isset($ret['data']['result']['transactions'])){
            $transaction_list =  $ret['data']['result']['transactions'];
            foreach ($transaction_list as $key => $value) {
              array_push($transactions, $value);
            }
            //var_dump($transactions);
            $lastblock = $i;           
          }

        }
      }
      $result['transactions']=$transactions;
      $result['lastblock']= $lastblock;
      return array('code'=>1,'data'=>$result);
    }    

    //查询交易收据log
    public function get_TransactionReceipt($hash)
    {      
        $params = [$hash];
        $data = $this->generateRequestData("eth_getTransactionReceipt", $params);
        $result =  $this->request(json_encode($data));
        $result_arr = json_decode($result,true);
        if(!isset($result_arr['result'])){
          return array('code'=>0,'data'=>$result);
        }else{
          return array('code'=>1,'data'=>$result_arr['result']);        
        }
    }

  // transfer
  public  function send_Transactions($from, $to, $value, $pwd){

      $unlockInfo = json_decode($this->unlock_Accounts($from, $pwd));
      if (!isset($unlockInfo->result) || !$unlockInfo->result ){
          return $unlockInfo;
      }

      $result = $this->sendTransaction($from, $to, $value);

      $result_arr = json_decode($result,true);
      
      $this->lock_Accounts($from);

      if(!isset($result_arr['result'])){
          return array('code'=>0,'msg'=>$result,'data'=>'');
      }
      $ret['tx_id'] = $result_arr['result'];
      return array('code'=>1,'msg'=>'','data'=>$ret);
  }

    public  function eth_gasPrice()
    {
        $data = $this->generateRequestData("eth_gasPrice");
        return $this->request(json_encode($data));
    }

    public  function eth_getTransactionCount($params)
    {
        $data = $this->generateRequestData("eth_getTransactionCount", $params);
        return $this->request(json_encode($data));
    }

    public  function sendRawTransaction($addr, $raw)
    {
        $params = [$raw];
        $data = $this->generateRequestData("eth_sendRawTransaction", $params);
        $result = $this->request(json_encode($data));
        $result_arr = json_decode($result,true);
        if(!isset($result_arr['result'])){
            return array('code'=>0,'data'=>$result_arr['error']['message']);
        }
        return array('code'=>1,'data'=>$result_arr['result']);
    }

    public  function eth_call($params)
    {
        $data = $this->generateRequestData("eth_call", $params);
        $result = $this->request(json_encode($data));
        return $result;
    }


  public  function unlock_Accounts($addr, $pwd)
  {   
      $duration = 60;
      $params = [$addr, $pwd, $duration];
      $data = $this->generateRequestData("personal_unlockAccount", $params);
      return $this->request(json_encode($data));
  }

	//查询子地址时给出的是1
  public  function lock_Accounts($addr)
  {
      $params = [$addr];
      $data = $this->generateRequestData("personal_lockAccount", $params);
      return $this->request(json_encode($data));
  }
  public  function filterchanges($filterid)
  {
    $params = [$filterid];
    $data = $this->generateRequestData("eth_getFilterLogs", $params);
    return $this->request(json_encode($data));
  }

  public  function newFilter($address)
  {
      return $this->_newFilter($address);
  }


  public  function _newFilter($address, $fromBlock = 0, $toBlock = 0, $topics = [])
  {
      $params = [
          "address" => $address,
      ];
      if ($fromBlock > 0) {
          $params['fromBlock'] = $fromBlock;
      }
      if ($toBlock > 0) {
          $params['toBlock'] = $toBlock;
      }
      if (!empty($topics)) {
          $params['topics'] = $topics;
      }
      $data = $this->generateRequestData("eth_newFilter", [(object) $params]);
      //var_dump(json_encode($data));
      return $this->request(json_encode($data));
  }



  // manual transfer, unlock first, then sendTransfer, and lock account last;
  public  function sendTransaction($from, $to, $value)
  {
      return $this->_sendTransaction($from, $to, $value);
  }

  // send transaction full pars
  public  function _sendTransaction($from, $to, $value, $gas = 0, $gasPrice = 0, $data = "", $nonce = 0)
  {
      $params = [
          "from" => $from,
          "to" => $to,
          "value" => $this->toHexWei($value),
      ];
      if ($gas > 0) {
          $params['gas'] = $gas;
      }
      $params['gas'] = "0x5208";
      if ($gasPrice > 0) {
          $params['gasPrice'] = $gasPrice;
      }
      $params['gasPrice'] = "0x342770c00";
      if (strlen($data) > 0) {
          $params['data'] = $data;
      }
      if ($nonce > 0) {
          $params['nonce'] = $nonce;
      }
      //var_dump($params);
      //var_dump($value);
      //die();
      $data = $this->generateRequestData("eth_sendTransaction", [(object) $params]);
      //echo "<pre>";
      //print_r(json_encode($data));die();
      return $this->request(json_encode($data));
  }

  public  function getTransactionByHash($hash)
  {
      $this->validateEthAddress($hash);
      $params = [$hash];
      $data = $this->generateRequestData("eth_getTransactionByHash", $params);
      return $this->request(json_encode($data));
  }

  public  function toDec($hexAmount)
  {
      return hexdec($hexAmount);
  }

	//hash查询结果中的value值  转换为10进制
  public  function toEth($hexAmount)
  {
      return $this->toDec($hexAmount) / $this->h;
  }

  public  function toWei($eth)
  {
      return $eth * $this->h;
  }

  public  function toHex($eth)
  {
      return "0x" . dechex($eth);
  }

  public  function toHexWei($eth)
  {
      //return "0x" . dechex($this->toWei($eth));
      return "0x" . base_convert($this->calc($eth, $this->hs, "mul"), 10, 16);
  }

  public  function bc_dechex($number)
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

  private  function validateEthAddress($addr)
  {
      if (strlen($addr) < 10 || substr($addr, 0, 2) != "0x") {
          //echo "Invalid address:". $addr;
          //exit();
          return 0;
      }else{
          return 1;
      }
  }

  private  function generateRequestData($method, $params = [])
  {
      $data = [
          "jsonrpc" => $this->v,
          "method" => $method,
          "params" => $params,
          "id" => mt_rand(1, 100000000),
      ];
      return $data;
  }

  private  function request($post_data)
  {

      if (strlen($this->address) <= 0 || $this->port <= 0) {
          echo "eth client address or port error";
          exit();
      }
      $url = $this->address . ":" . $this->port;
      return $this->post($url, $post_data);
  }

  // for big number
  private  function calc($m, $n, $x)
  {
      $errors = array(
          '被除数不能为零',
          '负数没有平方根'
      );
      switch ($x) {
          case 'add':
              $t = bcadd($m, $n);
              break;
          case 'sub':
              $t = bcsub($m, $n);
              break;
          case 'mul':
              $t = bcmul($m, $n);
              break;
          case 'div':
              if ($n != 0) {
                  $t = bcdiv($m, $n);
              } else {
                  return $errors[0];
              }
              break;
          case 'pow':
              $t = bcpow($m, $n);
              break;
          case 'mod':
              if ($n != 0) {
                  $t = bcmod($m, $n);
              } else {
                  return $errors[0];
              }
              break;
          case 'sqrt':
              if ($m >= 0) {
                  $t = bcsqrt($m);
              } else {
                  return $errors[1];
              }
              break;
      }
      $t = preg_replace("/\..*0+$/", '', $t);
      return $t;
  }

  // curl for request
  private  function post($url, $post_data = '', $timeout = 5)
  {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
      if ($post_data != '') {
          curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
      curl_setopt($ch, CURLOPT_HEADER, false);
      $file_contents = curl_exec($ch);
      curl_close($ch);
      return $file_contents;
  }
}

?>
