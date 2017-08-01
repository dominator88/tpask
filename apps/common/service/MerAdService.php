<?php namespace apps\common\service;
/**
 * MerAd Service
 *
 * @author  Zix
 * @version 2.0 2016-09-16
 */


class MerAdService extends BaseService {
  
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
      self::$instance        = new MerAdService();
      self::$instance->model = db( 'MerAd' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'         => '' ,
      'mer_id'     => '' ,
      'name'       => '' ,
      'sort'       => '99' ,
      'catalog_id' => '' ,
      'icon'       => '' ,
      'uri'        => '' ,
      'pv'         => '0' ,
      'status'     => '1' ,
      'created_at' => date( 'Y-m-d H:i:s' ) ,
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
      'field'           => [ 'a.*' , 'ac.text catalog_text' , 'ac.width' , 'ac.height' ] ,
      'merId'           => '' ,
      'catalogId'       => '' ,
      'keyword'         => '' ,
      'status'          => '' ,
      'page'            => 1 ,
      'pageSize'        => 10 ,
      'sort'            => 'id' ,
      'order'           => 'DESC' ,
      'count'           => FALSE ,
      'getAll'          => FALSE ,
      'catalogWithSize' => FALSE ,
    ];
    
    $param = extend( $default , $param );
    
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'name' , 'like' , "%{$param['keyword']}%" );
    }
    
    if ( $param['merId'] !== '' ) {
      $this->model->where( 'a.mer_id' , $param['merId'] );
    }
    
    if ( $param['catalogId'] !== '' ) {
      $this->model->where( 'a.catalog_id' , $param['catalogId'] );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'a.status' , $param['status'] );
    }
    
    $this->model->alias( 'a' );
    
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    $this->model
      ->field( $param['field'] )
      ->join( 'mer_ad_catalog ac' , 'ac.id = a.catalog_id' , 'left' );
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "a.{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    if ( $param['catalogWithSize'] ) {
      $data = $this->catalogWithSize( $data );
    }
    
    return $data ? $data : [];
  }
  
  
  private function catalogWithSize( $data ) {
    foreach ( $data as &$item ) {
      $item['catalog_text'] .= "({$item['width']}*{$item['height']})";
    }
    
    return $data;
  }
}