<?php
namespace apps\api\service\v1\auth;

/**
 * 修改密码
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-20
 */

use apps\api\service\v1\ApiService;
use apps\common\service\AuthService;

class ChangepwdService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'post' => 'POST - 设置修改密码'
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'post' => [
      'token'      => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'oldPwd'     => [ '原密码' , '' , PARAM_REQUIRED ] ,
      'pwd'        => [ '新密码' , '' , PARAM_REQUIRED ] ,
      'pwdConfirm' => [ '密码确认' , '' , PARAM_REQUIRED ] ,
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
      self::$instance         = new ChangepwdService();
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
   * @return array|number
   */
  public function post() {
    $Auth = AuthService::instance();
    
    $result = $Auth->apiChangePwd(
      $this->userId ,
      $this->params['oldPwd'] ,
      $this->params['pwd'] ,
      $this->params['pwdConfirm'] );
    
    if ( $result['code'] == 0 ) {
      $result['msg'] = '修改密码成功';
    }
    
    return $result;
  }
  
}
