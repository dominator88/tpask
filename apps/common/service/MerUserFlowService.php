<?php namespace apps\common\service;
/**
 * MerUserFlow Service
 *
 * @author  Zix
 * @version 2.0 2016-10-14
 */


class MerUserFlowService extends BaseService {
  
  //引入 GridTable trait
  use \apps\common\traits\service\GridTable;
  
  var $prefixSerialNo = [
    'bucks'  => 'B' ,
    'points' => 'P' ,
  ];
  
  var $serialNoLen = 6;
  
  
  var $type = [
    'bucks'  => '零钱' ,
    'points' => '积分' ,
  ];
  
  //状态
  public $status = [
    0 => '禁用' ,
    1 => '启用' ,
  ];
  
  //类实例
  private static $instance;
  
  //生成类单例
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance        = new MerUserFlowService();
      self::$instance->model = db( 'MerUserFlow' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'             => '' ,
      'serial_no'      => '' ,
      'user_id'        => '' ,
      'type'           => 'bucks' ,
      'before_balance' => '0.00' ,
      'amount'         => '0.00' ,
      'after_balance'  => '0.00' ,
      'remark'         => '' ,
      'created_at'     => date( 'Y-m-d H:i:s' ) ,
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
   * 写订单支付流水
   *
   * @param $userId
   * @param $oldBalance
   * @param $amount
   * @param $orderNo
   *
   * @return array
   */
  function payBucksByOrder( $userId , $oldBalance , $amount , $orderNo ) {
    $data = [
      'serial_no'      => $this->_getSerialNo( 'bucks' ) ,
      'type'           => 'bucks' ,
      'user_id'        => $userId ,
      'before_balance' => $oldBalance ,
      'amount'         => $amount ,
      'after_balance'  => $oldBalance - $amount ,
      'remark'         => "支付订单[$orderNo]"
    ];
    
    try {
      $this->model->insert( $data );
      
      return ajax_arr( '添加用户流水成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( '添加用户流水失败' , 500 );
    }
  }
  
  /**
   * 写订单退款流水
   *
   * @param $userId
   * @param $oldBalance
   * @param $amount
   * @param $orderNo
   *
   * @return array
   */
  function refundBucksByOrder( $userId , $oldBalance , $amount , $orderNo ) {
    $data = [
      'serial_no'      => $this->_getSerialNo( 'bucks' ) ,
      'type'           => 'bucks' ,
      'user_id'        => $userId ,
      'before_balance' => $oldBalance ,
      'amount'         => $amount ,
      'after_balance'  => $oldBalance + $amount ,
      'remark'         => "订单[$orderNo]退款"
    ];
    
    try {
      $this->model->insert( $data );
      
      return ajax_arr( '添加用户流水成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( '添加用户流水失败' , 500 );
    }
  }
  
  /**
   * 获取序列号
   *
   * @param $type
   *
   * @return string
   */
  function _getSerialNo( $type ) {
    $prefixSerialNo = $this->prefixSerialNo[ $type ] . date( 'Ymd' );
    
    $lastSerialNo = $this->model
      ->where( 'type' , $type )
      ->where( 'serial_no' , 'like' , $prefixSerialNo . '%d' )
      ->order( 'serial_no DESC' )
      ->limit( 1 )
      ->value( 'serial_no' );
    
    if ( empty( $lastSerialNo ) ) {
      $serialNo = $prefixSerialNo . str_pad( 1 , $this->serialNoLen , '0' , STR_PAD_LEFT );
    } else {
      $lastSerialNo = substr( $lastSerialNo , 9 ) + 1;
      $serialNo     = $prefixSerialNo . str_pad( $lastSerialNo , $this->serialNoLen , '0' , STR_PAD_LEFT );
    }
    
    return $serialNo;
  }
  
}