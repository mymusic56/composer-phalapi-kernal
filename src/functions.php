<?php
namespace PhalApi;

use PhalApi\DependenceInjection;
use PhalApi\Translator;

/**
 * 框架版本号
 */
defined('PHALAPI_VERSION') || define('PHALAPI_VERSION', '2.3.1');

/**
 * 考虑再三，出于人性化关怀，提供要些快速的函数和方法
 *
 * @license     http://www.phalapi.net/license GPL 协议
 * @link        http://www.phalapi.net/
 * @author      dogstar <chanzonghuang@gmail.com> 2014-12-17
 */

/**
 * 获取DI
 * 相当于DependenceInjection::one()
 * @return \PhalApi\DependenceInjection
 */
function DI() {
    return DependenceInjection::one();
}

/**
 * 设定语言，SL为setLanguage的简写
 * @param string $language 翻译包的目录名
 */
function SL($language) {
	Translator::setLanguage($language);
}

/**
 * 快速翻译
 * @param string $msg 待翻译的内容
 * @param array $params 动态参数
 */
function T($msg, $params = array()) {
    return Translator::get($msg, $params);
}

/**
 * session管理函数
 * @param string|array $name session名称 如果为数组则表示进行session设置
 * @param mixed $value session值
 * @return mixed
 */
function session($name='',$value=''){
    $session=DI()->session;
    if(is_array($name)){
        $session=$name;
    }
    elseif($name===''){
        return $session;
    }
    elseif(is_null($name)){
        $session=null;
    }
    elseif($value!==''){
        $session[$name]=$value;
    }elseif($value===''){
        return isset($session[$name])?$session[$name]:null;
    }elseif(is_null($value)){
        unset($session[$name]);
    }
    DI()->session=$session;
}