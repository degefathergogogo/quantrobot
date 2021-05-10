<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/30
 * Time: 11:49
 */

namespace api\wallet\service;

class ColumnName
{


    public static $db_transfer_log = [
        'type' => [
            '1' => '云端转出',
            '2' => '云端充值',
            '3' => '自动汇总',
        ],
        'transfer_status' => [
            '-1' => '失败',
            '0' => '等待处理',
            '2' => '交易中',
            '1' => '交易成功',
        ],
    ];
}