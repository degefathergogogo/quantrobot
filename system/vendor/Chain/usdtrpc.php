<?php
class usdtrpc
{
    private $username;
    private $password;
    private $proto;
    private $host;
    private $port;
    private $url;
    private $CACertificate;
    public $status;
    public $error;
    public $raw_response;
    public $response;
    private $id = 0;
    private $ewt_address = '';//钱包账号
    private $ewt_propertyid = 1;//USDT:31,USDT:1...
    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $proto
     * @param string $url
     */
    public function __construct($host,$port,$user,$pass,$protocal='http')
    {

        $this->host          = $host;
        $this->port          = $port;
        $this->username      = $user;
        $this->password      = $pass;
        $this->proto         = $protocal;        
        $this->url           = null;
        $this->CACertificate = null;
    }
    /**
     * USDT产生地址
     * @return
     */
    public function get_NewAddress($uid)
    {
      $uid = strval($uid);
      $result = $this->getnewaddress([$uid]);
      if(!$this->isError($result))
      {
        return array('code'=>0,'data'=>$result);
      }
      $addr = $result;
      $ret['seed'] = "";
      $ret['memo'] = "";
      $ret['address'] = $addr;
      if(strlen($addr) < 16){
          return array('code'=>0,'data'=>$result);             
      }else{
          return array('code'=>1,'data'=>$ret);           
      }
    }
    /**
     * USDT查询余额
     * @return
     */
    public function get_Balance($address)
    {
      $result = $this->omni_getbalance([$address,31]);
      if(!$this->isError($result))
      {
        return array('code'=>0,'data'=>$result);
      }

      $ret['balance'] = $result['balance'];
      return array('code'=>1,'data'=>$ret);      
    }
  
    /**
     * 查询BTC余额，已废弃
     * @return
     */
    public function get_Balance_BTC($address)
    {
      $result = $this->omni_getbalance([$address,0]);
      if(!$this->isError($result))
      {
        return array('code'=>0,'data'=>$result);
      }

      $ret['balance'] = $result['balance'];
      return array('code'=>1,'data'=>$ret);      
    }



    /**
     * 新查询BTC余额
     * listunspent 6 999999 ["16uHtj5js7SmhR1vURffFQDBNoXFiKFvBU"]
     * @return
     */
    public function get_Balance_BTC_New($address)
    {
		$value = strval($address);
		$param = [6,999999,[$address]];
		$ret = $this->listunspent($param);
		//var_dump($ret);die('00000');
		
		
		if(is_array($ret))
		{
			return json_encode(array('code'=>1,'data'=>$ret));
		}
		return json_encode(array('code'=>0,'data'=>$ret));

    }


  
    public function get_Balance_mybank($ewt_address,$ewt_propertyid)
    {
      $ret = $this->omni_getbalance($ewt_address,$ewt_propertyid);
      if(!$this->isError($ret))
      {
        return json_encode(array('code'=>"200",'message'=>'onSucc','data'=>$ret));
      }
      return json_encode(array('code'=>"500",'message'=>'onFail','data'=>$ret));
    }
  
  
    public function send_Transactions($from, $to, $value, $pwd){
        $value = strval($value);
        $param = [$from,$to,31,$value];
        $result = $this->omni_send($param);
        if(!$this->isError($result))
        {
            return array('code'=>0,'data'=>$result);
        }

        $ret['tx_id'] = $result;
        return array('code'=>1,'data'=>$ret);     
    }
    /**
     * USDT发送raw交易
     * @return
     */
    public function sendRawTransaction($raw)
    {
        $result = $this->sendrawtransaction($raw);
        if(!$this->isError($result))
        {
            return array('code'=>0,'data'=>$result);
        }
        return array('code'=>1,'data'=>$result);
    }

    /**
     * USDT获取区块高度
     * @return
     */
    public function get_BlockNumber()
    {
      $result = $this->getblockcount();

      if(!$this->isError($result))
      {
        return array('code'=>0,'data'=>$result);
      }
      $ret['block_num'] = $result;
      return array('code'=>1,'data'=>$ret);
    }


    /**
     * USDT获取区块
     * @return
     */
    public function get_Block($block_num)
    {
      $result = $this->getblock($block_num);

      if(!$this->isError($result))
      {
        return array('code'=>0,'data'=>$result);
      }
      $ret['block'] = $result;
      return array('code'=>1,'data'=>$ret);
    }    
    /**
     * USDT获取交易列表
     * @return
     */
    public function get_Transactions($pos)
    {
      $pos = intval($pos);
      $param = ["*",10000,0,$pos];
      $result = $this->omni_listtransactions($param);

      if(!is_array($result))
      {
        return array('code'=>0,'data'=>$result);
      }

      foreach ($result as $key => $value) {
        if (!isset($value['block'])) {
          unset($result[$key]);
        }
      }
      $result = array_values($result);


      $ret['transactions'] = $result;
      if(count($result)>0){
         if ($result[0]['block'] > 5000) {
            $ret['lastblock'] = $result[0]['block']+1; 
         }else{
            $ret['lastblock'] = $pos; 
         }
      }else{
        $ret['lastblock'] = $pos; 
      }      

      return array('code'=>1,'data'=>$ret);
    }    

    /**
     * USDT转帐
     * @param toaddress
     * @param amount
     * @return
     */
    public function get_Sendto($ewt_address,$toaddress,$ewt_propertyid,$amount)
    {
      $ret = $this->omni_send($ewt_address,$toaddress,$ewt_propertyid,$amount);
      if(!$this->isError($ret))
      {
        return json_encode(array('code'=>1,'data'=>$ret));
      }
      return json_encode(array('code'=>0,'data'=>$ret));

    }
    /**
     * 验证地址的有效性
     * @param address
     * @return
     */
    public function vailed_Address($address)
    {
      $ret = $this->validateaddress($address);
      if(!$this->isError($ret))
      {
        return json_encode(array('code'=>1,'data'=>$ret));
      }
      return json_encode(array('code'=>0,'data'=>$ret));

    }
    /**
     * 交易确认
     * @param txid
     * @param txt
     * @return
     */
    public function parse_Trade($txid)
    {

      $ret = $this->omni_gettransaction($txid);
      if(!$this->isError($ret))
      {
        if(!strpos($ret,$this->ewt_propertyid))
        {
          return json_encode(array('code'=>0,'data'=>'非USDT交易'.$ret));
        }
        return json_encode(array('code'=>1,'data'=>$ret));
      }

      return json_encode(array('code'=>0,'data'=>$ret));

    }
    public function isError($body)
    {
      if(is_array($body)){
        $body = json_encode($body);
      }
      if(strpos($body,'error')||strpos($body,'Failed')||strpos($body,' ')||$body===false||$body===null)
      {
        return false;
      }
      return true;
    }
    /**
     * @param string|null $certificate
     */
    public function setSSL($certificate = null)
    {
        $this->proto         = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }
    public function __call($method, $params)
    {
   
        if (isset($params[0])) {
           $params = $params[0];
        }
        $this->status       = null;
        $this->error        = null;
        $this->raw_response = null;
        $this->response     = null;
        // If no parameters are passed, this will be an empty array
       // $params = array_values($params);
        // The ID should be unique for each call
        $this->id++;
        // Build the request, it's ok that params might have any empty array

        $request = json_encode(array(
            'method' => $method,
            'params' => $params,
            'id'     => $this->id
        ));
       //var_dump($request);
       //die('88888');
        // Build the cURL session
        $curl    = curl_init("{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
        $options = array(
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $request
        );
        // This prevents users from getting the following warning when open_basedir is set:
        // Warning: curl_setopt() [function.curl-setopt]:
        //   CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
        if (ini_get('open_basedir')) {
            unset($options[CURLOPT_FOLLOWLOCATION]);
        }
        if ($this->proto == 'https') {
            // If the CA Certificate was specified we change CURL to look for it
            if (!empty($this->CACertificate)) {
                $options[CURLOPT_CAINFO] = $this->CACertificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
            } else {
                // If not we need to assume the SSL cannot be verified
                // so we set this flag to FALSE to allow the connection
                $options[CURLOPT_SSL_VERIFYPEER] = false;
            }
        }
        curl_setopt_array($curl, $options);
        // Execute the request and decode to an array
        $this->raw_response = curl_exec($curl);
      //var_dump($this->raw_response);
        $this->response     = json_decode($this->raw_response, true);
      //var_dump($this->response);
        // If the status is not 200, something is wrong
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // If there was no error, this will be an empty string
        $curl_error = curl_error($curl);
        curl_close($curl);
        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }
        if ($this->response['error']) {
            // If bitcoind returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        } elseif ($this->status != 200) {
            // If bitcoind didn't return a nice error message, we need to make our own
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }
        if ($this->error) {
            return $this->error;
        }
        return $this->response['result'];
    }
}
