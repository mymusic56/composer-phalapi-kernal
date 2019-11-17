<?php
namespace PhalApi\Exception;

use PhalApi\Exception;

/**
 * ErrorException 报错信息
 *
 * 用户自定义报错200
 * 210 未登录
 *
 *
 * @package     PhalApi\Exception
 * @license     http://www.phalapi.net/license GPL 协议
 * @link        http://www.phalapi.net/
 * @author      dogstar <chanzonghuang@gmail.com> 2017-07-01
 */

class ErrorException extends Exception {

    public function __construct($message, $code = 0) {
        parent::__construct(
            \PhalApi\T('{message}', array('message' => $message)), 200 + $code
        );
    }
}
