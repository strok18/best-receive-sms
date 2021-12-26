<?php


namespace app\mys\controller;
use app\common\controller\RedisController;
use think\Controller;

class WangpanController extends Controller
{
    protected $middleware = [
        'InitM' => ['only' => ['index']]
    ];
    
    public function password(){
    	$redis = new RedisController();
        $success = $redis->redisCheck('wangpan_password_success');
        $failed = $redis->redisCheck('wangpan_password_failed');
        $this->assign('precision', round($success/($success+$failed), 4) * 100);
        return $this->fetch('password');
    }
}