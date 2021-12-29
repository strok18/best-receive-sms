<?php


namespace app\common\controller;

use app\mys\controller\CountryController;
use think\Controller;
use think\facade\Lang;

class BreadCrumbController extends Controller
{
    public $lang_title = 'title';

    public function initialize()
    {
        $this->lang_title = (new CountryController())->countryLangTitle();
    }

    public function PhonePage($country, $country_data){
        $bread_crumb[0]['title'] = Lang::get('website_name');
        $bread_crumb[0]['url'] = '/';
        if ($_SERVER['REQUEST_URI'] == '/') {
            $bread_crumb[1]['title'] = Lang::get('common_home_page');
            $bread_crumb[1]['url'] = '/';
        }else{
            $bread_crumb[1]['title'] = $country_data[$this->lang_title] . ucwords(Lang::get('common_number'));
            $bread_crumb[1]['url'] = '/' . $country . '-phone-number.html';
        }
        return $bread_crumb;
    }

    public function MessagePage($phone_info, $page){
        $bread_crumb[0]['title'] = Lang::get('website_name');
        $bread_crumb[0]['url'] = '/';
        $bread_crumb[1]['title'] = $phone_info['country'][$this->lang_title] . ucwords(Lang::get('common_number'));
        $bread_crumb[1]['url'] = '/' . strtolower($phone_info['country']['en_title']) . '-phone-number.html';
        if (empty($page)){
            $bread_crumb[2]['title'] = $phone_info['phone_num'];
            $bread_crumb[2]['url'] = '';
        }else{
            $bread_crumb[2]['title'] = $phone_info['phone_num'];
            $bread_crumb[2]['url'] = '/' . strtolower($phone_info['country']['en_title']) . '-phone-number/verification-code-'.$phone_info['phone_num'] . '.html';
            $bread_crumb[3]['title'] = $page;
            $bread_crumb[3]['url'] = '';
        }
        return $bread_crumb;
    }
    
    public function MessagePageMys($phone_info, $page){
        $bread_crumb[0]['title'] = Lang::get('website_name');
        $bread_crumb[0]['url'] = '/';
        $bread_crumb[1]['title'] = $phone_info['country'][$this->lang_title] . ucwords(Lang::get('common_number'));
        $bread_crumb[1]['url'] = '/receive-sms-online/country/' . strtolower($phone_info['country']['en_title']);
        /*if($phone_info['id'] > 1692){
            $uid = $phone_info['uid'];
        }else{
            $uid = $phone_info['phone_num'];
        }*/
        $uid = $phone_info['uid'];
        if (empty($page)){
            $bread_crumb[2]['title'] = $uid;
            $bread_crumb[2]['url'] = '';
        }else{
            $bread_crumb[2]['title'] = $uid;
            $bread_crumb[2]['url'] = '/receive-sms-online/' . strtolower($phone_info['country']['en_title']) . '-phone-number-'.$uid . '.html';
            $bread_crumb[3]['title'] = $page;
            $bread_crumb[3]['url'] = '';
        }
        return $bread_crumb;
    }

}