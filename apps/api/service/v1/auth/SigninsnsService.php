<?php
namespace apps\api\service\v1\auth;

/**
 * 第三方平台登录
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-21
 */

use apps\api\service\v1\ApiService;
use apps\common\service\AuthService;

class SigninsnsService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取第三方平台登录' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'merId'          => [ '商户ID' , '' , PARAM_REQUIRED ] ,
      'platform'       => [ '第三方平台' , 'qq' , [ 'qq' => 'QQ' , 'wx' => '微信' , 'wb' => '微博' ] ] ,
      'snsUid'         => [ '第三方用户ID uid/openid' , '' , PARAM_REQUIRED ] ,
      'username'       => [ '用户名 username/screen_name' , '' , PARAM_REQUIRED ] ,
      'icon'           => [ '用户头像 profile_image_url' , '' ] ,
      'gender'         => [ '性别 gender' , '' ] ,
      'location'       => [ '用户位置' , '' ] ,
      'province'       => [ '省份' , '' ] ,
      'city'           => [ '城市' , '' ] ,
      'registrationId' => [ '激光推送ID' , '' ] ,
    ] ,
  
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get' => [
      "id"         => "id" ,
      "sex"        => "性别" ,
      "username"   => "用户名" ,
      "nickname"   => "昵称" ,
      "icon"       => "头像" ,
      "phone"      => "手机号" ,
      "status"     => "状态" ,
      "industries" => "工作或行业" ,
      "reg_from"   => "注册来源" ,
      "reg_at"     => "注册时间" ,
      "login_at"   => "登录时间" ,
      "token"      => "Token"
    ] ,
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new SigninsnsService();
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
    
    
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    //处理业务
    switch ( request()->method() ) {
      case 'GET' :
        return $data = $this->get();
      default :
        return api_result( '未知请求类型' , 500 );
    }
  }
  
  /**
   * get 的响应方法
   *
   * @return array|number
   */
  public function get() {
    $Auth = AuthService::instance();
    
    $result = $Auth->apiSignInBySns( $this->params );
    if ( $result['code'] == 0 ) {
      $result['data'] = $this->formatData( $result['data'] );
    }
    
    return $result;
  }
}
