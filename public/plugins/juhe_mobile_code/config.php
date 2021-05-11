<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
return array (
    'app_key' => array (// 在后台插件配置表单中的键名 ,会是config[text]
        'title' => 'APP KEY', // 表单的label标题
        'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => '',// 表单的默认值
        'tip' => '聚合短信应用key' //表单的帮助提示
    ),
    'template_id' => array (// 在后台插件配置表单中的键名 ,会是config[text]
        'title' => '模板ID', // 表单的label标题
        'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => '',// 表单的默认值
        'tip' => '模板Id' //表单的帮助提示
    ),
    'expire_minute' => array (// 在后台插件配置表单中的键名 ,会是config[text]
        'title' => '有效期', // 表单的label标题
        'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => '30',// 表单的默认值
        'tip' => '短信验证码过期时间，单位分钟' //表单的帮助提示
    ),
);
					