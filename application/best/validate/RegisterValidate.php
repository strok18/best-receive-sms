<?php
namespace app\mys\validate;


use think\Validate;

class RegisterValidate extends Validate
{
    protected $rule = [
        'name|用户名' => 'require|alphaNum|min:5|max:10',
        'password|密码' => 'require|chsDash|min:6|max:20',
        'password2|重复密码' => 'chsDash|min:6|max:20|confirm:password'
    ];
}