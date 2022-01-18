<?php

namespace app\best\controller;


use app\api\controller\ApiController;
use app\common\controller\ReCaptchaController;
use app\common\controller\RedisController;
use app\common\model\CollectionMsgModel;
use app\common\model\CountryModel;
use app\common\model\PhoneModel;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;
use think\facade\Validate;
use app\common\controller\BreadCrumbController;
use app\common\controller\QueueController;
use think\Controller;
use think\facade\Log;

class MessageController extends Controller
{
    protected $middleware = [
        'RecaptchaClick' => ['only' => ['index', 'random', 'report']]
    ];
    
    public function index(){
        $phone_num = Request::param('phone_num');
        $page = Request::param('page');
        if ($page > 100){
            $page = 100;
        }
        $validate = Validate::checkRule($phone_num, 'must|alphaNum|max:16');
        if(!$validate){
            return $this->error(Lang::get('common_fail'));
        }
        $phone_info = (new PhoneModel())->getPhoneDetailByUID($phone_num);
        if(!$phone_info){
            return $this->error(Lang::get('api_no_number'));
        }
        //临时301跳转
/*        if($phone_num == $phone_info['phone_num']){
            $url = Request::url(true);
            $r_url = preg_replace('/(\d{7,13})/', $phone_info['uid'], $url);
            $this->redirect($r_url,301);
        }*/
        
        //兼容uid和id
        $phone_num = $phone_info['uid'];

        if ($phone_info['type'] == 2){
            $redis = new RedisController('sync');
            $phone_online_time = $redis->redisCheck('phone_online_time');
            //上新，仅上架，不展示信息，预售号码
            $pageData = ['page_list' => '', 'result_sms' => ''];
            $this->assign('empty', '<div><img src="/static/web/images/time-down.svg" class="img-fluid"><div class="text-center text-secondary fs-2 fw-bold">Number online countdown<br><span class="fs-1 text-danger">'.gap_times($phone_online_time, 'en').'</span></div></div>');
            (new RedisController())->hIncrby('phone_click', $phone_num);
            $message_data = '';
        }else{
            //获取页面数据
            $pageData = $this->pageData($phone_num, $phone_info, $page);

            $message_data = $pageData['result_sms'];
            foreach($pageData['result_sms'] as $key => $value){
                if(is_array($pageData['result_sms'][$key])){
                    if (empty($page)){
                        $default_date = time() - 2592000;
                        if(is_numeric($value['smsDate'])){
                            //正确日期
                            if((time() - $value['smsDate']) > 2592000){
                                $sms_date = $default_date;
                            }else{
                                $sms_date = $value['smsDate'];
                            }
                        }else{
                            //非日期格式
                            $sms_date = $default_date;
                        }
                        $message_data[$key]['smsDate'] = gap_times($sms_date, 'en', 'ago');
                    }else{
                        $message_data[$key]['smsDate'] = 'expired';
                    }
                }
            }
            $this->assign('empty', '<div><img src="/static/web/images/empty-mail.svg"><p class="fw-bold">'.Lang::get('common_no_message_data').'</p></div>');
        }

        $message_heads = $this->generateHeads($phone_info['uid'], $phone_info['country']);
        //面包屑
        $bread_crumb = (new BreadCrumbController())->MessagePageMys($phone_info, $page);
        $this->assign('bread_crumb', $bread_crumb);
        $this->assign('message_heads',$message_heads);
        $this->assign('page', $pageData['page_list']);
        $current_lang = (new CountryController())->countryLangTitle();
        $this->assign('country_list',(new CountryModel())->getAllCountryName($current_lang, 10));
        $this->assign('recommend', (new ProjectController())->getRecommend());
        $this->assign('data', $message_data);
        $this->assign('alt_title', Request::subDomain());
        $phone_info['phone_encryption'] = phoneEncryption((string)$phone_info['phone_num']);
        $phone_info['bh_encryption'] = phoneEncryption((string)$phone_info['country']['bh']);
        $this->assign('phone_info', $phone_info);
        $this->assign('phone_data', (new PhoneModel())->getCountryPhone('hot'));
        $this->assign('upcomingNumber', (new PhoneModel())->getUpcomingNumber());
        $this->assign('mClick', (new RedisController())->hMget('ip:click:' . real_ip(), ['message_click', 'score']));
        return $this->fetch();
    }

    /**
     * 获取短信页数据
     * @param $phone_num
     * @param $phone_info
     * @return array|void page_list result_sms
     */
    private function pageData($phone_num, $phone_info, $page = null){
        //如果是历史页面处理
        $queue_controller = new QueueController();
        $en_title = strtolower($phone_info['country']['en_title']);
        //号码上线天数，以便设置页数
        $phone_hour = $this->phoneOnlineHour($phone_info['create_time'], $phone_num);
        $page_url = 'receive-sms-from-'.$en_title.'/' . $phone_info['uid'];
        if (empty($page)){
            $this->spiderQueue($phone_info);
            $result_sms = $this->getMsg($phone_num);
            
            /*//验证码生成图片
            $result_sms_count = count($result_sms);
            if ($result_sms_count > 0){
                for ($i = 0; $i < $result_sms_count; $i++){
                    $smsContent = preg_replace_callback("|(\d{4,6})|", "codePregReplaceCallbackFunction", $result_sms[$i]['smsContent'], 1);
                    $result_sms[$i]['smsContent'] = $smsContent;
                }
            }*/
            
            //判断是否需要显示分页
            if ($phone_info['warehouse']['message_save'] == 1){
                //trace($phone_hour,'notice');
                if ($phone_hour <= 0){
                    $page_list = '';
                }elseif($phone_hour <= 100){
                    $page_list = $queue_controller->generateSMSHistoryPage($page_url, 0, $phone_hour);
                }else{
                    $page_list = $queue_controller->generateSMSHistoryPage($page_url, 0, 100);
                }
            }else{
                $page_list = '';
            }

        }else{
            $message_data = (new CollectionMsgModel())->getHistorySms($phone_info['id'], $phone_num, $this->getPhoneSmsTotal($phone_num));
            //dump($message_data);
            $page_list = $message_data->render();
            $result_sms = $message_data->toArray()['data'];
        }

        //广告设置
        /*if (count($result_sms) > 2){
            array_splice($result_sms, 2, 0, 'Adsense');
        }
        if (count($result_sms) > 14){
            array_splice($result_sms, 10, 0, 'Adsense');
        }*/
        return ['page_list'=>$page_list, 'result_sms'=>$result_sms];
    }

    //获取号码总数
    public function getPhoneSmsTotal($phone_num){
        $redis = new RedisController('sync');
        $receive_count = $redis->hGet(Config::get('cache.prefix') . 'phone_receive', $phone_num);
        if (!$receive_count){
            $receive_count = 0;
        }
        if ($receive_count > 2000){
            $receive_count = 2000;
        }
        return $receive_count;
    }
    
    //号码上线时间
    public function phoneOnlineHour($time, $phone_num){
        //按时间来计算页数
        /*$phone_date = $time;
        $phone_date = strtotime($phone_date);
        $hour = round((time() - $phone_date)/3600);*/
        //按接收条数确定页数
        $redis = new RedisController('sync');
        $receive_count = $redis->hGet(Config::get('cache.prefix') . 'phone_receive', $phone_num);
        //以前的号码没有记录count值，如果参数为空，代表为老号码，返回100即可。
        if ($receive_count){
            $p = $receive_count / 20;
            if($p < 1){
                $page = 0;
            }else{
                $page = round($p);
            }
        }else{
            $page = 0;
        }
        return $page;
    }
    
    //序列化处理
    function mb_unserialize($str) {
        return preg_replace_callback('#s:(\d+):"(.*?)";#s',function($match){return 's:'.strlen($match[2]).':"'.$match[2].'";';},$str);
    }


    /**
     * 返回头部title description keywords信息
     */
    public function generateHeads($phone_num, $country, $title_page = 1){
        //生成title头部动态信息
        $country_title = (new CountryController())->countryLangTitle();
        $title = str_replace('[country]',$country[$country_title], Lang::get('message_title'));
        $title = str_replace('[number]','+' . $country['bh'], $title);
        //$title = str_replace('[page]', $title_page, $title);
        $heads['title'] = $title;
        $description = str_replace('[country]',$country[$country_title], Lang::get('message_description'));
        $heads['description'] = str_replace('[number]','+' . $country['bh'], $description);
        $heads['keywords'] = str_replace('[country]',$country[$country_title], Lang::get('message_keywords'));
        $heads['info_top_h1'] = str_replace('[country]',$country[$country_title], Lang::get('message_info_top_h1'));
        $heads['info_top_h4'] = str_replace('[country]',$country[$country_title], Lang::get('message_info_top_h4'));
        return $heads;
    }
    
    //前台报告无法收到短信
    public function report(){
        $phone_num = input('post.phone_num');
        $validate = Validate::checkRule($phone_num, 'must|number|max:15|min:6');
        if(!$validate){
            return show('传递参数异常', '', 4000);
        }
        $phone_info = (new PhoneModel())->getPhoneDetailByUID($phone_num);
        if(!$phone_info){
            return $this->error('号码不存在');
        }
        $phone_num = $phone_info['phone_num'];
        //把提交的号码保存进入redis. respore_1814266666
        $redis = new RedisController();
        $redis_key = config('cache.prefix') . 'report:' . $phone_num;
        $return = $redis->redisNumber($redis_key, 172800);
        if (!$return){
            return show(Lang::get('feedback_failed'), '', 4000);
        }
        return show(Lang::get('feedback_success'));
    }

    //前台随机获取一个号码显示
    public function random(){
        $phone_model = new PhoneModel();
        $phone_num_info = $phone_model->getRandom();
        $phone_num = $phone_num_info['uid'];
        if (!$phone_num){
            return show(Lang::get('message_api_random_fail'), '', 4000);
        }
        return show(Lang::get('common_success'), Request::domain() . '/receive-sms-from-'.$phone_num_info['country']['en_title'].'/' . $phone_num);
    }
    
    /**
     * 提交蜘蛛队列请求
    */
    protected function spiderQueue($phone_info){
        $key_phone_click = Config::get('cache.prefix') . 'click:' . $phone_info['uid'];
        //频率限制
        //每个号码10秒钟只能请求一次
        if ((new RedisController())->exists($key_phone_click)){
            return false;
        }
        //如果是爬虫，也不提交请求
        if ((new RedisController('sync'))->sIsMember('spider', real_ip())){
            return false;
        }
        //如果voice号，也不需要提交
        $warehouse = $phone_info['warehouse']['title'];
        if ($warehouse == 'ATLAS' || $warehouse == 'Voice'){
            return false;
        }
        
        $params = [
            'from' => 'best',
            'phone_num' => $phone_info['phone_num'],
            'phone_id' => $phone_info['phone_id'],
            'bh' => $phone_info['country']['bh'],
            'site' => $phone_info['warehouse']['title']
        ];
        
        $redis = new RedisController();
        $url = $redis->redisCheck(Config::get('cache.prefix') . 'curl_url');
        if ($url){
            $curl = asyncRequest($url, 'POST', $params);
            if ($curl){
                $redis->setex($key_phone_click, 15, 1);
            }
        }else{
            trace('远程请求地址不存在', 'notice');
        }
    }

    /**
     * redis读取号码缓存
     * @param $phone_num
     * @param int $num
     * @return array
     */
    protected function getMsg($phone_num, $num = 19){
        $redis = new RedisController('sync');
        $result = $redis->zRevRange(Config::get('cache.prefix') . 'message:' . $phone_num, 0, $num);
        $data = array();
        for ($i = 0; $i < count($result); $i++) {
            $data[$i] = unserialize($result[$i]);
        }
        return $data;
    }

}