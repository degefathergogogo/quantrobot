<?php
namespace app\user\controller;

use cmf\controller\AdminBaseController;
use cmf\lib\Upload;
use think\View;


class AdminDemoController extends AdminBaseController
{
    public function table(){
        return $this->fetch();
    }
    public function chart(){
        return $this->fetch();
    }
    public function form(){
        return $this->fetch();
    }
}