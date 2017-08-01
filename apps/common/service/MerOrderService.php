<?php namespace apps\common\service;

/**
 * MerOrder Service
 *
 * @author  Zix
 * @version 2.0 2016-09-18
 */

use think\Db;

class MerOrderService extends BaseService {
  
  //引入 GridTable trait
  use \apps\common\traits\service\GridTable;
  
  var $type = [
    'goods'   => '实物商品' ,
    'virtual' => '虚拟商品' ,
    'service' => '服务'
  ];
  
  var $currency = [
    'cny'   => '人民币' ,
    'point' => '积分'
  ];
  
  const CANCEL = 0;
  
  const CREATED = 10;
  
  const PAID         = 20;
  const REFUND_APPLY = 21;
  const REFUNDING    = 22;
  const REFUNDED     = 29;
  
  const DELIVERED    = 30;
  const RETURN_APPLY = 31;
  const RETURNING    = 32;
  const RETURNED     = 39;
  
  const DONE = 99;
  
  //状态
  public $status = [
    0 => '已取消' ,
    
    10 => '待支付' ,
    
    20 => '待发货' ,
    21 => '申请退款' ,
    22 => '退款中' ,
    29 => '已退款' ,
    
    30 => '已发货' ,
    31 => '申请退货' ,
    32 => '退货中' ,
    39 => '已退货' ,
    
    99 => '已完成' ,
  ];
  
  //订单流转
  var $flow = [
    '0' => [ 'text' => '已取消' , 'method' => 'Cancel' , 'flow' => [ 10 ] , ] ,
    
    '10' => [ 'text' => '待支付' , 'method' => 'Created' , 'flow' => [ 0 , 20 ] , ] ,
    
    '20' => [ 'text' => '待发货' , 'method' => 'Paid' , 'flow' => [ 29 , 30 ] , ] ,
    '21' => [ 'text' => '申请退款' , 'method' => 'RefundApply' , 'flow' => [ 22 ] , ] ,
    '22' => [ 'text' => '退款中' , 'method' => 'Refunding' , 'flow' => [ 29 ] , ] ,
    '29' => [ 'text' => '已退款' , 'method' => 'Refunded' , 'flow' => [] , ] ,
    
    '30' => [ 'text' => '已发货' , 'method' => 'Delivered' , 'flow' => [ 31 , 99 ] , ] ,
    '31' => [ 'text' => '申请退货' , 'method' => 'ReturnApply' , 'flow' => [ 32 ] , ] ,
    '32' => [ 'text' => '退货中' , 'method' => 'Returning' , 'flow' => [ 39 ] , ] ,
    '39' => [ 'text' => '已退货' , 'method' => 'Returned' , 'flow' => [] , ] ,
    
    '99' => [ 'text' => '已完成' , 'method' => 'Done' , 'flow' => [ 31 ] , ] ,
  ];
  
  const ORDER_NO_CACHE  = 'DMGAppOrderNo';
  const ORDER_NO_PREFIX = 'DMGApp';
  
  //类实例
  private static $instance;
  
  //生成类单例
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance        = new MerOrderService();
      self::$instance->model = db( 'MerOrder' );
    }
    
    return self::$instance;
  }
  
  /**
   * 根据ID 和用户获取订单详情
   *
   * @param $id
   * @param $userId
   *
   * @return array
   */
  public function getByIdUser( $id , $userId ) {
    $data = $this->model
      ->where( 'user_id' , $userId )
      ->where( 'id' , $id )
      ->find();
    
    if ( ! $data ) {
      return [];
    }
    
    $MerOrderItems = MerOrderItemsService::instance();
    $data['items'] = $MerOrderItems->getByCond( [
      'orderId' => $id
    ] );
    
    return $data;
  }
  
  //取默认值
  public function getDefaultRow() {
    return [
      'id'                => '' ,
      'user_id'           => '' ,
      'mer_id'            => '' ,
      'type'              => 'goods' ,
      'order_no'          => '' ,
      'address_id'        => '' ,
      'address_name'      => '' ,
      'address_phone'     => '' ,
      'address_area_text' => '' ,
      'address'           => '' ,
      'address_postcode'  => '' ,
      'event_id'          => '' ,
      'event_amount'      => '0' ,
      'coupon_id'         => '' ,
      'coupon_amount'     => '0.00' ,
      'bucks'             => '0.00' ,
      'currency'          => 'cny' ,
      'amount'            => '0.00' ,
      'pay_channel'       => 'alipay' ,
      'pay_amount'        => '0.00' ,
      'status'            => '' ,
      'get_points'        => '0' ,
      'pay_id'            => '' ,
      'user_remark'       => '' ,
      'sys_remark'        => '' ,
      'create_time'       => date( 'Y-m-d H:i:s' ) ,
      'pay_time'          => '' ,
      'update_time'       => '' ,
    ];
  }
  
  /**
   * 根据条件查询
   *
   * @param $param
   *
   * @return array|number
   */
  function getByCond( $param ) {
    $default = [
      'field'       => [ 'o.*' ] ,
      'keyword'     => '' ,
      'status'      => '' ,
      'multiStatus' => [] ,
      'merId'       => '' ,
      'userId'      => '' ,
      'type'        => '' ,
      'currency'    => '' ,
      'payChannel'  => '' ,
      'startAt'     => '' ,
      'endAt'       => '' ,
      'page'        => 1 ,
      'pageSize'    => 10 ,
      'sort'        => 'id' ,
      'order'       => 'DESC' ,
      'withDelete'  => TRUE ,
      'count'       => FALSE ,
      'getAll'      => FALSE ,
      'withUser'    => FALSE ,
      'withEvent'   => FALSE ,
      'withCoupon'  => FALSE ,
      'withItems'   => FALSE ,
      'withPay'     => FALSE ,
    ];
    
    $param = extend( $default , $param );
    
    $this->model->alias( 'o' );
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'o.order_no|o.address_name|o.address_phone|u.nickname|u.phone' ,
        'like' , "%{$param['keyword']}%" );
    }
    
    if ( $param['merId'] !== '' ) {
      $this->model->where( 'o.mer_id' , $param['merId'] );
    }
    
    if ( $param['userId'] !== '' ) {
      $this->model->where( 'o.user_id' , $param['userId'] );
    }
    
    if ( $param['type'] !== '' ) {
      $this->model->where( 'o.type' , $param['type'] );
    }
    
    if ( $param['currency'] !== '' ) {
      $this->model->where( 'o.currency' , $param['currency'] );
    }
    
    if ( $param['payChannel'] !== '' ) {
      $this->model->where( 'o.pay_channel' , $param['payChannel'] );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'o.status' , $param['status'] );
    }
    if ( ! $param['withDelete'] ) {
      $this->model->where( 'o.is_delete' , 0 );
    }
    
    if ( ! empty( $param['multiStatus'] ) ) {
      $this->model->where( 'o.status' , 'in' , $param['multiStatus'] );
    }
    
    if ( $param['startAt'] !== '' && $param['endAt'] !== '' ) {
      $this->model->whereTime( 'o.created_at' , 'between' , [ $param['startAt'] , $param['endAt'] ] );
    } else {
      if ( $param['startAt'] !== '' && empty( $param['endAt'] ) ) {
        $this->model->whereTime( 'o.created_at' , '>=' , $param['startAt'] );
      } else if ( $param['endAt'] !== '' && empty( $param['startAt'] ) ) {
        $this->model->whereTime( 'o.created_at' , '<' , $param['endAt'] );
      }
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    //关联用户
    if ( $param['withUser'] ) {
      $this->model->join( 'mer_user u' , 'u.id = o.user_id' , 'left' );
      $param['field'] = array_merge( $param['field'] , [ 'u.nickname' , 'u.phone' ] );
    }
    
    //关联支付
    if ( $param['withPay'] ) {
      $this->model->join( 'mer_pay p' , 'p.type_id = o.id and p.type="order"' , 'left' );
      $param['field'] = array_merge( $param['field'] , [ 'p.charge_id' , 'p.paid_at' ] );
    }
    
    $this->model->field( $param['field'] );
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    if ( $param['withItems'] ) {
      $data = $this->withItems( $data );
    }

//    echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  
  //配合 getByCond 取订单items
  private function withItems( $data ) {
    if ( empty( $data ) ) {
      return [];
    }
    
    $orderIds = [];
    foreach ( $data as $item ) {
      $orderIds[] = $item['id'];
    }
    
    $MerOrderItems = MerOrderItemsService::instance();
    $orderItem     = $MerOrderItems->getByCond( [
      'orderIds' => $orderIds ,
      'getAll'   => TRUE
    ] );
    
    $newOrderItems = [];
    foreach ( $orderItem as $item ) {
      $newOrderItems[ $item['order_id'] ][] = $item;
    }
    
    foreach ( $data as &$item ) {
      $item['items'] = $newOrderItems[ $item['id'] ];
    }
    
    return $data;
  }
  
  public function getById( $id , $withItem = FALSE ) {
    $data = $this->model->find( $id );
    if ( $withItem ) {
      $MerOrderItems = MerOrderItemsService::instance();
      $data['items'] = $MerOrderItems->getByCond( [
        'orderId' => $id
      ] );
    }
    
    return $data ? $data : [];
  }
  
  /**
   * 创建订单
   *
   * @param $data
   *
   * @return array
   */
  public function insert( $data ) {
    //检查商户
    $SysMerchant = SysMerchantService::instance();
    $merData     = $SysMerchant->getById( $data['merId'] );
    
    if ( empty( $merData ) || $merData['status'] != 1 ) {
      return ajax_arr( '机构不存在或状态不正确' , 500 );
    }
    
    //检查用户
    $MerUser  = MerUserService::instance();
    $userData = $MerUser->getById( $data['userId'] );
    if ( empty( $userData ) || $userData['status'] != 1 ) {
      return ajax_arr( '用户不存在或状态不正确' , 500 );
    }
    
    //检查收货地址
    $MerUserAddress = MerUserAddressService::instance();
    $addressData    = $MerUserAddress->getByIdWithUser( $data['addressId'] , $data['userId'] );
    if ( empty( $addressData ) || $addressData['status'] != 1 ) {
      return ajax_arr( '用户收货地址不正确' , 500 );
    }
    
    //检查商品
    $MerGoods = MerGoodsService::instance();
    $goodsIds = array_keys( $data['items'] );
    
    $goodsData = $MerGoods->getByIds( $goodsIds , $data['merId'] );
    if ( empty( $goodsData ) || count( $goodsData ) != count( $data['items'] ) ) {
      return ajax_arr( '商品未找到或已经下架' , 500 );
    }
    
    Db::startTrans();
    try {
      //TODO 检查活动 取活动抵扣金额
      $eventAmount = 0;
      
      //TODO 检查优惠券 取优惠券抵扣金额
      $couponAmount = 0;
      
      //订单生产后, 扣除余额时检查
      $bucks = empty( $data['bucks'] ) ? 0 : $data['bucks'];
      
      $amount    = 0; //订单金额
      $getPoints = 0; //可获取积分
      foreach ( $goodsData as $item ) {
        if ( $item['currency'] != $data['currency'] ) {
          return ajax_arr( '商品货币类型不正确' , 500 );
        }
        $amount += $item['price'] * $data['items'][ $item['id'] ];
        $getPoints += $item['points'] * $data['items'][ $item['id'] ];
      }
      
      //生成订单号
      $orderMeta = [
        'mer_id'            => $data['merId'] ,
        'type'              => $data['type'] ,
        'order_no'          => $this->getOrderNo() ,
        'user_id'           => $data['userId'] ,
        'address_id'        => $data['addressId'] ,
        'address_name'      => $addressData['name'] ,
        'address_phone'     => $addressData['phone'] ,
        'address_area_text' => $addressData['area_text'] ,
        'address'           => $addressData['address'] ,
        'address_postcode'  => $addressData['postcode'] ,
        'bucks'             => $bucks ,
        'currency'          => $data['currency'] ,
        'amount'            => $amount ,
        'pay_amount'        => $amount - $eventAmount - $couponAmount - $bucks ,
        'pay_channel'       => $data['payChannel'] ,
        'get_points'        => $getPoints ,
        'status'            => 10
      ];
      
      //创建订单
      $orderId = $this->model->insertGetId( $orderMeta );
      if ( $orderId < 1 ) {
        throw new \Exception( '添加订单失败' );
      }
      
      //创建订单商品
      $itemData = [];
      foreach ( $goodsData as $item ) {
        $itemData[] = [
          'order_id'   => $orderId ,
          'goods_id'   => $item['id'] ,
          'goods_name' => $item['name'] ,
          'icon'       => $item['icon'] ,
          'currency'   => $item['currency'] ,
          'amount'     => $item['price'] ,
          'qty'        => $data['items'][ $item['id'] ] ,
          'get_points' => $item['points']
        ];
      }
      
      $itemRows = db( 'mer_order_items' )->insertAll( $itemData );
      if ( $itemRows < 0 ) {
        throw new \Exception( '添加订单商品失败' );
      }
      
      //扣用户零钱
      if ( $bucks > 0 ) {
        $MerUser     = MerUserService::instance();
        $resultBucks = $MerUser->payByOrder( $orderMeta['user_id'] , $orderMeta['bucks'] , $orderMeta['order_no'] );
        
        if ( $resultBucks['code'] != 0 ) {
          throw new \Exception( $resultBucks['msg'] );
        }
      }
      
      //检查支付金额是否为0
      if ( $orderMeta['pay_amount'] <= 0 ) {
        //如果不需要支付 , 更新订单支付信息
        $this->model->where( 'id' , $orderId )->update( [
          'pay_amount' => 0 ,
          'status'     => '20'
        ] );
      } else {
        //如果需要支付 创建支付
        $MerPay    = MerPayService::instance();
        $payResult = $MerPay->insertByOrder(
          $orderMeta['mer_id'] ,
          $orderMeta['user_id'] ,
          $orderId ,
          $orderMeta['currency'] ,
          $orderMeta['pay_amount'] ,
          $orderMeta['pay_channel']
        );
        if ( $payResult['code'] != 0 ) {
          throw new \Exception( $payResult['msg'] );
        }
        
        //更新订单支付信息
        $this->model->where( 'id' , $orderId )->update( [
          'pay_id' => $payResult['data']['id']
        ] );
      }
      
      Db::commit();
      
      //查询订单并返回
      $result = $this->getById( $orderId , TRUE );
      
      return ajax_arr( '创建订单成功' , 0 , $result );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  //生成订单编号
  function getOrderNo() {
    $date       = date( 'ymd' );
    $oldOrderNo = cache( self::ORDER_NO_CACHE );
    if ( empty( $oldOrderNo ) ) {
      //没有 创建新订单号
      $orderNo = self::ORDER_NO_PREFIX . $date . str_pad( mt_rand( 1 , 20 ) , 6 , '0' , STR_PAD_LEFT );
    } else {
      //分析订单号
      $oldDate = substr( $oldOrderNo , strlen( self::ORDER_NO_PREFIX ) , 6 );
      if ( $oldDate == $date ) {
        $oldNo   = substr( $oldOrderNo , strlen( self::ORDER_NO_PREFIX ) + 6 ) + mt_rand( 1 , 20 );
        $orderNo = self::ORDER_NO_PREFIX . $date . str_pad( $oldNo , 6 , '0' , STR_PAD_LEFT );
      } else {
        $orderNo = self::ORDER_NO_PREFIX . $date . str_pad( mt_rand( 1 , 20 ) , 6 , '0' , STR_PAD_LEFT );
      }
    }
    
    cache( self::ORDER_NO_CACHE , $orderNo );
    
    return $orderNo;
  }
  
  /**
   * 写订单日志
   *
   * @param $id
   * @param $data
   * @param string $sysUserId
   *
   * @return array
   */
  public function updateAndSaveLogs( $id , $data , $sysUserId = '' ) {
    $ret = $this->update( $id , $data );
    
    if ( $ret['code'] == 0 ) {
      //保存订单log;
      $logData = [
        'order_id' => $id ,
        'remark'   => "修改了系统备注" ,
      ];
      
      if ( ! empty( $sysUserId ) ) {
        $logData['sys_user_id'] = $sysUserId;
      }
      $MerOrderLog = MerOrderLogService::instance();
      $MerOrderLog->insert( $logData );
    }
    
    return $ret;
  }
  
  //订单流转
  public function statusFlowTo( $orderId , $flowStatus , $sysUserId = '' ) {
    
    $data = $this->getById( $orderId );
    
    Db::startTrans();
    try {
      if ( empty( $data ) ) {
        throw new \Exception( '订单未找到' );
      }
      $canFlowArr = $this->flow[ $data['status'] ]['flow'];
      if ( ! in_array( $flowStatus , $canFlowArr ) ) {
        throw new \Exception( '订单状态不能改为 ' . $this->status[ $flowStatus ] );
      }
      
      $method = 'flow' . $this->flow[ $flowStatus ]['method'];
      
      if ( ! method_exists( $this , $method ) ) {
        throw new \Exception( $method . ' 方法未找到' );
      }
      
      $ret = $this->$method( $data );
      if ( $ret['code'] != 0 ) {
        throw new \Exception( $ret['msg'] );
      }
      
      //写订单log
      $logData = [
        'order_id' => $orderId ,
        'remark'   => "订单状态[{$this->status[ $data['status'] ]}] -> [{$this->status[ $flowStatus ]}]" ,
      ];
      if ( ! empty( $sysUserId ) ) {
        $logData['sys_user_id'] = $sysUserId;
      }
      $MerOrderLog = MerOrderLogService::instance();
      $MerOrderLog->insert( $logData );
      
      Db::commit();
      
      return $ret;
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  function changeStatus( $id , $status ) {
    return $this->model->where( 'id' , $id )->update( [
      'status' => $status
    ] );
  }
  
  //订单取消
  private function flowCancel( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::CANCEL );
    if ( $ret === FALSE ) {
      return ajax_arr( '取消订单失败' , 500 );
    }
    
    return ajax_arr( '取消订单成功' , 0 );
  }
  
  //已下单(不会使用)
  private function flowCreated( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::CREATED );
    if ( $ret === FALSE ) {
      return ajax_arr( '取消订单失败' , 500 );
    }
    
    return ajax_arr( '取消订单成功' , 0 );
  }
  
  //已支付
  private function flowPaid( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::PAID );
    if ( $ret === FALSE ) {
      return ajax_arr( '订单支付失败' , 500 );
    }
    
    return ajax_arr( '订单支付成功' , 0 );
  }
  
  //申请退款
  private function flowRefundApply( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::REFUND_APPLY );
    if ( $ret === FALSE ) {
      return ajax_arr( '申请退款失败' , 500 );
    }
    
    return ajax_arr( '申请退款成功' , 0 );
  }
  
  //退款中
  private function flowRefunding( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::REFUNDING );
    if ( $ret === FALSE ) {
      return ajax_arr( '设置退款中失败' , 500 );
    }
    
    //TODO 消息推送
    return ajax_arr( '设置退款中成功' , 0 );
  }
  
  //已退款
  private function flowRefunded( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::REFUNDED );
    if ( $ret === FALSE ) {
      return ajax_arr( '退款失败' , 500 );
    }
    
    //TODO 将资金 + 使用的零钱 退往 用户零钱 或 用户支付渠道
    //此处为退换到 用户零钱
    $refundAmount = $orderData['pay_amount'] + $orderData['bucks'];
    if ( $refundAmount <= 0 ) {
      return ajax_arr( '无需退款' , 0 );
    }
    
    //开始退款
    $MerUser    = MerUserService::instance();
    $refund_ret = $MerUser->refundByOrder( $orderData['user_id'] , $refundAmount , $orderData['order_no'] );
    if ( $refund_ret['code'] != 0 ) {
      return $refund_ret;
    }
    
    //TODO 消息推送
//		$JPush = JPushService::instance();
//		$JPush->send( [
//			'catalog' => 'order',
//			'title'   => '订单通知',
//			'content' => "订单[{$orderData['order_no']}]退款成功",
//			'alias'   => [ $orderData['user_id'] ],
//			'param'   => [
//				'id' => $orderData['id']
//			]
//		] );
    return ajax_arr( '退款成功' , 0 );
  }
  
  //已发货
  private function flowDelivered( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::DELIVERED );
    if ( $ret === FALSE ) {
      return ajax_arr( '设置订单已发货失败' , 500 );
    }
    
    //TODO 消息推送
//		$JPush = JPushService::instance();
//		$JPush->send( [
//			'catalog' => 'order',
//			'title'   => '订单通知',
//			'content' => "订单[{$orderData['order_no']}]已发货",
//			'alias'   => [ $orderData['user_id'] ],
//			'param'   => [
//				'id' => $orderData['id']
//			]
//		] );
    return ajax_arr( '设置订单发货成功' , 0 );
  }
  
  //申请退货
  private function flowReturnApply( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::RETURN_APPLY );
    if ( $ret === FALSE ) {
      return ajax_arr( '申请退货失败' , 500 );
    }
    
    return ajax_arr( '申请退货成功' , 0 );
  }
  
  //退货中
  private function flowReturning( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::RETURNING );
    if ( $ret === FALSE ) {
      return ajax_arr( '设置退货中失败' , 500 );
    }
    
    //TODO 消息推送
//		$JPush = JPushService::instance();
//		$JPush->send( [
//			'catalog' => 'order',
//			'title'   => '订单通知',
//			'content' => "订单[{$orderData['order_no']}]退货中",
//			'alias'   => [ $orderData['user_id'] ],
//			'param'   => [
//				'id' => $orderData['id']
//			]
//		] );
    return ajax_arr( '设置退货中成功' , 0 );
  }
  
  //已退货
  private function flowReturned( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::RETURNED );
    if ( $ret === FALSE ) {
      return ajax_arr( '退货失败' , 500 );
    }
    
    //TODO 将资金 + 使用的零钱 退往 用户零钱 或 用户支付渠道
    //此处为退换到 用户零钱
    $refundAmount = $orderData['pay_amount'] + $orderData['bucks'];
    if ( $refundAmount <= 0 ) {
      return ajax_arr( '无需退款' , 0 );
    }
    
    $MerUser      = MerUserService::instance();
    $refundResult = $MerUser->refundByOrder( $orderData['user_id'] , $refundAmount , $orderData['order_no'] );
    if ( $refundResult['code'] != 0 ) {
      return $refundResult;
    }
    
    //TODO 消息推送
//		$JPush = JPushService::instance();
//		$JPush->send([
//			'catalog' => 'order' ,
//			'title' => '订单通知',
//			'content' => "订单[{$orderData['order_no']}]已退货退款成功",
//			'alias' => [ $orderData['user_id'] ],
//			'param' => [
//				'id' => $orderData['id']
//			]
//		]);
    
    return ajax_arr( '退货成功' , 0 );
  }
  
  //已完成
  private function flowDone( $orderData ) {
    $ret = $this->changeStatus( $orderData['id'] , self::DONE );
    if ( $ret === FALSE ) {
      return ajax_arr( '设置订单完成失败' , 500 );
    }
    
    //TODO 消息推送
//		$JPush = JPushService::instance();
//		$JPush->send( [
//			'catalog' => 'order',
//			'title'   => '订单通知',
//			'content' => "订单[{$orderData['order_no']}]已完成",
//			'alias'   => [ $orderData['user_id'] ],
//			'param'   => [
//				'id' => $orderData['id']
//			]
//		] );
    return ajax_arr( '订单已成功' , 0 );
  }
  
  /**
   * 用户删除订单
   *
   * @param $id
   * @param $userId
   *
   * @return array
   */
  public function deleteByIdFromUser( $id , $userId ) {
    try {
      $rows = $this->model
        ->where( 'id' , $id )
        ->where( 'is_delete' , 0 )
        ->where( 'status' , 0 )
        ->where( 'user_id' , $userId )
        ->update( [
          'is_delete' => 1
        ] );
      
      if ( $rows <= 0 ) {
        throw new \Exception( '删除订单失败' );
      }
      
      return ajax_arr( '删除订单成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
    
    
  }
  
}