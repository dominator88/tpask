<?php namespace apps\common\service;
/**
 * MerOrderItem Service
 *
 * @author  Zix
 * @version 2.0 2016-10-12
 */


class MerOrderItemsService extends BaseService {
  
  //引入 GridTable trait
  use \apps\common\traits\service\GridTable;
  
  
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
      self::$instance        = new MerOrderItemsService();
      self::$instance->model = db( 'MerOrderItems' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'            => '' ,
      'order_id'      => '' ,
      'goods_id'      => '' ,
      'goods_name'    => '' ,
      'icon'          => '' ,
      'currency'      => '' ,
      'amount'        => '0.00' ,
      'qty'           => '' ,
      'event_id'      => '' ,
      'event_amount'  => '0' ,
      'coupon_id'     => '' ,
      'coupon_amount' => '0' ,
      'get_points'    => '' ,
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
      'field'    => [ 'oi.*' ] ,
      'keyword'  => '' ,
      'goodsId'  => '' ,
      'orderId'  => '' ,
      'orderIds' => [] ,
      'status'   => '' ,
      'page'     => 1 ,
      'pageSize' => 10 ,
      'sort'     => 'id' ,
      'order'    => 'DESC' ,
      'count'    => FALSE ,
      'getAll'   => FALSE ,
      'withUser' => FALSE ,
    ];
    
    $param = extend( $default , $param );
    
    $this->model->alias( 'oi' );
    
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'oi.goods_name' , 'like' , "%{$param['keyword']}%" );
    }
    
    if ( $param['goodsId'] !== '' ) {
      $this->model->where( 'oi.goods_id' , $param['goodsId'] );
    }
    
    if ( $param['orderId'] !== '' ) {
      $this->model->where( 'oi.order_id' , $param['orderId'] );
    }
    
    if ( ! empty( $param['orderIds'] ) ) {
      $this->model->where( 'oi.order_id' , 'in' , $param['orderIds'] );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'oi.status' , $param['status'] );
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    if ( $param['withUser'] ) {
      $this->model->join( 'mer_order o' , 'o.id = oi.order_id' );
      $this->model->join( 'mer_user u' , 'u.id = o.user_id' );
      $param['field'] = array_merge( $param['field'] , [
        'u.nickname' ,
        'u.phone' ,
        'o.order_no' ,
        'o.address_name' ,
        'o.address_phone' ,
        'o.status' ,
        'o.created_at' ,
      ] );
    }
    
    $this->model->field( $param['field'] );
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    
    $order[] = "oi.{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  
}