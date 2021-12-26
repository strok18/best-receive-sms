<?php

namespace app\mys\controller;


use app\common\controller\RedisController;
use app\common\model\ArticleModel;
use app\common\model\CountryModel;
use app\common\model\PhoneModel;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;
use app\common\controller\BreadCrumbController;
use think\Controller;

class PhoneController extends Controller
{
    protected $middleware = [
        'InitM' => ['only' => ['index']]
    ];
    public function index(){
        $country = Request::param('country');
        //如果是首页,或者多个国家排序,比如东南亚,欧洲号码,对
        $page = Request::param('page');
        $title_page = $page;
        $country_model = new CountryModel();
        if ($country == 'gat'){
            $country_data = $country_model->findCountry('Hong Kong Macao Taiwan');
        }elseif ($country == 'gw'){
            $country_data = $country_model->findCountry('Foreign');
        }else{
            if ($country == '' || $country == 'upcoming'){
                $country_data = null;
            }else{
                $country_data = $country_model->findCountry($country);
            }
        }
        //dump($country_data);

        //首页获取全部
        if (empty($country)){
            $country = 'all';
        }
        switch ($country)
        {
            case 'gat':
                $country_id = [7,8];
                break;
            case 'gw':
                $country_id = [2,3,4,5,6,9,10,11,12,13,14,15,16,17,18,19,20,21,22];
                break;
            case 'foreign':
                $country_id = [2,3,4,5,6,9,10,11,12,13,14,15,16,17,18,19,20,21,22];
                break;
            case 'all':
                $country_id = [];
                break;
            case 'upcoming':
                $country_id = 'upcoming';
                break;
            case 'index':
                $country_id = 'index';
                break;
            default:
                $country_id = [$country_data->id, $country_data->id];
        }
        //获取page
        //写入redis缓存
        $redis = new RedisController();
        if (empty($page)){
            $page = 1;
            $title_page = 1;
        }
        $sub_domain = get_subdomain();
        $domain = get_domain();
        $cacheKeyCountryPage = 'phonePage:' . $sub_domain . '.' . $domain ."_web_{$country}_" . $page;
        $redis_value = $redis->redisCheck(Config::get('cache.prefix') . $cacheKeyCountryPage);
        if($redis_value){
            $result = unserialize($redis_value);
        }else{
            $result = (new PhoneModel())->getCountryPhoneMys($country_id);
            Cache::tag('phonePage')->set($cacheKeyCountryPage, serialize($result), 1800);
        }
        $page = $result->render();
        $result = $result->toArray();
        $count = count($result['data']);
        if ($count > 4){
            array_splice($result['data'], 4, 0, 'Adsense');
        }

        //面包屑
        $bread_crumb = (new BreadCrumbController())->PhonePage($country, $country_data);
        $this->assign('bread_crumb', $bread_crumb);
        $this->assign('page', $page);
        $this->assign('data', $result['data']);
        
        //copy phone_num
        $js_data = [];
        $k = 0;
        for($i = 0; $i < count($result['data']); $i++){
            if (isset($result['data'][$i]['uid'])) {
                $js_data[$k]['phone_num'] = $result['data'][$i]['phone_num'];
                $js_data[$k]['uid'] = $result['data'][$i]['uid'];
            }else{
                $k--;
            }
            $k++;
        }
        $this->assign('js_data', $js_data);
        $this->assign('phone_heads', $this->generateHeads($country_data, $title_page));
        return $this->fetch();
    }

    /**
     * 返回头部title description keywords信息
     */
    public function generateHeads($country, $title_page){
        
        //如果是首页
        if ($_SERVER['REQUEST_URI'] == '/'){
            $heads['title'] = Lang::get('index_title');
            $heads['description'] = Lang::get('index_description');
            $heads['keywords'] = Lang::get('index_keywords');
            $heads['info_top_h1'] = Lang::get('index_info_top_h1');
            $heads['info_top_h4'] = Lang::get('index_info_top_h4');
        }else{
            $country_title = (new CountryController())->countryLangTitle();
            $title = str_replace('[country]',$country[$country_title], Lang::get('country_page_title'));
            $title = str_replace('[page]', $title_page, $title);
            $heads['title'] = '+' . $country['bh'] . ' ' . $title;
            $heads['description'] = str_replace('[country]',$country[$country_title], Lang::get('country_page_description'));
            $heads['keywords'] = str_replace('[country]',$country[$country_title], Lang::get('country_page_keywords'));
            $heads['info_top_h1'] = str_replace('[country]',$country[$country_title], Lang::get('country_page_info_top_h1'));
            $heads['info_top_h4'] = str_replace('[country]',$country[$country_title], Lang::get('country_page_info_top_h4'));
        }
        //$heads['guoqi'] = str_replace('[country]',$country[$country_title . '_title'], Lang::get('common_guoqi'));
        return $heads;
    }
    
    //获取单个phone关联信息，如果有缓存就从缓存读取没有
    public function getPhoneDetail($phone_num){
        $redis = new RedisController();
        $redis_value = $redis->redisCheck('phone_detail_' . $phone_num);
        if($redis_value){
            $result = unserialize($redis_value);
        }else{
            $phone_model = new PhoneModel();
            /*if(strlen($phone_num) == 10){
                $result = $phone_model->getUidDetail($phone_num);
                if(!$result){
                    $result = $phone_model->getPhoneNum($phone_num);
                }
            }else{
                $result = $phone_model->getPhoneNum($phone_num);
                if(!$result){
                    $result = $phone_model->getUidDetail($phone_num);
                }
            }*/
            $result = $phone_model->getUidDetail($phone_num);
            
            if ($result){
                $redis->redisSetCache('phone_detail_' . $phone_num, serialize($result));
            }
        }
        return $result;
    }
}