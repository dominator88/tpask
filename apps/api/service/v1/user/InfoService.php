<?php
namespace apps\api\service\v1\user;

/**
 * 用户信息
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-25
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerUserService;

class InfoService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取用户信息' ,
    'put' => 'PUT - 设置用户信息' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'token' => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'field' => [ '字段(字段为空取全部信息)' , '' ]
    ] ,
    'put' => [
      'merId' => [ '商户ID' , '' , PARAM_REQUIRED ] ,
      'token' => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'field' => [ '字段' , 'icon' , [ 'icon' => '头像' , 'nickname' => '昵称' , 'sex' => '性别' ] ] ,
      'value' => [ '值' , '' , PARAM_REQUIRED ]
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
      "icon"       => [ "头像" , 'formatIcon' ] ,
      "phone"      => "手机号" ,
      "email"      => '用户邮箱' ,
      "status"     => "状态" ,
      "industries" => "工作或行业" ,
      "reg_from"   => "注册来源" ,
      "reg_at"     => "注册时间" ,
      "login_at"   => "登录时间" ,
      "token"      => "Token"
    ] ,
    'put' => [] ,
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new InfoService();
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
      case 'GET' :
        return api_result( '查询成功' , 0 , $this->get() );
      case 'PUT' :
        return $this->put();
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
    $MerUser  = MerUserService::instance();
    $userData = $MerUser->getById( $this->userId );
    
    if ( empty( $this->params['field'] ) ) {
      $userData['token'] = $this->params['token'];
      $userData          = $this->formatData( $userData , $this->defaultResponse['get'] );
      
      return $userData;
    } else {
      $field = $this->params['field'];
      if ( $field == 'icon' ) {
        $data[ $field ] = $this->formatIcon( $userData[ $this->params['field'] ] );
      } else {
        $data[ $field ] = $userData[ $this->params['field'] ];
      }
    }
    
    return $data;
  }
  
  
  /**
   * post 的响应方法
   *
   * @return array
   */
  public function put() {
    $MerUser = MerUserService::instance();
    
    $data['mer_id'] = $this->merId;
    $field          = $this->params['field'];
    $value          = $this->params['value'];
    
    //验证头像
    if ( $field == 'icon' ) {
      if ( ! filter_var( $value , FILTER_VALIDATE_URL ) ) {
        return api_result( '请输入正确的uri' );
      }
      //去掉uri 中的 img uri
      $value = str_replace( config( 'imgUri' ) , '' , $value );
    } elseif ( $field == 'sex' ) {
      if ( ! in_array( $value , $MerUser->sex ) ) {
        return api_result( '请输入正确的sex' );
      }
    }
    
    $data[ $field ] = $value;
    $result         = $MerUser->update( $this->userId , $data );
    
    return $result;
  }
  
}
