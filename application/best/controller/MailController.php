<?php

namespace app\best\controller;


use think\facade\Config;
use think\facade\Lang;
use think\facade\Session;
use think\facade\Request;
use think\Controller;
use app\common\controller\RedisController;

class MailController extends Controller
{
    protected $middleware = [
        'InitM' => ['only' => ['index']]
    ];
    
    public function index(){
        $email = Session::get('email');
        if ($email){
            $this->assign('email', $email);
        }
        
        $redis = new RedisController('sync');
        $mails_data = $redis->zRevRange(Config::get('cache.prefix') . 'mails', 0 , 10);
        $mails = [];
        foreach ($mails_data as $key => $value){
            $mails[$key]['site'] = $value;
        }
        $this->assign('mails', $mails);
        $this->assign('mail_heads', $this->generateHeads());
        return $this->fetch();
    }

    /**
     * 返回头部title description keywords信息
     */
    public function generateHeads(){
        $heads['title'] = Lang::get('mail_title');
        $heads['description'] = Lang::get('mail_description');
        $heads['keywords'] = Lang::get('mail_keywords');
        return $heads;
    }
}