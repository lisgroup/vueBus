<?php
/**
 * 存储本系统输出的所有错误码
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | 通用错误码
    |--------------------------------------------------------------------------
    */
    
    '200' => [
        'reason' => 'success'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | 订单模块，以10开头
    |--------------------------------------------------------------------------
    */
    
    '1000' => [
        'reason' => '下单失败'
    ],
    '1001' => [
        'reason' => '用户名错误'
    ],
    '1002' => [
        'reason' => '无效的订单列表'
    ],
    '1003' => [
        'reason' => '网络繁忙，请稍后重试'
    ],
    '1004' => [
        'reason' => '数据源错误，非标准数据格式'
    ],
    '1005' => [
        'reason' => '下单过于频繁，请稍后重试'
    ],
    '1006' => [
        'reason' => '参数错误'//具体参数错误信息请自定义
    ],
    '1007' => [
        'reason' => '获取报告名称列表失败'
    ],
    '1008' => [
        'reason' => '订单删除失败'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | 报告模块，以11开头
    |--------------------------------------------------------------------------
    */
    
    '1100' => [
        'reason' => '网络繁忙，请稍后重试'
    ],
    '1101' => [
        'reason' => '订单号错误'
    ],
    '1102' => [
        'reason' => 'vin信息查询失败'
    ],
    '1103' => [
        'reason' => '数据源错误，非标准数据格式'
    ],
    '1104' => [
        'reason' => '参数错误'
    ],

    /*
    |--------------------------------------------------------------------------
    | 用户模块，以12开头
    |--------------------------------------------------------------------------
    */

    '1200' => [
        'reason' => '用户未登录或登录超时'
    ],
    '1201' => [
        'reason' => '用户名或密码错误'
    ],
    '1202' => [
        'reason' => '账号被锁定，请联系管理员'
    ],
    '1203' => [
        'reason' => '手机号为空或格式错误'
    ],
    '1204' => [
        'reason' => '密码为空或小于6个字符'
    ],
    '1205' => [
        'reason' => '该手机号未注册'
    ],
    '1206' => [
        'reason' => '该手机号已注册'
    ],
    '1207' => [
        'reason' => '验证码错误'
    ],
    '1209' => [
        'reason' => '登录错误次数过多，禁止登录'
    ],
    '1213' => [
        'reason' => '重复密码和新密码不一致'
    ],

    /*
    |--------------------------------------------------------------------------
    | 用户模块验证码，以121开头
    |--------------------------------------------------------------------------
    */

    '1210' => [
        'reason' => '验证码参数错误'
    ],
    '1211' => [
        'reason' => '验证码发送超限'
    ],
    '1212' => [
        'reason' => '验证码错误或过期'
    ],

    /*
    |--------------------------------------------------------------------------
    | 支付，以14开头
    |--------------------------------------------------------------------------
    */

    '1400' => [
        'reason' => '用户余额不足'
    ],
    '1401' => [
        'reason' => '扣款失败'
    ],
    '1403' => [
        'reason' => '付款方式不正确'
    ],
    '1404' => [
        'reason' => '付款订单错误'
    ],
    '1405' => [
        'reason' => '支付时候错误'
    ],
    '1406' => [
        'reason' => '更新付款记录失败'
    ],
    '1407' => [
        'reason' => '获取支付地址失败'
    ],
    '1408' => [
        'reason' => '解析回调失败',
    ],
    '1409' => [
        'reason' => '回调签名错误',
    ],
    '1410' => [
        'reason' => '无此订单',
    ],
    '1411' => [
        'reason' => '重复回调',
    ],
    '1412' => [
        'reason' => '订单金额不一致',
    ],

    /*
    |--------------------------------------------------------------------------
    | 异常模块，以 40 开头
    |--------------------------------------------------------------------------
    */
    '4000' => [
        'reason' => '错误请求'
    ],
    '4001' => [
        'reason' => '未授权'
    ],
    '4002' => [
        'reason' => '登录失败'
    ],
    '4003' => [
        'reason' => '服务器拒绝访问'
    ],
    '4004' => [
        'reason' => 'Resource not found.'
    ],
    '4005' => [
        'reason' => '旧密码输入有误'
    ],

    /*
    |--------------------------------------------------------------------------
    | 异常模块，以 50 开头
    |--------------------------------------------------------------------------
    */
    '5000' => [
        'reason' => '系统错误'
    ],
    '5001' => [
        'reason' => 'Token 异常黑名单'
    ],
    '5002' => [
        'reason' => '未生成全文索引的错误'
    ],
];
