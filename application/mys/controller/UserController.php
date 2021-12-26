<?php
namespace app\mys\controller;


use think\facade\Request;
use think\facade\Session;

class UserController extends SessionLoginController
{
    public function index(){
        return $this->fetch('user');
    }

    public function receiveSMS(){
        return $this->fetch('receive_sms');
    }

    //购买
    public function buy(){
        return $this->fetch('buy');
    }

    //订单,已购的号码
    public function order(){

        return $this->fetch('order');
    }

    //获取
    public function getSMS(){
        
    }
}