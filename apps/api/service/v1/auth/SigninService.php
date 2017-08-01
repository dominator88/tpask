<?php
namespace apps\api\service\v1\auth;

/**
 * 用户登录
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-18
 */

use apps\api\service\v1\ApiService;
use apps\common\service\AuthService;

class SignInService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 用户登录'
  ];
  
  /**
   * 传参 如:
   * "title" => ['标题' , '默认值' ]
   * "status" => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      "merId"          => [ '商户ID' , '1' , PARAM_REQUIRED ] ,
      'userInfo'       => [ '手机号或Email' , '' , PARAM_REQUIRED ] ,
      'pwd'            => [ '密码' , '' , PARAM_REQUIRED ] ,
      'registrationId' => [ '激光推送注册ID' , '' ]
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   */
  public $defaultResponse = [
    'get' => [
      "id"         => "id" ,
      "sex"        => "性别" ,
      "username"   => "用户名" ,
      "nickname"   => "昵称" ,
      "icon"       => [ "头像" , 'formatIcon' ] ,
      "phone"      => "手机号" ,
      "email"      => '用户邮箱' ,
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
      self::$instance         = new SignInService();
      self::$instance->params = $params;
    }
    
    return self::$instance;
  }
  
  //接口响应方法
  function response() {
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    $Auth   = AuthService::instance();
    $result = $Auth->apiSignInByUserInfo( $this->params );
    
    //格式化返回数据
    $result['data'] = $this->formatData( $result['data'] );
    
    return $result;
  }
  
}
