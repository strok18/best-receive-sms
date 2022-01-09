<?php


namespace app\best\controller;


use app\common\model\MailboxModel;
use think\Controller;
use think\facade\Lang;
use think\facade\Validate;
use think\facade\Request;
use bt\BtOtherEmailServer;

class SubscriptionController extends Controller
{
    protected $middleware = [
        'InitM' => ['only' => ['index']],
        //'RecaptchaClick' => ['only' => ['mailbox', 'unsubscribe']],
    ];
    
    //取消订阅
    public function unsubscribePage(){
        return $this->fetch('unsubscribe');
    }
    
    public function sendTestmail(){
        $mailbox = trim(input('post.mailbox'));
        $validate = Validate::checkRule($mailbox, 'must|email|max:30|min:5');
        if (!$validate){
            return show(Lang::get('subscribe_params_error'), $mailbox, 4000);
        }
        //查询是否已经订阅
        $mailbox_model = new MailboxModel();
        $result = $mailbox_model->search($mailbox);
        if(!$result){
            return show('Please click to subscribe first', $mailbox, 4000);
        }
        
        $bt = new BtOtherEmailServer();
        $content = "The subscription is successful, the number will be updated every week, and you will be notified by email as soon as possible. <br>If the mail is in the trash can, please move it to the inbox.";
        $result = $bt->sendEmail('notify@mytempsms.com', $mailbox, 'Send Test', $content);
        if($result['status']){
            return show('The test email is sent successfully <br>please enter the mailbox to check (trash box)<br>please remove the email from the trash box<br>or add 【nitify@mytempsms.com】 as a friend or whitelist.');
        }else{
            return show('Failed to send test mail.', $mailbox, 4000);
        }
    }
    
    public function mailbox($mailbox){
        $mailbox = trim(input('post.mailbox'));
        $validate = Validate::checkRule($mailbox, 'must|email|max:30|min:5');
        if (!$validate){
            return show(Lang::get('subscribe_params_error'), $mailbox, 4000);
        }
        $dns = explode('@', $mailbox);
            $mail_afters = [
                'qq',
                'gmail',
                '163',
                'yahoo',
                'outlook',
                'hotmail',
                'icloud',
                'live',
                'msn',
                'sina',
                '126',
                '189',
                'yandx',
                'aliyun',
                'protonmail'
        ];
        $exist = strIsExist($dns[1], $mail_afters);
        if (!$exist){
            //更换常用邮件订阅
            return show('Please change the frequently used email，gmail / live / outlook / hotmail / 163', '', 4000);
        }
        $mailbox_model = new MailboxModel();
        if ($mailbox_model->search($mailbox) > 0){
            return show($mailbox . Lang::get('subscribe_has_been'), $mailbox, 4000);
        }
        $data['mailbox'] = $mailbox;
        $data['ip'] = real_ip();
        $data['befrom'] = get_subdomain();
        $result = $mailbox_model->insertMailbox($data);
        if ($result > 0){
            return show(Lang::get('subscribe_success'));
        }else{
            return show(Lang::get('subscribe_failed'), $mailbox, 4000);
        }
    }
    
    //取消订阅
    public function unsubscribe(){
        $mailbox = trim(input('post.mailbox'));
        $validate = Validate::checkRule($mailbox, 'must|email|max:30|min:5');
        if (!$validate){
            return show(Lang::get('subscribe_params_error'), $mailbox, 4000);
        }
        
        //查询数据库是否存在，如果存在，send标记为0
        $mailbox_model = new MailboxModel();
        $result = $mailbox_model->search($mailbox);
        if(!$result){
            return show("【{$mailbox}】This Email is not subscribed！", $mailbox, 4000);
        }
        //标记取消订阅
        $result = $mailbox_model->save(['send' => 0], ['mailbox' => $mailbox]);
        if($result){
            return show('Email unsubscribe successfully');
        }else{
            return show('Email unsubscribe failed', $mailbox, 4000);
        }
    }
}