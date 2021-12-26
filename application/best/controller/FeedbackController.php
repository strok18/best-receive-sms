<?php


namespace app\best\controller;


use app\common\model\FeedbackModel;
use think\facade\Lang;
use think\facade\Validate;
use think\Controller;

class FeedbackController extends Controller
{
    protected $middleware = ['Click'];
    
    public function create(){
        $content = trim(input('post.content'));
        $email = trim(input('post.email'));
        $type = trim(input('post.type'));
        $validate = Validate::make(['content' => 'require|max:200', 'email' => 'require|email|max:30', 'type' => 'must|integer|max:1']);
        $data['content'] = $content;
        $data['email'] = $email;
        $data['type'] = $type;
        if (!$validate->check($data)){
            return show($validate->getError(), $content, 4000);
        }
        $data['ip'] = real_ip();
        $data['browser'] = $_SERVER['HTTP_USER_AGENT'];
        $data['page'] = get_subdomain();
        if (is_mobile()){
            $data['is_mobile'] = 'mobile';
        }else{
            $data['is_mobile'] = 'web';
        }
        $feedback_model = new FeedbackModel();
        $feedback_count = $feedback_model->searchTodayIP($data['ip']);
        if ($feedback_count > 5){
            return show(Lang::get('feedback_failed'), $content, 4000);
        }
        $result = $feedback_model->insertFeedback($data);
        if ($result > 0){
            return show(Lang::get('feedback_success'));
        }else{
            return show(Lang::get('feedback_failed'), $content, 4000);
        }
    }
}