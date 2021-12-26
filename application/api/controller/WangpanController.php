<?php


namespace app\api\controller;


use app\common\model\WangpanPasswordModel;
use think\Controller;
use think\facade\Lang;
use think\facade\Validate;
use app\common\controller\RedisController;
use app\index\controller\BaseController;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WangpanController extends BaseController
{
    protected $middleware = ['Click'];
    /**
         https://pan.baidu.com/s/1qYLrSOs https://pan.baidu.com/s/pid
        URL: “http://ypsuperkey.meek.com.cn/api/items/BDY-"+pid
        Type: GET
        pid: 看上面
        access_key: 默认值4fxNbkKKJX2pAm3b8AEu2zT5d2MbqGbD
        client_version: 默认值web-client
        1553997935000: 为空，猜测这串数字(即参数名)为时间戳
     * 备用
     * https://node.pnote.net/public/pan?url=https://pan.baidu.com/share/init?surl=jwaDG3BRVgA7f8w8sJNQnQ
     */
    public function getPassword(){
        return show('该项目暂停维护...', '', 4000);
    	$url = trim(input('post.url'));
        //检查url
        $urls = [
            'https://www.lanzous.com/',
            'https://lanzous.com/',
            'lanzous.com/',
            'https://pan.baidu.com/',
            'https://share.weiyun.com/',
        ];
        //判断url是否是列表的地址
        $check_url = 0;
        foreach ($urls as $value){
            if (strpos($url, $value) !== false){
                $check_url++;
                break;
            }
        }
        if ($check_url == 0){
            return show('仅支持百度、蓝奏云、微云网盘!', $url, 4000);
        }
        $validate = Validate::checkRule($url, 'must|max:200|min:5');
        if (!$validate){
            return show('网盘URL地址提供错误,请重新提交!', $url, 4000);
        }
        $redis = new RedisController();
        $wangpan = $this->checkWangpan($url);
        $api_url = 'https://api.newday.me/share/disk/query';
        $client = new Client();
        $param = [
            "version" => "0.3.7",
            "uid" => "r8r7tq9h5982eaoogjixxc3meeln7exi",
            "share_point" => input('post.point'),
            "share_link" => $url,
            "share_id" => $wangpan['pid'],
            "mode" => "script",
            "browser" => "chrome",
            "aid" => "tampermonkey"
        ];
        try {
            $result = $data = $client->request('POST', $api_url, [
                'form_params' => $param,
                //'timeout' => 20
                ])->getBody();
        } catch (RequestException $e ) {
            return show('抱歉,访问超时', '', 4000);
        }
        
        $result = json_decode($result, true);
        $value = [];
        if ($result['code'] == 1){
            $redis->redisNumberNoTime('wangpan_password_success');
            $value['url'] = $url;
            $value['password'] = $result['data']['share_pwd'];
            $this->searchUrl($url, $value);
            return show('获取密码成功', $result['data']['share_pwd']);
        }else{
            $redis->redisNumberNoTime('wangpan_password_failed');
            return show('抱歉,没有找到您需要的密码', '', 4000);
        }
/*        //$api_url = "http://ypsuperkey.meek.com.cn/api/items/" . $wangpan['type'] . "-" . $wangpan['pid'] . "?access_key=4fxNbkKKJX2pAm3b8AEu2zT5d2MbqGbD&client_version=web-client&" . time() . '000';
        $result = curl_get($api_url);
        if (strpos($result, '\u6761\u76ee\u4e0d\u5b58\u5728') !== false){
            return show('抱歉,没有找到您需要的密码', '', 2000);
        }
        $result = json_decode(curl_get($api_url), true);
        //dump($result);
        $value = [];
        if ($result['state'] == 'VALID'){
        	(new RedisController())->redisNumberNoTime('wangpan_password_success');
            $value['url'] = $url;
            if (!array_key_exists('access_code', $result)){
                //没有密码
                $this->searchUrl($url, $value);
                return show('当前网盘并没有设置密码或解析失败', '', 2000);
            }else{
                //有密码 返回信息
                $value['password'] = $result['access_code'];
                $this->searchUrl($url, $value);
                return show('获取密码成功', $result['access_code']);
            }
        }else{
        	(new RedisController())->redisNumberNoTime('wangpan_password_failed');
            return show('获取密码失败', '', 4000);
        }*/
    }
    
        //解析地址为哪个云盘
    public function checkWangpan($url){
        //现在api仅能提供蓝奏云和百度链接
        //https://www.lanzous.com/b07xe5xpi
        //https://pan.baidu.com/s/1tZiWcqgco2caucH0pCcRZA
        //https://pan.baidu.com/s/1c0tgyC4
        //https://share.weiyun.com/5nYsfso
        //https://wuaipojie.lanzous.com/ic32ddi
        //先判断是百度还是蓝奏
        $wangpan_arr = [
            ['https://www.lanzous.com/', 'lanzous'],
            ['https://lanzous.com/', 'lanzous'],
            ['lanzous.com/', 'lanzous'],
            ['https://pan.baidu.com/', 'baidu'],
            ['https://share.weiyun.com/', 'weiyun'],
        ];
        $wangpan = [];
        //判断类型
        for ($i = 0; $i < count($wangpan_arr); $i++){
            if (strpos($url, $wangpan_arr[$i][0]) !== false){
                $wangpan['type'] = $wangpan_arr[$i][1];
                break;
            }
        }
        //获取pid
        $url_arr = explode('/', $url);
        if ($url_arr[3] == 's'){
            $pid = $url_arr[4];
        }elseif ($url_arr[3] == 'share') {
            $pid = substr(strstr($url_arr[4], '='), 1);
        }else{
            $pid = $url_arr[3];
        }
        //百度特殊处理,Pid前面如果是1的话 需要删除
        if ($wangpan['type'] == 'baidu'){
            if (substr($pid, 0, 1) == 1){
                $pid = substr($pid, 1);
            }
        }
        $wangpan['pid'] = $pid;
        return $wangpan;
    }

    //解析地址为哪个云盘
    public function checkWangpanOld($url){
        //现在api仅能提供蓝奏云和百度链接
        //https://www.lanzous.com/b07xe5xpi
        //https://pan.baidu.com/s/1tZiWcqgco2caucH0pCcRZA
        //https://pan.baidu.com/s/1c0tgyC4
        //先判断是百度还是蓝奏
        $wangpan_arr = [
            ['https://www.lanzous.com/', 'LZY'],
            ['https://pan.baidu.com/', 'BDY'],
        ];
        $wangpan = [];
        //判断类型
        for ($i = 0; $i < count($wangpan_arr); $i++){
            if (strpos($url, $wangpan_arr[$i][0]) !== false){
                $wangpan['type'] = $wangpan_arr[$i][1];
                break;
            }
        }
        //获取pid
        $url_arr = explode('/', $url);
        if ($url_arr[3] == 's'){
            $pid = $url_arr[4];
        }elseif ($url_arr[3] == 'share') {
            $pid = substr(strstr($url_arr[4], '='), 1);
        }else{
            $pid = $url_arr[3];
        }
        //百度特殊处理,Pid前面如果是1的话 需要删除
        if ($wangpan['type'] == 'BDY'){
            if (substr($pid, 0, 1) == 1){
                $pid = substr($pid, 1);
            }
        }
        $wangpan['pid'] = $pid;
        return $wangpan;
    }
    
    //url是否存在数据库
    protected function searchUrl($url, $value){
        $wangpan_model = new WangpanPasswordModel();
        $result = $wangpan_model->searchUrl($url);
        if (!$result){
            $wangpan_model->add($value);
        }
    }
}