<?php

namespace app\best\controller;


use app\api\controller\ApiController;
use app\common\controller\ReCaptchaController;
use app\common\controller\RedisController;
use app\common\model\CollectionMsgModel;
use app\common\model\PhoneModel;
use think\facade\Cache;
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
        'RecaptchaClick' => ['only' => ['index', 'random', 'report']],
        'InitM' => ['only' => ['index']]
    ];
    
    public function index(){
        $phone_num = Request::param('phone_num');
        $page = Request::param('page');
        if ($page > 100){
            $page = 100;
        }
        $validate = Validate::checkRule($phone_num, 'must|alphaNum|max:16');
        if(!$validate){
            return $this->error('传递参数异常');
        }
        $phone_info = (new PhoneModel())->getPhoneDetailByUID($phone_num);
        if(!$phone_info){
            return $this->error('号码不存在');
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
            $this->assign('empty', '<div style="text-align: center;color: red;font-size: 50px;">Number online countdown：'.gap_times($phone_online_time, 'en').'</div>');
            (new RedisController())->hIncrby('phone_click', $phone_num);
            $message_data = '';
        }else{
            //获取页面数据
            $pageData = $this->pageData($phone_num, $phone_info, $page);
            $this->assign('empty', '<div style="text-align: center;color: red;font-size: 50px;">NO DATA</div>');
            
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
                        $message_data[$key]['smsDate'] = '1 months ago';
                    }
                }
            }

        }

        $message_heads = $this->generateHeads($phone_info['uid'], $phone_info['country']);
        //面包屑
        $bread_crumb = (new BreadCrumbController())->MessagePageMys($phone_info, $page);
        $this->assign('bread_crumb', $bread_crumb);
        $this->assign('message_heads',$message_heads);
        $this->assign('page', $pageData['page_list']);
        $this->assign('data', $message_data);
        $this->assign('alt_title', Request::subDomain());
        $this->assign('phone_info', $phone_info);
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
        $page_url = 'receive-sms-online/'.$en_title.'-phone-number-'.$phone_info['uid'];
        if (empty($page)){
            $result_sms = (new ApiController())->getSMS($phone_num);
            $result_sms = json_decode($result_sms->getContent(), true);

            if($result_sms['error_code'] != 0){
                return $this->error($result_sms['msg']);
            }
            $result_sms = $result_sms['data'];
            //Log::write($result_sms, 'notice');
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
                    $page_list = $queue_controller->generatePageUls($page_url, 0, $phone_hour);
                }else{
                    $page_list = $queue_controller->generatePageUls($page_url, 0, 100);
                }
            }else{
                $page_list = '';
            }

        }else{
            $message_data = (new CollectionMsgModel())->getMessagePage($phone_info['id'], $page);
            if ($phone_hour <= 0){
                $page_list = '';
            }elseif($phone_hour <= 100){
                $page_list = $queue_controller->generatePageUls($page_url, $page, $phone_hour);
            }else{
                $page_list = $queue_controller->generatePageUls($page_url, $page, 100);
            }
            $result_sms = $message_data->toArray();
        }

        //dump($result_sms);
        if (count($result_sms) > 2){
            array_splice($result_sms, 2, 0, 'Adsense');
        }
        if (count($result_sms) > 14){
            array_splice($result_sms, 10, 0, 'Adsense');
        }
        return ['page_list'=>$page_list, 'result_sms'=>$result_sms];
    }
    
    //号码上线时间
    public function phoneOnlineHour($time, $phone_num){
        //按时间来计算页数
        /*$phone_date = $time;
        $phone_date = strtotime($phone_date);
        $hour = round((time() - $phone_date)/3600);*/
        //按接收条数确定页数
        $redis = new RedisController('sync');
        $receive_count = $redis->hGet('phone_receive', $phone_num);
        //以前的号码没有记录count值，如果参数为空，代表为老号码，返回100即可。
        if ($receive_count){
            $page = round($receive_count / 20) - 4;
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
     * 获取缓存短信的page列表
     */
    public function getPage($phone_num, $id){
        $redis = new RedisController();
        $redis_message_page = $redis->redisCheck('mytempsms_message_page_' . $phone_num);
        if (!$redis_message_page){
            $list = (new CollectionMsgModel())->messagePageMytempsms($id, $phone_num);
            $page = $list->render();
            //写入redis
            $redis->setStringValue('mytempsms_message_page_' . $phone_num, $page);
        }else{
            $page = $redis_message_page;
        }
        return $page;
    }

    /**
     * 返回头部title description keywords信息
     */
    public function generateHeads($phone_num, $country, $title_page = 1){
        //生成title头部动态信息
        $country_title = (new CountryController())->countryLangTitle();
        $title = str_replace('[country]',$country[$country_title], Lang::get('message_title'));
        $title = str_replace('[number]','+' . $country['bh'] . ' ' . $phone_num, $title);
        //$title = str_replace('[page]', $title_page, $title);
        $heads['title'] = $title;
        $heads['description'] = str_replace('[country]',$country[$country_title], Lang::get('message_description'));
        $heads['keywords'] = str_replace('[country]',$country[$country_title], Lang::get('message_keywords'));
        $heads['info_top_h1'] = str_replace('[country]',$country[$country_title], Lang::get('message_info_top_h1'));
        $heads['info_top_h4'] = str_replace('[country]',$country[$country_title], Lang::get('message_info_top_h4'));
        return $heads;
    }

        /**
     * 获取message历史分页数据
     */
    public function getPageData($phone_num, $page){
        $phone_info = (new PhoneModel())->getPhoneNum($phone_num);
        $result = (new CollectionMsgModel())->getPageDataMytempsms($phone_info['id'], $phone_num);
        $page = $result->render();
        $result_data = $result->toArray();
        $result = array();
        for ($i = 0; $i < count($result_data['data']); $i++){
            $msg_data = unserialize($result_data['data'][$i]['content']);
            $result[$i]['smsDate'] = $msg_data['smsDate'];
            //$result[$i]['PhoNum'] = $msg_data['PhoNum']; //smsonline部分号码没有存入PhoNum值
            $result[$i]['smsNumber'] = $msg_data['smsNumber'];
            $result[$i]['smsContent'] = $msg_data['smsContent'];
        }
        $message_heads = $this->generateHeads($phone_num, $phone_info['country'], Request::param('page'));
        $this->assign('message_heads',$message_heads);
        $this->assign('page', $page);
        $this->assign('phone_info', $phone_info);
        $this->assign('data', $result);
        return $this->fetch('index');
    }
    
        //前台报告无法收到短信
    public function report(){
        $phone_num = input('post.phone_num');
        $validate = Validate::checkRule($phone_num, 'must|number|max:15|min:6');
        if(!$validate){
            return show('传递参数异常', '', 4000);
        }
        //把提交的号码保存进入redis. respore_1814266666
        $redis = new RedisController();
        $return = $redis->redisNumber('report_' . $phone_num, 172800);
        if (!$return){
            return show('提交反馈失败', '', 4000);
        }
        return show('反馈成功');
    }

    //前台随机获取一个号码显示
    public function random(){
        $phone_model = new PhoneModel();
        $phone_num_info = $phone_model->getRandom();
        $phone_num = $phone_num_info['uid'];
        $phone_country = strtolower($phone_num_info['country']['en_title']);
        if (!$phone_num){
            return show('Random Phone success', '', 4000);
        }
        return show('Random Phone fail', Request::domain() . '/receive-sms-online/'.$phone_country.'-phone-number-'.$phone_num.'.html');
    }

}