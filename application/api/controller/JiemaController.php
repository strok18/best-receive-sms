<?php


namespace app\api\controller;


use app\common\controller\RedisController;
use app\common\model\JiemaOrderModel;
use app\common\model\JiemaProjectModel;
use app\common\model\UserModel;
use app\mytempsms\controller\SessionLoginController;
use think\Db;
use think\Exception;
use think\facade\Log;
use think\facade\Request;
use think\facade\Session;

class JiemaController extends SessionLoginController
{
    /**
     * 根据项目获取号码
     * 获取前需要查询余额是否足够
     */
    public function getNumber(){
        //如果获取到号码，redis记录一次
        $pid = input('post.project_id');
        $redis = new RedisController();
        //查询项目单价，并检查余额是否足够
        //$price = (new JiemaProjectModel())->getFieldValue($pid, 'price');
        $project_info = (new JiemaProjectModel())->getInfo($pid);
        if ($project_info){
            if (!$this->checkMoney($project_info['price'])){
                return show('余额不足', '', 4000);
            }
        }else{
            return show('该项目不存在', '', 4000);
        }

        $url = 'http://dkh.hfsxf.com:81/service.asmx/GetHM2Str?token=64A01CE37DF94F7757D87CDAEB7F59E7&xmid='.$pid.'&sl=1&lx='.$project_info['ka_type'].'&a1=&a2=&pk=&ks=0&rj=deepblue';
        $result = curl_get($url);
        if (strlen($result) > 7){
            $value = explode('=', $result);
            if (count($value) == 2){
                $redis_num = $redis->redisNumber('jiema_number_' . Session::get('user_login'), 600);
                if ($redis_num > 1){
                    return show('还有未释放的号码，请点击释放号码后重新发起', '', 4000);
                }
                $data['number'] = $value[1];
                $data['project_id'] = $pid;
                return show('获取号码成功', $data);
            }else{
                return show('获取号码失败，请重试', '', 4000);
            }
        }else{
            return show('获取号码失败，请重试', '', 4000);
        }
    }

    //根据号码获取短信
    public function getMessage(){
        $data = input('post.')['data'];
        $number = $data['number'];
        $xmid = $data['project_id'];
        $url = 'http://dkh.hfsxf.com:81/service.asmx/GetYzm2Str?token=64A01CE37DF94F7757D87CDAEB7F59E7&hm='.$number.'&xmid='.$xmid.'&sf=0';
        $result = curl_get($url);
        if (strlen($result) > 4){
            if (stristr($result, '输入字符串的格式不正确')){
                $this->_putNumber($number);
                return show('信息提交错误', '信息输入错误，号码已经释放，请重新获取新号码', 3000);
            }
            $this->_putNumber($number);
            preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}收到 ([\s\S]*)/', $result, $message);
            $message = $message[1];
            //扣款处理
            $price = (new JiemaProjectModel())->getFieldValue($xmid, 'price');
            $this->money($xmid, -$price, $number, $message);
            return show('获取短信成功', $message);
        }
    }

    //释放号码
    public function putNumber($number = 0){
        $number = input('post.number');
        $url = 'http://dkh.hfsxf.com:81/service.asmx/sfHmStr?token=64A01CE37DF94F7757D87CDAEB7F59E7&hm=' . $number;
        $result = curl_get($url);
        if ($result == 1){
            (new RedisController())->deleteString('jiema_number_' . Session::get('user_login'));
            return show('号码释放成功');
        }else{
            return show('号码释放失败', $result, 4000);
        }
    }

    //释放号码
    public function _putNumber($number){
        $url = 'http://dkh.hfsxf.com:81/service.asmx/sfHmStr?token=64A01CE37DF94F7757D87CDAEB7F59E7&hm=' . $number;
        $result = curl_get($url);
        if ($result == 1){
            (new RedisController())->deleteString('jiema_number_' . Session::get('user_login'));
            return show('号码释放成功');
        }else{
            return show('号码释放失败', $result, 4000);
        }
    }

    //释放所有号码
    public function putAllNumber($number = 0){
        $url = 'http://dkh.hfsxf.com:81/service.asmx/sfAllStr?token=64A01CE37DF94F7757D87CDAEB7F59E7';
        $result = curl_get($url);
        if ($result == 1){
            (new RedisController())->deleteString('jiema_number_' . Session::get('user_login'));
            return show('所有号码释放成功');
        }else{
            return show('所有号码释放失败', $result, 4000);
        }
    }

    //拉黑号码
    public function blacklist(){
        $number = input('post.number');
        $xmid = input('post.project');
        $url = 'http://dkh.hfsxf.com:81/service.asmx/Hmd2Str?token=64A01CE37DF94F7757D87CDAEB7F59E7&xmid='.$xmid.'&hm='.$number.'&sf=1';
        $result = curl_get($url);
        if ($result == 1){
            (new RedisController())->deleteString('jiema_number_' . Session::get('user_login'));
            return show('拉黑成功');
        }else{
            return show('拉黑失败', $result, 4000);
        }
    }

    //拉黑号码
    public function _blacklist($xmid, $number){
        $url = 'http://dkh.hfsxf.com:81/service.asmx/Hmd2Str?token=64A01CE37DF94F7757D87CDAEB7F59E7&xmid='.$xmid.'&hm='.$number.'&sf=1';
        $result = curl_get($url);
        if ($result == 1){
            (new RedisController())->deleteString('jiema_number_' . Session::get('user_login'));
            return show('拉黑成功');
        }else{
            return show('拉黑失败', $result, 4000);
        }
    }

    //发送短信
    public function sendMessage(){
        $data = input('post.')['data'];
        $url = 'http://dkh.hfsxf.com:81/service.asmx/SendSms3Str?token=64A01CE37DF94F7757D87CDAEB7F59E7&xmid='.$data['project'].'&hm='.$data['number'].'&nr='.$data['nr'].'&pk=&rj=deepblue&rhm='.$data['com'];
        $result = curl_get($url);
        if ($result == 1){
            return show('短信已经发送，请等待成功回执后操作');
        }else{
            return show('发送短信失败，请换号重新发送', '', 4000);
        }
    }

    //发送查询反馈是否成功
    public function sendState(){
        $data = input('post.');
        $this->_blacklist($data['project'], $data['number']);
        $url = 'http://dkh.hfsxf.com:81/service.asmx/GetFsState?token=64A01CE37DF94F7757D87CDAEB7F59E7&xmid='.$data['project'].'&hm='.$data['number'];
        $result = curl_get($url);
        if ($result == '发送成功'){
            $this->blacklist($data['project'], $data['number']);
            //扣款处理
            $price = (new JiemaProjectModel())->getFieldValue($data['project'], 'price');
            $this->money($data['project'], -$price, $data['number'], $data['nr']);
            return show('短信发送成功', '短信发送成功');
        }elseif($result == '发送失败'){
            return show('短信已经发送失败，平台退回费用', '短信已经发送失败，平台退回费用');
        }elseif($result == 1){
            return show('正在发送中','正在发送中...', 4000);
        }
    }

    //帐户余额增加扣除
    //money，+ -
    protected function money($pid, $price, $number, $message){
        $user = Session::get('user_login');
        $data = [
            'order' => $this->generateOrderNumber(),
            'number' => $number,
            'message' => $message,
            'project_id' => $pid,
            'money' => $price,
            'before_money' => (new UserModel())->getFieldValue($user, 'money'),
            'user' => $user,
            'create_time' => time(),
            'update_time' => time()
        ];
        Db::transaction(function () use ($data){
            $data['money'] > 0 ? $set = 'inc' : $set = 'dec';
            //1.扣余额，2.写入流水
            Db::table('user')->where('name', $data['user'])->$set('money', abs($data['money']))->inc('total_receive')->update();
            Db::table('jiema_order')->insert($data);
        });
    }

    //检查帐户是否够余额
    protected function checkMoney($price){
        $user = Session::get('user_login');
        $user_model = new UserModel();
        $user_money = $user_model->getFieldValue($user, 'money');
        if ($user_money >= $price){
            return true;
        }else{
            return false;
        }
    }

    //生成订单号
    protected function generateOrderNumber(){
        $order = date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return $order;
    }

    //订单流水
    public function jiemaOrder()
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $user = Session::get('user_login');
        $jiema_order_model = new JiemaOrderModel();
        $data = $jiema_order_model->getPageList($user, $page, $limit);
        $new_data = [];
        for ($i = 0; $i < count($data); $i++){
            $new_data[$i]['type'] = $data[$i]['jiemaproject']['type'];
            $new_data[$i]['number'] = $data[$i]['number'];
            $new_data[$i]['order'] = $data[$i]['order'];
            $new_data[$i]['message'] = $data[$i]['message'];
            $new_data[$i]['before_money'] = $data[$i]['before_money'];
            $new_data[$i]['title'] = $data[$i]['jiemaproject']['title'];
            $new_data[$i]['money'] = $data[$i]['money'];
            $new_data[$i]['create_time'] = $data[$i]['create_time'];
        }
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => (new UserModel())->getFieldValue($user, 'total_receive'),
            'data' => $new_data,
        ];
        return json($result);
    }

    //获取当前帐户所属项目user=0 or user=self
    public function getProject(){
        $user = Session::get('user_login');
        $result = (new JiemaProjectModel())->getUserProject($user);
        if ($result){
            return show('success', $result);
        }
    }

    //根据pid获取发送接口的发送端口
    public function getProjectPort(){
        $pid = input('post.pid');
        $result = (new JiemaProjectModel())->getFieldValue($pid, 'port');
        if ($result){
            return show('success', $result);
        }
    }
}