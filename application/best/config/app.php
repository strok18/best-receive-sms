<?php

return [
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => Env::get('app_path') . 'best/tpl/jump.html',
    'dispatch_error_tmpl'    => Env::get('app_path') . 'best/tpl/jump.html',
    'exception_tmpl'         => Env::get('app_path') . 'best/tpl/exception.html',
    // 默认语言
];