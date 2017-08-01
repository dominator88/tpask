<?php
namespace apps\api\service\v1\user;

/**
 * 用户订单
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-13
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerOrderService;
use apps\api\service\v1\goods\IndexService as GoodsService;

class OrderService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get'    => 'GET - 用户订单详情' ,
    'post'   => 'POST - 生成订单(同步)' ,
    'put'    => 'PUT - 取消订单' ,
    'delete' => 'DELETE - 删除订单'
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get'    => [
      'token'   => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'orderId' => [ '订单ID' , '' , PARAM_REQUIRED ] ,
    ] ,
    'post'   => [
      'token'      => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      "type"       => [ '订单类型' , 'goods' , [ 'goods' => '商品' , 'virtual' => '虚拟商品' , 'service' => '服务' ] ] ,
      "currency"   => [ '货币类型' , 'cny' , [ 'cny' => '人民币' , 'points' => '积分' ] ] ,
      'payChannel' => [ '支付渠道' , 'alipay' , [ 'alipay' => '支付宝' , 'wx' => '微信' , 'points' => '积分' ] ] ,
      'addressId'  => [ '收货地址ID' , '' , PARAM_REQUIRED ] ,
      'bucks'      => [ '使用余额' , '' ] ,
      'couponId'   => [ '优惠券ID' , '' ] ,
      'items'      => [ '商品(JSON字符串), 如:[{"id":"1","qty":"1"}]' , [ "id" => '商品ID' , "qty" => '数量' ] , 'array' ]
    ] ,
    'put'    => [
      'token'   => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'orderId' => [ '订单ID' , '' , PARAM_REQUIRED ] ,
    ] ,
    'delete' => [
      'token'   => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'orderId' => [ '订单ID' , '' , PARAM_REQUIRED ] ,
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get'    => [
      "id"                => '订单ID' ,
      "user_id"           => "用户ID" ,
      "mer_id"            => "机构ID" ,
      "type"              => "订单类型" ,
      "order_no"          => "订单编号" ,
      "address_name"      => "收货人姓名" ,
      "address_phone"     => "收货人电话" ,
      "address_area_text" => "收货人区域" ,
      "address"           => "收货人地址" ,
      "currency"          => "货币类型" ,
      "bucks"             => "使用零钱" ,
      "amount"            => "订单金额" ,
      "pay_channel"       => "支付渠道" ,
      "pay_amount"        => "支付金额" ,
      "status"            => "订单状态" ,
      "get_points"        => "获取积分" ,
      "user_remark"       => "用户备注" ,
      "sys_remark"        => "系统留言" ,
      "created_at"        => "创建时间" ,
      "paid_at"           => "支付时间" ,
      "updated_at"        => "更新时间" ,
      "items"             => [
        "id"         => "item ID" ,
        "order_id"   => "订单ID" ,
        "goods_id"   => "商品ID" ,
        "goods_name" => "商品名称" ,
        "icon"       => [ '商品图标' , 'formatIcon' ] ,
        "currency"   => "货币类型" ,
        "amount"     => "商品金额" ,
        "qty"        => "商品数量" ,
        "get_points" => "可获取积分"
      ]
    ] ,
    'post'   => [
      "id"                => '订单ID' ,
      "user_id"           => "用户ID" ,
      "mer_id"            => "机构ID" ,
      "type"              => "订单类型" ,
      "order_no"          => "订单编号" ,
      "address_name"      => "收货人姓名" ,
      "address_phone"     => "收货人电话" ,
      "address_area_text" => "收货人区域" ,
      "address"           => "收货人地址" ,
      "currency"          => "货币类型" ,
      "bucks"             => "使用零钱" ,
      "amount"            => "订单金额" ,
      "pay_channel"       => "支付渠道" ,
      "pay_amount"        => "支付金额" ,
      "status"            => "订单状态" ,
      "get_points"        => "获取积分" ,
      "user_remark"       => "用户备注" ,
      "sys_remark"        => "系统留言" ,
      "created_at"        => "创建时间" ,
      "paid_at"           => "支付时间" ,
      "updated_at"        => "更新时间" ,
      "items"             => [
        "id"         => "item ID" ,
        "order_id"   => "订单ID" ,
        "goods_id"   => "商品ID" ,
        "goods_name" => "商品名称" ,
        "icon"       => [ '商品图标' , 'formatIcon' ] ,
        "currency"   => "货币类型" ,
        "amount"     => "商品金额" ,
        "qty"        => "商品数量" ,
        "get_points" => "可获取积分"
      ]
    ] ,
    'put'    => [] ,
    'delete' => []
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new OrderService();
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
        //用户查询订单
        $data = $this->get();
        
        return api_result( '查询成功' , 0 , $this->formatData( $data ) );
      case 'POST' :
        //创建订单
        $result = $this->post();
        if ( isset( $result['data'] ) ) {
          $result['data'] = $this->formatData( $result['data'] );
        }
        
        return $result;
      case 'PUT' :
        return $this->put();
      case 'DELETE' :
        return $this->delete();
      default:
        return ajax_arr( '未知请求' , 500 );
    }
  }
  
  /**
   * 取用户订单
   *
   * @return array
   */
  public function get() {
    $MerOrder = MerOrderService::instance();
    
    return $MerOrder->getByIdUser( $this->params['orderId'] , $this->userId );
  }
  
  
  /**
   * 创建订单
   *
   * @return array
   */
  public function post() {
    
    $data = [
      'merId'      => 1 ,
      'userId'     => $this->userId ,
      'type'       => $this->params['type'] ,
      'currency'   => $this->params['currency'] ,
      'payChannel' => $this->params['payChannel'] ,
      'addressId'  => $this->params['addressId'] ,
      'bucks'      => $this->params['bucks'] ,
      'couponId'   => $this->params['couponId'] ,
      'items'      => []
    ];
    
    //检查商品是否正确
    $GoodsService  = GoodsService::instance();
    $data['items'] = $GoodsService->transGoods( $this->params['items'] );
    if ( empty( $data['items'] ) ) {
      return ajax_arr( '请填写商品' , 500 );
    }
    
    $MerOrder = MerOrderService::instance();
    
    return $MerOrder->insert( $data );
  }
  
  /**
   * 取消订单
   *
   * @return array
   */
  public function put() {
    $MerOrder = MerOrderService::instance();
    
    return $MerOrder->statusFlowTo( $this->params['orderId'] , 0 );
  }
  
  /**
   * 用户删除订单
   *
   * @return array
   */
  public function delete() {
    $MerOrder = MerOrderService::instance();
    
    return $MerOrder->deleteByIdFromUser( $this->params['orderId'] , $this->userId );
  }
  
}
