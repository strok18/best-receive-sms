<?php

namespace app\best\controller;

use app\common\controller\RedisController;
use app\common\model\CountryModel;
use think\facade\Lang;
use think\facade\Request;
use think\Controller;

class CountryController extends Controller
{
    protected $middleware = [
        'InitM' => ['only' => ['index']]
    ];
    
    public function index(){
        $country_data = (new CountryModel())->getAllCountry();
        $page = $country_data->render();
        $country_title = $this->countryLangTitle();
        for ($i = 0; $i < count($country_data); $i++){
            $country_data[$i]['country_image_title'] = str_replace('[country]',$country_data[$i][$country_title], Lang::get('country_main_image_title'));
            $country_data[$i]['country_title'] = $country_data[$i][$country_title] . ' ' . Lang::get('common_number');
        }
        $this->assign('page', $page);
        $this->assign('data', $country_data);
        $this->assign('country_heads', $this->generateHeads());
        return $this->fetch();
    }

    /**
     * 返回头部title description keywords信息
     */
    public function generateHeads(){
        $heads['title'] = Lang::get('country_title');
        $heads['description'] = Lang::get('country_description');
        $heads['keywords'] = Lang::get('country_keywords');
        return $heads;
    }

    /**
     * 获取当前国家的各语言名称
     * @return string
     */
    public function countryLangTitle(){
        
        $sub_domain = get_subdomain();
        $domain = get_domain();
        if ($sub_domain == 'www' || $sub_domain == '106.55' || $sub_domain == 'num'){
                $country_title = 'en_title';
            }else{
                $country_title = $sub_domain . '_title';
            }
        return $country_title;
    }

}