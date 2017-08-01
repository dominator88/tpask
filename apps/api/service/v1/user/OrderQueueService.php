<?php
namespace apps\api\service\v1\user;

/**
 * 生成订单队列
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-18
 */

use think\queue\Queue;
use apps\api\service\v1\ApiService;
use apps\api\service\v1\goods\IndexService as GoodsService;

class OrderQueueService extends ApiService {
  
  public $ticketExpire = 3600;
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get'  => 'GET - 查询订单队列处理结果' ,
    'post' => 'POST - 提交订单到队列'
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get'  => [
      'token'  => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'ticket' => [ '查询票据' , '' , PARAM_REQUIRED ]
    ] ,
    'post' => [
      'token'      => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      "type"       => [ '订单类型' , 'goods' , [ 'goods' => '商品' , 'virtual' => '虚拟商品' , 'service' => '服务' ] ] ,
      "currency"   => [ '货币类型' , 'cny' , [ 'cny' => '人民币' , 'points' => '积分' ] ] ,
      'payChannel' => [ '支付渠道' , 'alipay' , [ 'alipay' => '支付宝' , 'wx' => '微信' , 'points' => '积分' ] ] ,
      'addressId'  => [ '收货地址ID' , '' , PARAM_REQUIRED ] ,
      'bucks'      => [ '使用余额' , '' ] ,
      'couponId'   => [ '优惠券ID' , '' ] ,
      'items'      => [ '商品(JSON字符串), 如:[{"id":"1","qty":"1"}]' , [ "id" => '商品ID' , "qty" => '数量' ] , 'array' ]
    ]
  
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get'  => [
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
    'post' => [
      "ticket" => '查询票据'
    ]
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new OrderQueueService();
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
    if ( request()->method() == 'GET' ) {
      return $data = $this->get();
    }
    
    return $this->post();
  }
  
  /**
   * get 的响应方法
   *
   * @return array|number
   */
  public function get() {
    $ticket = $this->params['ticket'];
    
    $result = cache( config( 'ticketPrefix' ) . $ticket );
    if ( empty( $result ) ) {
      return api_result( 'ticket 不正确或已过期' , 500 );
    }
    
    //处理data
    if ( isset( $result['data'] ) ) {
      $result['data'] = $this->formatData( $result['data'] );
    }
    
    return $result;
    
  }
  
  /**
   * post 的响应方法
   *
   * @return array
   */
  public function post() {
    
    $ticket = $this->getTicket();
    $data   = [
      'merId'      => 1 ,
      'userId'     => $this->userId ,
      'type'       => $this->params['type'] ,
      'currency'   => $this->params['currency'] ,
      'payChannel' => $this->params['payChannel'] ,
      'addressId'  => $this->params['addressId'] ,
      'bucks'      => $this->params['bucks'] ,
      'couponId'   => $this->params['couponId'] ,
      'ticket'     => $ticket ,
      'items'      => []
    ];
    
    //检查商品是否正确
    $GoodsService  = GoodsService::instance();
    $data['items'] = $GoodsService->transGoods( $this->params['items'] );
    
    
    if ( empty( $data['items'] ) ) {
      return ajax_arr( '请填写商品' , 500 );
    }
    
    try {
      Queue::push( 'api/OrderTask' , $data );
      
      $cacheName = config( 'ticketPrefix' ) . $ticket;
      $result    = api_result( '订单正在处理中' , 250 );
      
      cache( $cacheName , $result , config( 'ticketExpire' ) );
      
      return api_result( '订单加入队列成功' , 0 , [ 'ticket' => $ticket ] );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 获取查询票据
   *
   * @return string
   */
  private function getTicket() {
    return md5( 'order' . $this->params['token'] . time() . rand_string( 6 ) );
  }
  
}
