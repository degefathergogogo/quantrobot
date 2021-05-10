<?php

/**
 * 生成二维码
 */
namespace api\wallet\controller;

use cmf\phpqrcode\QRcode;

class QrcodeController  {

    public function make($url) {
  		
    	$size = '10';
		
		$level = 'L';
		
		$QRcode = new QRcode();
		
		$QRcode::png($url, false, $level, $size);
		
		die();
    }
    

}

