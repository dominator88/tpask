<?php
namespace apps\api\service\v1\auth;

/**
 * 忘记密码重置
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-20
 */

use apps\api\service\v1\ApiService;
use apps\common\service\AuthService;

class ResetpwdService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'post' => 'POST - 修改密码'
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'post' => [
      'merId'    => [ '商户ID' , 1 , PARAM_REQUIRED ] ,
      'type'     => [ '验证码类型' , 'phone' , [ 'phone' => '手机验证码' , 'email' => '邮件验证码' ] ] ,
      'userInfo' => [ '手机号/邮箱' , '' , PARAM_REQUIRED ] ,
      'captcha'  => [ '验证码' , '' , PARAM_REQUIRED ] ,
      'pwd'      => [ '新密码' , '' , PARAM_REQUIRED ]
    ]
  
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'post' => []
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new ResetpwdService();
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
      case 'POST' :
        return $this->post();
      default:
        return api_result( '未知请求类型' , 500 );
    }
  }
  
  /**
   * post 的响应方法
   *
   * @return array
   */
  public function post() {
    
    $result = [];
    $Auth   = AuthService::instance();
    if ( $this->params['type'] == 'phone' ) {
      $result = $Auth->apiResetPwdByPhone(
        $this->params['merId'] ,
        $this->params['userInfo'] ,
        $this->params['captcha'] ,
        $this->params['pwd']
      );
    } else if ( $this->params['type'] == 'email' ) {
      $result = $Auth->apiResetPwdByEmail(
        $this->params['merId'] ,
        $this->params['userInfo'] ,
        $this->params['captcha'] ,
        $this->params['pwd']
      );
    }
    
    return $result;
  }
  
}
