<?php
namespace apps\api\service\v1\system;

/**
 * 取PingPP Charge
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-20
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerPayService;

class ChargeService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 获取PingPP Charge' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'token'   => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'merId'   => [ '商户ID' , 1 , PARAM_REQUIRED ] ,
      'orderId' => [ '订单ID' , '' , PARAM_REQUIRED ] ,
    ] ,
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get' => []
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new ChargeService();
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
      case 'GET' :
        return $this->get();
      default:
        return api_result( '未知请求类型' , 500 );
    }
  }
  
  /**
   * get 请求
   *
   * @return array
   */
  public function get() {
    $MerPay = MerPayService::instance();
    $data   = $MerPay->getOrderCharge(
      $this->params['merId'] ,
      $this->userId ,
      $this->params['orderId'] );
    
    
    return api_result( '查询成功' , 0 , $data );
  }
  
}
