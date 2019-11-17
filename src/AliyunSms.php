<?php

namespace PhalApi;

if (!defined('ALIYUN_ROOT')) {
    define('ALIYUN_ROOT', dirname(__FILE__) . '/');
    require_once(ALIYUN_ROOT . 'aliyun_sms/vendor/autoload.php');

}

use Aliyun\Core\Config as AliyunConfig;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\SendBatchSmsRequest;

//use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;

// 加载区域结点配置
AliyunConfig::load();

/**
 * 阿里云短信接口
 *
 * Created on 17/10/17.
 * 短信服务API产品的DEMO程序,工程中包含了一个SmsDemo类，直接通过
 * 执行此文件即可体验语音服务产品API功能(只需要将AK替换成开通了云通信-短信服务产品功能的AK即可)
 * 备注:Demo工程编码采用UTF-8
 */
class AliyunSms
{
    static $acsClient = null;
    static $singname;  //签名名称
    static $accesskeyid;   //yourAccessKeyId
    static $accesskeysecret;   //yourAccessKeySecret
    static $region;    //cn-hangzhou
    static $endpointname;  //服务结点

    public function __construct($config = null)
    {
        self::$singname = $config['SignName'];
        self::$accesskeyid = $config['AccessKeyId'];
        self::$accesskeysecret = $config['AccessKeySecret'];
        self::$region = isset($config['region']) ? $config['region'] : 'cn-hangzhou';
        self::$endpointname = isset($config['endpointname']) ? $config['endpointname'] : 'cn-hangzhou';
    }

    /**
     * 取得AcsClient
     *
     * @return DefaultAcsClient
     */
    public static function getAcsClient()
    {
        //产品名称:云通信流量服务API产品,开发者无需替换
        $product = "Dysmsapi";
        //产品域名,开发者无需替换
        $domain = "dysmsapi.aliyuncs.com";

        if (static::$acsClient == null) {
            //初始化acsClient,暂不支持region化
            $profile = DefaultProfile::getProfile(self::$region, self::$accesskeyid, self::$accesskeysecret);
            // 增加服务结点
            DefaultProfile::addEndpoint(self::$endpointname, self::$region, $product, $domain);
            // 初始化AcsClient用于发起请求
            static::$acsClient = new DefaultAcsClient($profile);
        }
        return static::$acsClient;
    }

    /**
     * 发送短信
     * @return stdClass
     */
    public static function sendSms($tempCode, $mobilephone, $parmas = array())
    {
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        // 必填，设置短信接收号码
        $request->setPhoneNumbers($mobilephone);

        // 必填，设置签名名称，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $request->setSignName(self::$singname);

        // 必填，设置模板CODE，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $request->setTemplateCode(static::template_code($tempCode));


        //可选:模板中的变量替换JSON串,如模板内容为"亲爱的${name},您的验证码为${code}"时,此处的值为
        /*$request->setTemplateParam(json_encode(array(  // 短信模板中字段的值
            "code" => "12345",
            "product" => "dsd"
        ), JSON_UNESCAPED_UNICODE));*/
        if (!empty($parmas)) {
            $request->setTemplateParam(json_encode($parmas, JSON_UNESCAPED_UNICODE));
        }
        // 可选，设置流水号
//        $request->setOutId("yourOutId");

        // 选填，上行短信扩展码（扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段）
//        $request->setSmsUpExtendCode("1234567");

        // 发起访问请求
        \PhalApi\DI()->logger->debug('aliyun_request:',var_export($request,true));
        $acsResponse = static::getAcsClient()->getAcsResponse($request);
        \PhalApi\DI()->logger->debug('aliyun_acsResponse:',var_export($acsResponse,true));
        return $acsResponse;
    }

    /**
     * 批量发送短信
     * @return stdClass
     */
    public static function sendBatchSms($tempCode, $mobilephone, $parmas = array(), $signname = array())
    {

        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendBatchSmsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        // 必填:待发送手机号。支持JSON格式的批量调用，批量上限为100个手机号码,批量调用相对于单条调用及时性稍有延迟,验证码类型的短信推荐使用单条调用的方式
        $request->setPhoneNumberJson(json_encode($mobilephone, JSON_UNESCAPED_UNICODE));

        // 必填:短信签名-支持不同的号码发送不同的短信签名
        if (empty($signname)) {
            for ($i = 0; $i < count($mobilephone); $i++) {
                $signname[] = self::$singname;
            }
        }
        $request->setSignNameJson(json_encode($signname, JSON_UNESCAPED_UNICODE));

        // 必填:短信模板-可在短信控制台中找到
        $request->setTemplateCode(static::template_code($tempCode));

        // 友情提示:如果JSON中需要带换行符,请参照标准的JSON协议对换行符的要求,比如短信内容中包含\r\n的情况在JSON中需要表示成\\r\\n,否则会导致JSON在服务端解析失败
        if (!empty($parmas)) {
            $request->setTemplateParamJson(json_encode($parmas, JSON_UNESCAPED_UNICODE));
        }

        // 可选-上行短信扩展码(扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段)
        // $request->setSmsUpExtendCodeJson("[\"90997\",\"90998\"]");

        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);

        return $acsResponse;
    }

    protected static function template_code($temp_code)
    {
        $templateCode = array(
            't1' => 'SMS_158650159',    //身份验证验证码
            't2' => 'SMS_158650158',    //登录确认验证码
            't3' => 'SMS_158650157',    //登录异常验证码
            't4' => 'SMS_158650156',    //用户注册验证码
            't5' => 'SMS_158650155',    //修改密码验证码
            't6' => 'SMS_158650154',    //信息变更验证码
            't7' =>'SMS_158635113',      //注册验证码
            't8' =>'SMS_158650155'      //登录找回密码
        );
        return $templateCode[$temp_code];
    }

    protected static function template_code_content($temp_code)
    {
        $templateContent = array(
            't1' => '验证码${s1}，您正在进行身份验证，打死不要告诉别人哦！',
            't2' => '验证码${s1}，您正在登录，若非本人操作，请勿泄露。',
            't3' => '验证码${s1}，您正尝试异地登录，若非本人操作，请勿泄露。',
            't4' => '验证码${s1}，您正在注册成为新用户，感谢您的支持！',
            't5' => '验证码${s1}，您正在尝试修改登录密码，请妥善保管账户信息。',
            't6' => '验证码${s1}，您正在尝试变更重要信息，请妥善保管账户信息。',
            't7' => '验证码${s1}，您正在尝试修改登录密码，请妥善保管账户信息。',
        );
        return $templateContent[$temp_code];
    }

    /**
     * 短信发送记录查询
     * @return stdClass
     */
    /*public static function querySendDetails()
    {

        // 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
        $request = new QuerySendDetailsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        // 必填，短信接收号码
        $request->setPhoneNumber("12345678901");

        // 必填，短信发送日期，格式Ymd，支持近30天记录查询
        $request->setSendDate("20170718");

        // 必填，分页大小
        $request->setPageSize(10);

        // 必填，当前页码
        $request->setCurrentPage(1);

        // 选填，短信发送流水号
        $request->setBizId("yourBizId");

        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);

        return $acsResponse;
    }*/

}
