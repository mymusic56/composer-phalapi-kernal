<?php
namespace PhalApi\Pay\Weixin;
/**
*
* example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
**/
require_once dirname(__FILE__)."/lib/WxPay.Api.php";
require_once dirname(__FILE__)."/log.php";
/**
 * 
 * 微信退款
 * 
 * @author luo
 *
 */
class WxRefund
{
    public $logger;
    public $config;
    public function __construct($logger,$config)
    {
        $this->logger=$logger;
        $this->config=$config;
    }

    /**
     * 执行退款操作
     */
    public  function Refund($payRefunInfo){
        $input = new \WxPayRefund();
        $input->SetTransaction_id($payRefunInfo['transaction_id']);
        $input->SetTotal_fee($payRefunInfo['total_fee']*100);
        $input->SetRefund_fee($payRefunInfo['refund_fee']*100);
        $input->SetOut_refund_no($payRefunInfo['reorder_sn']);
        $input->SetOp_user_id($this->config->GetMerchantId());
        $this->logger->info("提交的数据：",$input->GetValues());
        $result= \WxPayApi::refund($this->config, $input);
        $this->logger->info("返回的结果数据：",$result);
        return $result;
    }
}
