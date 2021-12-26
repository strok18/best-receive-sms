<?php

namespace app\mys\validate;


use think\Validate;

class LoginValidate extends Validate
{
    protected $rule = [
        'name|用户名' => 'require|alphaNum|min:5|max:10',
        'password|密码' => 'require|chsDash|min:6|max:20'
    ];
}