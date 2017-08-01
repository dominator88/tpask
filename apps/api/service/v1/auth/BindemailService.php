<?php
namespace apps\api\service\v1\auth;

/**
 * 绑定邮箱
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-21
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerUserService;
use apps\common\service\SysMailService;

class BindemailService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'post' => 'POST - 设置绑定邮箱' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'post' => [
      'merId'   => [ '商户ID' , '' , PARAM_REQUIRED ] ,
      'token'   => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'email'   => [ '用户Email' , '' , PARAM_REQUIRED ] ,
      'captcha' => [ '邮箱验证码' , '' , PARAM_REQUIRED ]
    ] ,
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'post' => [] ,
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new BindemailService();
      self::$instance->params = $params;
    }
    
    return self::$instance;
  }
  
  /**
   * 接口响应方法
   *
   * @return array
   */
  public function response() {
    //验证用户
    if ( ! $this->validToken() ) {
      return api_result( $this->error , $this->errCode );
    }
    
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    //处理业务
    switch ( request()->method() ) {
      case 'POST' :
        return $this->post();
      default :
        return api_result( '未知请求类型' , 500 );
    }
  }
  
  
  /**
   * post 的响应方法
   *
   * @return array
   */
  public function post() {
    $SysMail = SysMailService::instance();
    if ( ! $SysMail->validCaptcha( $this->params['email'] , $this->params['captcha'] ) ) {
      return api_result( '验证码不正确' , 500 );
    }
    
    $MerUser = MerUserService::instance();
    
    return $MerUser->bindEmail( $this->params['merId'] , $this->userId , $this->params['email'] );
  }
  
}
