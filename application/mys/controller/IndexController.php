<?php

namespace app\mys\controller;

use app\common\controller\RedisController;
use app\common\model\CountryModel;
use app\common\model\PhoneModel;
use app\index\validate\IndexValidate;
use think\facade\Lang;
use think\facade\Request;
use think\Controller;

class IndexController extends Controller
{
    public function index()
    {
        $country = 'ALL';
        //写入redis缓存
        $redis = new RedisController();
        if (empty($page)){
            $page = 1;
            $title_page = 1;
        }
        $sub_domain = get_subdomain();
        $redis_value = $redis->redisCheck($sub_domain . "_web_{$country}_" . $page);
        if($redis_value){
            $result = unserialize($redis_value);
        }else{
            $result = (new PhoneModel())->getCountryPhone();
            $redis->redisSetCache($sub_domain . "_web_{$country}_" . $page, serialize($result));
        }
        $page = $result->render();
        $result = $result->toArray();
        $count = count($result['data']);
        if ($count > 3){
            array_splice($result['data'], 3, 0, 'Adsense');
        }
        if ($count > 9){
            array_splice($result['data'], 11, 0, 'Adsense');
        }
        $this->assign('page', $page);
        $this->assign('data', $result['data']);
        $this->assign('index_heads', $this->generateHeads());
        return $this->fetch();
    }

    /**
     * 返回头部title description keywords信息
     */
    public function generateHeads(){
        $sub_domain = get_subdomain();
        if ($sub_domain == 'tw'){
            $message_country_title = $country_info['tw_title'];
        }elseif ($sub_domain == 'www' || $sub_domain == ''){
            $message_country_title = $country_info['title'];
        }else{
            $message_country_title = $country_info['en_title'];
        }
        $heads['title'] = Lang::get('index_title');
        $heads['description'] = Lang::get('index_description');
        $heads['keywords'] = Lang::get('index_keywords');
        $heads['guoqi'] = str_replace('[country]',$message_country_title, Lang::get('common_guoqi'));
        return $heads;
    }

    //心跳检测
    public function heartBeat(){
    	return 1;
    }
}
