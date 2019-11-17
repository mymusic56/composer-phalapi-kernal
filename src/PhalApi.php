<?php
namespace PhalApi;

use PhalApi\ApiFactory;
use PhalApi\Exception;
use PhalApi\Logger\FileLogger;

/**
 * PhalApi 应用类
 *
 * - 实现远程服务的响应、调用等操作
 * 
 * <br>使用示例：<br>
```
 * $api = new PhalApi();
 * $rs = $api->response();
 * $rs->output();
```
 *
 * @package     PhalApi\Response
 * @license     http://www.phalapi.net/license GPL 协议
 * @link        http://www.phalapi.net/
 * @author      dogstar <chanzonghuang@gmail.com> 2014-12-17
 */

class PhalApi {
    
    /**
     * 响应操作
     *
     * 通过工厂方法创建合适的控制器，然后调用指定的方法，最后返回格式化的数据。
     *
     * @return mixed 根据配置的或者手动设置的返回格式，将结果返回
     *  其结果包含以下元素：
```
     *  array(
     *      'ret'   => 200,	            //服务器响应状态
     *      'data'  => array(),	        //正常并成功响应后，返回给客户端的数据	
     *      'msg'   => '',		        //错误提示信息
     *  );
```
     */
    public function response() {
        $di = DI();

        // 开始响应接口请求
        $di->tracer->mark('PHALAPI_RESPONSE');

        $rs = $di->response;
        $logger = new FileLogger(API_ROOT . "/runtime/".(isset($_REQUEST['service'])&&$_REQUEST['service']!=''?$_REQUEST['service']:'no'), Logger::LOG_LEVEL_DEBUG | Logger::LOG_LEVEL_INFO | Logger::LOG_LEVEL_ERROR);
        $logger->info('接受的数据', $_REQUEST);
        $partnercode=isset($_REQUEST['partnercode'])?$_REQUEST['partnercode']:'';
        $service=isset($_REQUEST['service'])&&$_REQUEST['service']!=''?$_REQUEST['service']:'no';
        $partnercodLimt=\PhalApi\DI()->config->get('app.partnercodLimt');
        if(isset($partnercodLimt[$partnercode])&&!in_array($service,$partnercodLimt[$partnercode])){
            $rs->setRet(800);
            $rs->setMsg('非法访问1');
            return $rs;
        }
//
//        if('autopartnercode'==$partnercode&&$di->request->getServiceApi()!='Auto'){
//            $rs->setRet(800);
//            $rs->setMsg('非法访问2');
//            return $rs;
//        }
//        if($di->request->getServiceApi()=='Auto'&&'autopartnercode'!=$partnercode){
//            $rs->setRet(800);
//            $rs->setMsg('非法访问3');
//            return $rs;
//        }

//        if($partnercode=='minipartnercode'){
//            foreach ($partnercodLimt as $k=>$v){
//                if($k!='minipartnercode'&&in_array($service,$partnercodLimt[$k])){
//                    $rs->setRet(800);
//                    $rs->setMsg('非法访问');
//                    return $rs;
//                }
//            }
//        }

        try {
            // 接口调度与响应
            $api    = ApiFactory::generateService();
            $action = $di->request->getServiceAction();
            $data   = call_user_func(array($api, $action));
            if (isset($data['error'])) {
                $data['error'] = strval($data['error']);
            }
            if($service=='App.User.Getwxacodeunlimit'){
                //获取小程序码  直接输出返回的结果
                echo $data;
                die();
            }
            if(!isset($data['token'])){
                $data['token']=$di->token;
            }
            $rs->setData($data);
        } catch (Exception $ex) {
            // 框架或项目可控的异常
            $rs->setRet($ex->getCode());
            $rs->setMsg($ex->getMessage());
        } catch (\Exception $ex) {
            // 不可控的异常
            $di->logger->error(DI()->request->getService(), strval($ex));

            if ($di->debug) {
                $rs->setRet($ex->getCode());
                $rs->setMsg($ex->getMessage());
                $rs->setDebug('exception', $ex->getTrace());
            } else {
                $di->Csession->set($di->token,$di->session,\PhalApi\DI()->config->get('sys.sesseion_expire'));
                throw $ex;
            }
        }
       // $logger->info('返回的数据', $rs->getResult());

        // 结束接口调度
        $di->tracer->mark('PHALAPI_FINISH');

        $rs->setDebug('stack', $di->tracer->getStack());
        $rs->setDebug('sqls', $di->tracer->getSqls());
        $rs->setDebug('version', PHALAPI_VERSION);

        return $rs;
    }
    //析构函数
    public function __destruct()
    {
        $di = DI();
        if(is_null($di->session)){
            $di->Csession->delete($di->token);
        }else{
            $di->Csession->set($di->token,$di->session,\PhalApi\DI()->config->get('sys.sesseion_expire'));
        }
    }
    
}
