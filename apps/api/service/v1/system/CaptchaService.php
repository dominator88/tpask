<?php
namespace apps\api\service\v1\system;

/**
 * 发送验证码
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-19
 */

use apps\api\service\v1\ApiService;
use apps\common\service\SysMailService;
use apps\common\service\SysSmsService;

class CaptchaService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 发送验证码'
  ];
  
  /**
   * 传参 如:
   * "title" => ['标题' , '默认值' ]
   * "status" => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'type'     => [ '发送类型' , 'phone' , [ 'phone' => '发送到手机' , 'email' => '发送到邮箱' ] ] ,
      "userInfo" => [ '手机号/邮箱' , '' , PARAM_REQUIRED ] ,
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   */
  public $defaultResponse = [
    'get' => []
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new CaptchaService();
      self::$instance->params = $params;
    }
    
    return self::$instance;
  }
  
  //接口响应方法
  function response() {
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    $result = [];
    switch ( $this->params['type'] ) {
      case 'phone' :
        $SysSms = SysSmsService::instance();
        $result = $SysSms->createByCaptcha( $this->params['userInfo'] , TRUE );
        break;
      case 'email' :
        $SysMail = SysMailService::instance();
        $result  = $SysMail->sendCaptcha( $this->params['userInfo'] );
        break;
    }
    
    if ( $result['code'] != 0 ) {
      return $result;
    }
    
    return api_result( '验证码已发送' , 0 );
  }
  
}
