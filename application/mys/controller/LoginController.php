<?php

namespace app\mys\controller;


use app\common\model\UserLoginModel;
use app\common\model\UserModel;
use app\mys\validate\LoginValidate;
use app\mys\validate\RegisterValidate;
use geetest\Geetest;
use think\facade\Config;
use think\facade\Request;
use think\facade\Session;
use think\Controller;

class LoginController extends Controller
{
    public function index(){
/*        $session = Session::get('user_login');
        if($session){
            $this->redirect(url('mytempsms/user/index'));
        }*/
        return $this->fetch();
    }

    public function login(){
        $data = input('post.');
        $validate = new LoginValidate();
        //极验证判断
        if (self::validateGeetest($data) == 'fail'){
            return show('验证失败,请重新验证', '', 4000);
        }
        
        if (!$validate->check($data)){
            return show($validate->getError(), '', 4000);
        }
        $result = (new UserModel())->getUserInfo($data['name']);
        if ($result){
            $verify_md5 = $result['password'];
            $solt = $result['solt'];
            $md5 = md5($data['password'] . $solt);
            if ($verify_md5 != $md5){
                return show('用户名或密码错误', '', 4000);
            }
            //写入登陆记录
            $data['user_id'] = $result['id'];
            $data['ip'] = $_SERVER["REMOTE_ADDR"];
            //(new UserLoginModel())->createUserLogin($data);
            Session::set('user_login', $data['name']);
            return show('登陆成功');
        }else{
            return show('登陆失败', '', 4000);
        }

    }

    public function register(){
        return $this->fetch('login/register');
    }

    public function beginRegister(){
        $data = input('post.');
        $validate = new RegisterValidate();
        //极验证判断
        if (self::validateGeetest($data) == 'fail'){
            return show('验证失败,请重新验证', '', 4000);
        }
        if (trim($data['register_code']) !== 'swdk') {
            return show('注册码错误', '', 4000);
        }
        if (!$validate->check($data)){
            return show($validate->getError(),'', 5000);
        }
        //写入数据
        //判断该用户是否存在
        $name = (new UserModel())->getUserInfo($data['name']);
        if ($name){
            return show('该用户已经存在','', 5000);
        }
        //dump($name);die;
        $data = Request::only(['name','password']);
        $data['solt'] = getRandChar(6);
        $data['type'] = 1;
        $data['password'] = md5($data['password'] . $data['solt']);
        $data['type'] = 2;
        //dump($data);die;
        $result = (new UserModel())->insertUser($data);
        if ($result){
            return show('注册用户成功', $data['name']);
        }else{
            return show('注册失败','', 5000);
        }
    }

    //GT校验
    public function geetestSession()
    {
        $data = array(
            "user_id" => $_SERVER['REQUEST_TIME'], # 网站用户id
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => '127.0.0.1' # 请在此处传输用户请求验证时所携带的IP
        );

        $GtSdk = new Geetest(Config::get('config.geetest.captcha_id'), Config::get('config.geetest.private_key'));
        $status = $GtSdk->pre_process($data, 1);
        Session::set('gtserver', $status);
        Session::set('user_id', $data['user_id']);
        echo $GtSdk->get_response_str();
    }

    //GT后端登陆检查,判断走哪个通道
    public function validateGeetest($data)
    {
        $value = array(
            "user_id" => Session::get('user_id'), # 网站用户id
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => '127.0.0.1' # 请在此处传输用户请求验证时所携带的IP
        );
        $GtSdk = new Geetest(Config::get('config.geetest.captcha_id'), Config::get('config.geetest.private_key'));
        if (Session::get('gtserver') == 1) {   //服务器正常
            $result = $GtSdk->success_validate($data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode'], $value);
            if ($result) {
                return 'success';
            } else {
                return 'fail';
            }
        } else {  //服务器宕机,走failback模式
            if ($GtSdk->fail_validate($data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode'])) {
                return 'success';
            } else {
                return 'fail';
            }
        }
    }

    public function loginout(){
        Session::delete('user_login');
        //$this->redirect('/public');
        $this->redirect(url('/'));
    }
}