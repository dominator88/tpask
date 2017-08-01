<?php namespace apps\common\service;
/**
 * MerPromotionGoodsGifts Service
 *
 * @author  Zix
 * @version 2.0 2016-10-24
 */


class MerPromotionGoodsGiftsService extends BaseService {
  
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
      self::$instance        = new MerPromotionGoodsGiftsService();
      self::$instance->model = db( 'MerPromotionGoodsGifts' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'          => '' ,
      'pg_id'       => '' ,
      'pg_goods_id' => '' ,
      'goods_id'    => '' ,
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
      'field'     => [ 'pgg.*' ] ,
      'keyword'   => '' ,
      'pgId'      => '' ,
      'pgGoodsId' => '' ,
      'goodsId'   => '' ,
      'status'    => '' ,
      'page'      => 1 ,
      'pageSize'  => 10 ,
      'sort'      => 'id' ,
      'order'     => 'DESC' ,
      'count'     => FALSE ,
      'getAll'    => FALSE ,
      'withGoods' => FALSE
    ];
    
    $param = extend( $default , $param );
    
    $this->model->alias( 'pgg' );
    
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'pgg.name' , 'like' , "%{$param['keyword']}%" );
    }
    
    if ( $param['pgId'] !== '' ) {
      $this->model->where( 'pgg.pg_id' , $param['pgId'] );
    }
    
    if ( $param['pgGoodsId'] !== '' ) {
      $this->model->where( 'pgg.pg_goods_id' , $param['pgGoodsId'] );
    }
    
    if ( $param['goodsId'] !== '' ) {
      $this->model->where( 'pgg.goods_id' , $param['goodsId'] );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'pgg.status' , $param['status'] );
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    if ( $param['withGoods'] ) {
      $this->model->join( 'mer_goods g' , 'g.id = pgg.goods_id' , 'left' );
      $param['field'] = array_merge( $param['field'] , [ 'g.name goods_name' , 'g.icon goods_icon' ] );
    }
    
    $this->model->field( $param['field'] );
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "pgg.{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
}