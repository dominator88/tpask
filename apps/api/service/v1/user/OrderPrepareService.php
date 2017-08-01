<?php
namespace apps\api\service\v1\user;

/**
 * 订单准备
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-13
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerUserService;
use apps\api\service\v1\goods\IndexService as GoodsService;

class OrderprepareService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 订单准备' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'token'    => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      "type"     => [ '订单类型' , 'goods' , [ 'goods' => '商品' , 'virtual' => '虚拟商品' , 'service' => '服务' ] ] ,
      "currency" => [ '货币类型' , 'cny' , [ 'cny' => '人民币' , 'points' => '积分' ] ] ,
      'items'    => [ '商品(JSON字符串), 如:[{"id":"1","qty":"1"}]' , [ "id" => '商品ID' , "qty" => '数量' ] , 'array' ]
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get' => [
      'goods'      => [
        "id"           => "商品ID" ,
        "name"         => "商品名称" ,
        "catalog_id"   => "分类ID" ,
        "catalog_text" => "分类text" ,
        "catalog_type" => "分类type" ,
        "highlight"    => "亮点" ,
        "icon"         => [ '图标' , "formatIcon" ] ,
        "desc"         => "描述" ,
        "currency"     => "货币" ,
        "price_market" => "市场价" ,
        "price"        => "销售价" ,
        "points"       => "可获积分" ,
        "status"       => "状态" ,
        "recommend"    => "是否推荐" ,
        "hot"          => "是否热卖" ,
        "cheap"        => "是否特价"
      ] ,
      'bucks'      => "余额" ,
      'points'     => "积分" ,
      'address'    => [
        "id"         => "收货地址ID" ,
        "user_id"    => "用户ID" ,
        "name"       => "收货人姓名" ,
        "phone"      => "收货人手机" ,
        "area_id"    => "区域ID" ,
        "area_text"  => "区域text" ,
        "address"    => "详细地址" ,
        "is_default" => "是否默认" ,
      ] ,
      'payChannel' => [
        'channel' => '支付渠道' ,
        'text'    => '支付渠道名称'
      ] ,
      'coupon'     => []
    ]
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new OrderprepareService();
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
    
    //检查商品是否正确
    $GoodsService = GoodsService::instance();
    $goods        = $GoodsService->transGoods( $this->params['items'] );
    if ( empty( $goods ) ) {
      return ajax_arr( '请填写商品' , 500 );
    }
    
    $result = $GoodsService->checkGoods( $goods );
    if ( $result['code'] != 0 ) {
      return $result;
    }
    
    if ( empty( $result['data']['rows'] ) ) {
      return ajax_arr( '商品未找到' , 500 );
    }
    
    $data = $this->getUser();
    
    $data['goods']      = $result['data']['rows'];
    $data['address']    = $this->getAddress();
    $data['payChannel'] = $this->getPayment();
    $data['coupon']     = $this->getCoupon();
    
    $data = $this->formatData( $data );
    
    return api_result( '查询成功' , 0 , $data );
    
  }
  
  private function getUser() {
    $MerUser = MerUserService::instance();
    $data    = $MerUser->getById( $this->userId );
    
    return [
      'points' => $data['points'] ,
      'bucks'  => $data['bucks'] ,
    ];
  }
  
  private function getAddress() {
    $Address = AddressService::instance();
    
    return $Address->get();
  }
  
  private function getPayment() {
    return [
      [
        'channel' => 'alipay' ,
        'text'    => '支付宝'
      ] ,
      [
        'channel' => 'wx' ,
        'text'    => '微信支付'
      ]
    ];
  }
  
  private function getCoupon() {
    return [];
  }
  
  
}
