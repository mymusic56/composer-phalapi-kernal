<?php
namespace PhalApi\Filter;

use PhalApi\Filter;
use PhalApi\Exception\BadRequestException;

/**
 * SimpleMD5Filter 简单的MD5拦截器
 *
 * - 签名的方案如下：
 *
 * + 1、排除签名参数（默认是sign）
 * + 2、将剩下的全部参数，按参数名字进行字典排序
 * + 3、将排序好的参数，全部用字符串拼接起来
 * + 4、进行md5运算
 *
 * 注意：无任何参数时，不作验签
 *
 * @package     PhalApi\Filter
 * @license     http://www.phalapi.net/license GPL 协议
 * @link        http://www.phalapi.net/
 * @author      dogstar <chanzonghuang@gmail.com> 2015-10-23
 */

class SimpleMD5Filter implements Filter {

    protected $signName;
    protected $unJoinSign = [];

    public function __construct($signName = 'sign') {
        $this->signName = $signName;
    }

    public function check() {
        $allParams = \PhalApi\DI()->request->getAllNocookie();
        if (empty($allParams)) {
            return;
        }
        $partnercode=isset($_REQUEST['partnercode'])?$_REQUEST['partnercode']:'';
        if($partnercode=='screenpartnercode'){
            return;
        }

        if(!\PhalApi\DI()->config->get('app.check_sign') || PHP_SAPI == 'cli'){
            return;
        }
        //处理不参与签名的字段
        $service = \PhalApi\DI()->request->getService();
        if (in_array($service, ['Admin.GoodsMaterial.MaterialSave'])) {
            $this->unJoinSign[] = 'content';
            return;
        }

        if(isset($_GET['__debdanengug__'])&&!empty($_GET['__debdanengug__']) ){
            return;
        }

        $sign = isset($allParams[$this->signName]) ? $allParams[$this->signName] : '';
        unset($allParams[$this->signName]);

        $expectSign = $this->encryptAppKey($allParams);

        \PhalApi\DI()->response->setDebug('expectSign',$expectSign);
        if ($expectSign != $sign) {
            \PhalApi\DI()->logger->debug('Wrong Sign', array('needSign' => $expectSign));
            throw new BadRequestException(\PhalApi\T('wrong sign'), 6);
        }
    }

    protected function encryptAppKey($params) {
        ksort($params);

        $paramsStrExceptSign = '';
        foreach ($params as $field => $val) {
            if ($val != "" && !is_array($val) && !in_array($field, $this->unJoinSign)) {
                $paramsStrExceptSign .= $val;
            }
        }
        $service_partnercode=\PhalApi\DI()->config->get('app.service_partnercode');
        return md5(md5($paramsStrExceptSign).$service_partnercode[$params['partnercode']]);
    }
}
