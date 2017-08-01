<?php
namespace apps\api\service\v1\user;

/**
 * 用户订单列表
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-20
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerOrderService;

class OrdersService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get'  => 'GET - 取用户订单列表' ,
    //'post' => 'POST - 设置用户订单列表'
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'token'       => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'type'        => [ '订单类型' , 'goods' , [ 'goods' => '商品' , 'virtual' => '虚拟商品' , 'service' => '服务' ] ] ,
      'payChannel'  => [ '支付渠道' , 'alipay' , [ 'alipay' => '支付宝' , 'wx' => '微信' , 'points' => '积分' ] ] ,
      "currency"    => [ '货币类型' , 'cny' , [ 'cny' => '人民币' , 'points' => '积分' ] ] ,
      "multiStatus" => [ '状态, 用,分隔' , 10 ] ,
      'withItems'   => [ '是否查询订单商品' , '0' , [ '否' , '是' ] ] ,
      'page'        => [ '页码' , 1 , PARAM_REQUIRED ] ,
      'pageSize'    => [ '每页行数' , 6 , PARAM_REQUIRED ]
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
    ]
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new OrdersService();
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
        $data = $this->get();
        
        return api_result( '查询成功' , 0 , $data );
      default:
        return api_result( '未知请求类型' , 500 );
    }
  }
  
  /**
   * get 的响应方法
   *
   * @return array|number
   */
  public function get() {
    $MerOrder = MerOrderService::instance();
    if ( $this->params['multiStatus'] === 0 ) {
      $multiStatus = [ '0' ];
    } elseif ( empty( $this->params['multiStatus'] ) ) {
      $multiStatus = [ '10' ];
    } else {
      $multiStatus = explode( ',' , $this->params['multiStatus'] );
    }
    
    $params = [
      'userId'      => $this->userId ,
      'type'        => $this->params['type'] ,
      'currency'    => $this->params['currency'] ,
      'payChannel'  => $this->params['payChannel'] ,
      'multiStatus' => $multiStatus ,
      'page'        => $this->params['page'] ,
      'pageSize'    => $this->params['pageSize'] ,
      'withItems'   => $this->params['withItems'] ,
      'withDelete'  => FALSE
    ];
    
    $data            = $MerOrder->getByCond( $params );
    $params['count'] = TRUE;
    $data            = $this->formatData( $data );
    
    return [
      'rows'  => $data ,
      'total' => $MerOrder->getByCond( $params )
    ];
  }
  
}
