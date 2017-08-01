<?php namespace apps\common\service;

/**
 * MerPay Service
 *
 * @author  Zix
 * @version 2.0 2016-10-14
 */

use Pingpp\Pingpp;
use Pingpp\Charge;

class MerPayService extends BaseService {
  
  //引入 GridTable trait
  use \apps\common\traits\service\GridTable;
  
  var $payChannel = [
    'alipay' => '支付宝' ,
    'wx'     => '微信支付' ,
    'points' => '积分兑换' ,
  ];
  
  var $payChannelByCurrency = [
    'cny'    => [
      'alipay' => '支付宝' ,
      'wx'     => '微信支付' ,
    ] ,
    'points' => [
      'points' => '积分兑换' ,
    ]
  ];
  
  //状态
  var $status = [
    - 1 => '过期' ,
    0   => '未支付' ,
    1   => '已支付' ,
  ];
  
  //类实例
  private static $instance;
  
  //生成类单例
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance        = new MerPayService();
      self::$instance->model = db( 'MerPay' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'                => '' ,
      'type'              => 'order' ,
      'type_id'           => '' ,
      'mer_id'            => '' ,
      'user_id'           => '' ,
      'pay_channel'       => 'alipay' ,
      'currency'          => 'cny' ,
      'pay_amount'        => '0.00' ,
      'charge_id'         => '' ,
      'status'            => '0' ,
      'created_at'        => date( 'Y-m-d H:i:s' ) ,
      'charge_created_at' => '' ,
      'paid_at'           => '' ,
    ];
  }
  
  /**
   * 根据条件查询
   *
   * @param $param
   *
   * @return array|number
   */
  public function getByCond( $param ) {
    $default = [
      'field'    => [] ,
      'keyword'  => '' ,
      'status'   => '' ,
      'page'     => 1 ,
      'pageSize' => 10 ,
      'sort'     => 'id' ,
      'order'    => 'DESC' ,
      'count'    => FALSE ,
      'getAll'   => FALSE
    ];
    
    $param = extend( $default , $param );
    
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'name' , 'like' , "%{$param['keyword']}%" );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'status' , $param['status'] );
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    $this->model->field( $param['field'] );
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  /**
   * 创建订单支付
   *
   * @param $merId
   * @param $userId
   * @param $orderId
   * @param $currency
   * @param $payAmount
   * @param $payChannel
   *
   * @return array
   */
  public function insertByOrder( $merId , $userId , $orderId , $currency , $payAmount , $payChannel ) {
    
    $data = [
      'mer_id'      => $merId ,
      'user_id'     => $userId ,
      'type'        => 'order' ,
      'type_id'     => $orderId ,
      'currency'    => $currency ,
      'pay_amount'  => $payAmount ,
      'pay_channel' => $payChannel
    ];
    
    return $this->insert( $data );
  }
  
  /**
   * 根据用户和订单获取支付
   *
   * @param $merId
   * @param $userId
   * @param $orderId
   *
   * @return array
   */
  function getByUserWithOrder( $merId , $userId , $orderId ) {
    $data = $this->model
      ->field( [ 'p.* , o.order_no' ] )
      ->alias( 'p' )
      ->join( 'mer_order o on o.id = p.type_id' , 'left' )
      ->where( 'p.type' , 'order' )
      ->where( 'p.mer_id' , $merId )
      ->where( 'p.user_id' , $userId )
      ->where( 'p.type_id' , $orderId )
      ->find();
    
    return $data ? $data : [];
  }
  
  /**
   * 获取pingPP支付 charge
   *
   * @param $merId
   * @param $userId
   * @param $orderId
   *
   * @return array
   */
  function getOrderCharge( $merId , $userId , $orderId ) {
    //取订单
    $data = $this->getByUserWithOrder( $merId , $userId , $orderId );
    
    if ( empty( $data ) ) {
      return ajax_arr( '订单未找到' , 500 );
    }
    
    if ( ! empty( $data['charge_id'] ) ) {
      $charge = $this->getChargeById( $data['charge_id'] );
    } else {
      $charge = $this->createCharge( $data );
    }
    
    $chargeData = [ 'charge' => json_decode( $charge , TRUE ) ];
    if ( empty( $chargeData['charge'] ) ) {
      return ajax_arr( '获取charge失败' , 500 );
    }
    
    if ( empty( $data['charge_id'] ) ) {
      $this->model
        ->where( 'id' , $data['id'] )
        ->update( [
          'charge_id'         => $chargeData['charge']['id'] ,
          'charge_created_at' => date( 'Y-m-d H:i:s' )
        ] );
    }
    
    return ajax_arr( '获取charge成功' , 0 , $chargeData );
  }
  
  /**
   * 创建 PingPP charge
   *
   * @param $payData
   *
   * @return Charge
   */
  private function createCharge( $payData ) {
    $PingPPConfig = config( 'custom.PingPP' );
    //初始化PingPP
    Pingpp::setApiKey( $PingPPConfig['secret'] );
    
    
    $send_data = [
      'order_no'  => $payData['order_no'] ,
      'amount'    => $payData['pay_amount'] * 100 ,
      'app'       => [
        'id' => $PingPPConfig['appId'] ,
      ] ,
      'channel'   => $payData['pay_channel'] ,
      'currency'  => $payData['currency'] ,
      'client_ip' => request()->ip( 0 , TRUE ) ,
      'subject'   => '商品订单' ,
      'body'      => '订单支付，单号：' . $payData['order_no'] ,
    ];
    
    return Charge::create( $send_data );
  }
  
  /**
   * 根据chargeID 获取 PingPP charge
   *
   * @param $chargeId
   *
   * @return Charge
   */
  private function getChargeById( $chargeId ) {
    $PingPPConfig = config( 'custom.PingPP' );
    Pingpp::setApiKey( $PingPPConfig['secret'] );
    
    return Charge::retrieve( $chargeId );
  }
  
}