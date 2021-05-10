<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/30
 * Time: 12:07
 */

namespace api\wallet\service;

use think\Controller;
use think\Db;
use Think\Exception;

class Apibase extends Controller
{
    /**
     * 判断是否SSL协议
     * @return boolean
     */
    public static function is_ssl() {
        if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){
            return true;
        }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
            return true;
        }
        return false;
    }
    /**
     * 返回带协议的域名
     */
    public static function sp_get_host(){
        $host=$_SERVER["HTTP_HOST"];
        $protocol=self::is_ssl()?"https://":"http://";
        return $protocol.$host;
    }
    /**
     * @param $num         科学计数法字符串  如 2.1E-5
     * @param int $double 小数点保留位数 默认18位
     * @return string
     */

    public static function sctonum($num, $double = 18){
        if(false !== stripos($num, "e")){
            $a = explode("e",strtolower($num));
            $b = bcmul($a[0], bcpow(10, $a[1], $double), $double);
            $c = rtrim($b, '0');
            return $c;
        }else{
            return $num;
        }
    }

    public static function updateBalance($type,$uid,$balance_type,$change,$detial,$detial_type,$extension){
        $model = Db::name("user");
        $amount = $model->where(['id'=>$uid])->value($balance_type);
        Db::startTrans();
        try {
            $ff = ($change < 0) ? '-' : '+';
            $temp = [
                $balance_type => Db::raw("{$balance_type}{$ff}".abs($change))
            ];
            $set = $model->where(['id'=>$uid])->update($temp);
            $new_balance = $change + $amount;

            if ($set){
                $log = array(
                    'type' => $type,
                    'user_id' => $uid,
                    'balance_type' => $balance_type,
                    'change' => $change,
                    'amount' => $new_balance,
                    'detial' => $detial,
                    'detial_type' => $detial_type,
                    'ctime' => time(),
                    'extension' => $extension,
                );
                $log_id = Db::name("BalanceLog")->insert($log);
                if ($log_id){
                    // 提交事务
                    Db::commit();
                }
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

    //根据ID计算唯一邀请码
    public static function createCode($Id){
        static $sourceString = [
            0,1,2,3,4,5,6,7,8,9,10,
            'a','b','c','d','e','f',
            'g','h','i','j','k','l',
            'm','n','o','p','q','r',
            's','t','u','v','w','x',
            'y','z'
        ];

        $num = $Id;
        $code = '';
        while($num)
        {
            $mod = $num % 36;
            $num = (int)($num / 36);
            $code = "{$sourceString[$mod]}{$code}";
        }

        //判断code的长度
        if( empty($code[4]))
            str_pad($code,5,'0',STR_PAD_LEFT);

        return $code;
    }

    /*
     * 依据邀请奖励规则发放奖励
     * uid  自身uid
     * parent_tree 上级用户ID树
     * type 场景类型    reg：注册/deposit:存入
     * detial
     */
    public static function invite_reward($uid,$parent_tree,$type,$detial=''){
        $uids = explode('|',$parent_tree);
        if (empty($uids)){
            return false;
        }
        //查询邀请奖励规则
        $reward_rule = Db::name("UserInviteReward")->where(['status'=>1])->select()->toArray();
        if (!empty($reward_rule)){
            $reward_rule = array_column($reward_rule,NULL,'type');
        }
        foreach ($uids as $k => $x){
            $x = (int)$x;
            if ($x > 0){
                if (isset($reward_rule[$k+1]) && !empty($reward_rule[$k+1])){
                    $rule = $reward_rule[$k+1];
                    $parent_need_allocation = $rule["{$type}_parent_reward"];
                    $offspring_need_allocation = $rule["{$type}_offspring_reward"];
                    $rule['parent_tree'] = $parent_tree;
                    if ($parent_need_allocation > 0){
                        $need_detial = empty($detial) ? $uid : $detial;
                        self::updateBalance(1,$x,'score',(int)$parent_need_allocation,$need_detial,'invite_reward_'.(string)$rule['type'].'_'.$type.'_parent_score_income',json_encode($rule));
                    }
                    if ($offspring_need_allocation > 0){
                        self::updateBalance(1,$uid,'score',(int)$offspring_need_allocation,$uid,'invite_reward_'.(string)$rule['type'].'_'.$type.'_offspring_score_income',json_encode($rule));
                    }
                }
            }
        }
        return true;
    }

    /**
     * 获取随机字符串
     *
     * @param $length
     * @param bool $numeric
     * @return string
     */
    public static function random($length, $numeric = false)
    {
        $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));

        if ($numeric)
        {
            $hash = '';
        }
        else
        {
            $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
            $length--;
        }

        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++)
        {
            $hash .= $seed{mt_rand(0, $max)};
        }

        return $hash;
    }

    /**
     * 获取数字随机字符串
     *
     * @param bool $prefix 判断是否需求前缀
     * @param int $length 长度
     * @return string
     */
    public static function randomNum($prefix = false, $length = 8)
    {
        $str = $prefix ? $prefix : '';
        return $str . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, $length);
    }

    public static function timetodate($c){
        if($c < 86400){
            $time = explode(' ',gmstrftime('%H %M %S',$c));
            $duration = $time[0].'小时'.$time[1].'分'.$time[2].'秒';
        }else{
            $time = explode(' ',gmstrftime('%j %H %M %S',$c));
            $duration = ($time[0]-1).'天'.$time[1].'小时'.$time[2].'分'.$time[3].'秒';
        }
        return $duration;
    }

    /*
     * 推送方法
     */
    public static function send_push($type,$title,$content,$url='',$uids=[]){
        Db::startTrans();
        $code = 0;
        try{
            $log = [
                'type' => $type,
                'title' => $title,
                'content' => $content,
                'url' => $url,
                'status' => 1,
                'ctime' => time(),
            ];
            $data = [];
            if (!empty($uids)){
                foreach ($uids as $x){
                    $log['user_id'] = $x;
                    array_push($data,$log);
                }
            }else{
                $log['user_id'] = 0;
                array_push($data,$log);
            }
            if (!empty($data)){
                $log_id = Db::name('PushLog')->insertAll($data);
                if ($log_id){
                    Db::commit();
                    $code = 1;
                }
            }
        }catch (\Exception $e) {

        }
        return $code;
    }

    /*
     * 具体发送推送函数
     */
    public static function jpush_send_push($title,$content,$type,$url,$registration_id = 0,$os='android'){
        $code = 0;
        $message = '';
        $app_conf = cmf_get_option("app_config");
        if (empty($app_conf['jpush_app_key']) || empty($app_conf['jpush_master_secret'])){
            $message = '请先设置推送配置';
        }else {
            $client = new \JPush\Client($app_conf['jpush_app_key'], $app_conf['jpush_master_secret']);

            $msg = array(
                'title' => $title,
                'extras' => array(
                    'type' => $type,
                    'content' => $content,
                    'url' => $url,
                ),
            );
            $pusher = $client->push()
                ->setPlatform('all')
                ->options(array(
                    // apns_production: 表示APNs是否生产环境，
                    // True 表示推送生产环境，False 表示要推送开发环境；如果不指定则默认为推送生产环境
                    'apns_production' => APP_DEBUG ? false : true,
                ));
            if (!empty($registration_id)){
                if (strtolower($os) == 'android'){
                    $pusher->androidNotification($content, $msg);
                }else{
                    $msg['alert'] = $title;
                    unset($msg['title']);
                    $pusher->iosNotification(['title'=>$title,'body'=>$content], $msg);
                }
                $pusher->addRegistrationId($registration_id);
            }else{
                $pusher->addAllAudience();
                $pusher->androidNotification($content, $msg);
                $msg['alert'] = $title;
                unset($msg['title']);
                $pusher->iosNotification(['title'=>$title,'body'=>$content], $msg);
            }
            try {
                $rst = $pusher->send();
                if (!empty($rst['body']['msg_id'])){
                    $code = 1;
                    $message = '成功';
                }else{
                    $message = '推送失败';
                }
            } catch (\JPush\Exceptions\JPushException $e) {
                $message = '推送失败';
            }
        }
        return ['code'=>$code,'msg'=>$message];
    }

    /*
     * 获取extension配置项
     */
    public static function get_extension($type){
        $rst = Db::name("Extension")
            ->where(['type'=>$type])
            ->value('detial');
        return empty($rst) ? null : $rst;
    }

    /*
     *
     */
    public static function chain_rpc($coin,$userId,$wallet_type,$method,$params=[]){
        $coin = strtolower($coin);
        $vendor_name = "Chain.".$coin."rpc";
        Vendor($vendor_name);
        $class_name = "\\".$coin."rpc";
        if(!class_exists($class_name)){
            return array('code'=>0,'msg'=>"$class_name not exists");
        }
        //检测钱包是否存在
        $fieldStr = 'rpc_ip,rpc_port,rpc_user,rpc_pass,b.id,b.address,IFNULL(b.status,-1) as status';
        $coin_data = Db::name('coin')
            ->alias('a')
            ->join(config('database.prefix').'wallet b',"a.coin_symbol = b.coin_symbol and uid = $userId and type = $wallet_type","LEFT")
            ->field($fieldStr)
            ->where('a.coin_symbol', strtoupper($coin))
            ->find();
        if(empty($coin_data)){ //没有该币种
            return array('code'=>0,'msg'=>"coin symbol not exists");
        }
        if(empty($coin_data['address'])){
            return array('code'=>0,'msg'=>"user wallet not exists");
        }
        //币种rpc配置
        $rpc_ip = $coin_data['rpc_ip'];
        $rpc_port = $coin_data['rpc_port'];
        $rpc_user = $coin_data['rpc_user'];
        $rpc_pass = $coin_data['rpc_pass'];
        if(empty($rpc_ip)||empty($rpc_port)){
            return array('code'=>0,'msg'=>"rpc ip or port not set");
        }
        //RPC连接
        $rpc = new $class_name($rpc_ip,$rpc_port,$rpc_user,$rpc_pass);
        if (!method_exists($rpc,$method)) {
            return array('code'=>0,'msg'=>"$class_name method $method not exists");
        }
        $ret = $rpc->$method($params);
        $code = 0;
        $msg = '';
        $data = $ret;
        try {
            $ret = json_decode($ret);
            $code = 1;
            $data = $ret->result;
        }catch (Exception $e){

        }
        return ['code'=>$code,'msg'=>$msg,'data'=>$data];
    }

    /*
     * BTC查unspent
     */
    public static function btc_unspent($addr){
        try{
            $NODE_HOST = "https://blockchain.info/unspent";
            $url = $NODE_HOST . '?active=' . $addr;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Blockchain-PHP/1.0');
            //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
            curl_setopt($ch, CURLOPT_CAINFO, VENDOR_PATH.'/Chain/blockchain-ca-bundle.crt');
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $ret = curl_exec($ch);
            curl_close($ch);

            if (false === $ret) {
                $code = 0;
                $rst = curl_errno($ch);
            }else{
                if(strpos($ret, 'unspent_outputs') !== false){
                    $ret = json_decode($ret, true);
                    if(!empty($ret['unspent_outputs'])){
                        $code = 1;
                        $rst = $ret['unspent_outputs'];
                        foreach ($rst as $k => &$v){
                            if($v['confirmations'] < 6){
                                unset($rst[$k]);
                            }
                        }
                    }else{
                        $code = 0;
                        $rst = 'unspent error';
                    }
                }else{
                    if($ret == "No free outputs to spend"){
                        $code = 1;
                        $rst = 0;
                    }else{
                        $code = 0;
                        $rst = $ret;
                    }
                }
            }
        }catch(Exception $e){
            $code = 0;
            $rst = $e->getMessage();
        }
        return array(
            'code' => $code,
            'data' => $rst
        );
    }

    public static function btc_unspent_v2($addr){
        try{
            $NODE_HOST = "https://chain.api.btc.com/v3/address/";
            $url = $NODE_HOST . $addr . '/unspent';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Blockchain-PHP/1.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
            //curl_setopt($ch, CURLOPT_CAINFO, VENDOR_PATH.'/Chain/blockchain-ca-bundle.crt');
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $ret = curl_exec($ch);
            curl_close($ch);

            if (false === $ret) {
                $code = 0;
                $rst = curl_errno($ch);
            }else{
                $ret = json_decode($ret,true);
                if (empty($ret['data']['list'])){
                    $code = 1;
                    $rst = 0;
                }else{
                    $code = 1;
                    $rst = $ret['data']['list'];
                    foreach ($rst as $k => &$v){
                        if($v['confirmations'] < 6){
                            unset($rst[$k]);
                        }
                    }
                }
            }
        }catch(Exception $e){
            $code = 0;
            $rst = $e->getMessage();
        }
        return array(
            'code' => $code,
            'data' => $rst
        );
    }

    public static function calcbtcbalance($unspent){
        $values = array_column($unspent,'value');

        $total = array_sum($values);
        return $total / 100000000;
    }
}