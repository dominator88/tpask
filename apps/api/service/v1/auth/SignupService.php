<?php
namespace apps\api\service\v1\auth;

/**
 * 用户注册
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-19
 */

use apps\api\service\v1\ApiService;
use apps\common\service\AuthService;

class SignupService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'post' => 'POST - 用户注册'
  ];
  
  /**
   * 传参 如:
   * "title" => ['标题' , '默认值' ]
   * "status" => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'post' => [
      "merId"          => [ '商户ID' , '1' , PARAM_REQUIRED ] ,
      'type'           => [ '登录类型' , 'phone' , [ 'phone' => '手机' , 'email' => 'Email注册' ] ] ,
      "userInfo"       => [ '手机号或email' , '' , PARAM_REQUIRED ] ,
      "pwd"            => [ '密码' , '' , PARAM_REQUIRED ] ,
      "captcha"        => [ '手机或email验证码' , '' , PARAM_REQUIRED ] ,
//      'username'       => [ '用户名' , '' , PARAM_REQUIRED ] ,
//      'industries'     => [ '工作' , '' , PARAM_REQUIRED ] ,
      'registrationId' => [ '激光注册ID' , '' ] ,
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   */
  public $defaultResponse = [
    'post' => [
      "id"         => "id" ,
      "sex"        => "性别" ,
      "username"   => "用户名" ,
      "nickname"   => "昵称" ,
      "icon"       => [ "头像" , 'formatIcon' ] ,
      "phone"      => "手机号" ,
      "status"     => "状态" ,
      "industries" => "工作或行业" ,
      "reg_from"   => "注册来源" ,
      "reg_at"     => "注册时间" ,
      "login_at"   => "登录时间" ,
      "token"      => "Token"
    ]
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new SignupService();
      self::$instance->params = $params;
    }
    
    return self::$instance;
  }
  
  //接口响应方法
  function response() {
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    $Auth = AuthService::instance();
    if ( $this->params['type'] == 'phone' ) {
      $result = $Auth->apiSignUpByPhone( $this->params );
    } elseif ( $this->params['type'] == 'email' ) {
      $result = $Auth->apiSignUpByEmail( $this->params );
    } else {
      return api_result( '未知的type' , 500 );
    }
    
    $result['data'] = $this->formatData( $result['data'] );
    
    return $result;
  }
  
}
