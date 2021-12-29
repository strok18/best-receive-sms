<?php


namespace app\best\controller;


use app\common\controller\RedisController;
use app\common\model\CollectionMsgModel;
use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;
use think\Controller;

class ProjectController extends Controller
{
    protected $middleware = [
        'InitM' => ['only' => ['index']]
    ];
    
    public function index(){
        $project = Request::param('project');
        $result_sms = (new CollectionMsgModel())->getProjectMessage($project);
        //插入广告
        /*if (count($result_sms) > 2){
            array_splice($result_sms, 2, 0, 'Adsense');
        }
        if (count($result_sms) > 14){
            array_splice($result_sms, 10, 0, 'Adsense');
        }*/
        //获取推荐关键字
        $redis = new RedisController('sync');
        $redis_key = Config::get('cache.prefix') . 'project_recommend';
        $recommend = $redis->getSetAllValue($redis_key);
        $this->assign('recommend', $recommend);
        $this->assign('data', $result_sms);
        $this->assign('project_heads', $this->generateHeads($project));
        $this->assign('empty', '<div class="text-center"><img src="/static/web/images/empty.svg"><p class="fw-bold">NO PHONE NUMBER</p></div>');
        return $this->fetch();
    }

    /**
     * 数据表内容读取
     */

    /**
     * 返回头部title description keywords信息
     */
    public function generateHeads($project){
        $heads['title'] = str_replace('[project]',$project , Lang::get('project_title'));
        $heads['description'] = str_replace('[project]',$project , Lang::get('project_description'));
        $heads['keywords'] = str_replace('[project]',$project , Lang::get('project_keywords'));
        $heads['info_top_h1'] = str_replace('[project]',$project , Lang::get('project_info_top_h1'));
        $heads['info_top_h4'] = str_replace('[project]',$project , Lang::get('project_info_top_h4'));
        return $heads;
    }
}