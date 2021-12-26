<?php

namespace app\mys\controller;

use think\Controller;
use think\facade\Session;

class SessionLoginController extends Controller
{
    public function initialize(){
        $session = Session::get('user_login');
        if(!isset($session)){
            $this->redirect(url('/login'));
        }
    }
}