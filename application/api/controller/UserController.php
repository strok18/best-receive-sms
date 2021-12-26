<?php


namespace app\api\controller;


use app\common\model\UserModel;
use app\mytempsms\controller\SessionLoginController;
use think\facade\Session;

class UserController extends SessionLoginController
{
    public function getUserMoney(){
        $user = Session::get('user_login');
        $user_model = new UserModel();
        $result = $user_model->getFieldValue($user, 'money');
        $data['user'] = $user;
        $data['money'] = $result;
        return show('获取成功', $data);
    }
}