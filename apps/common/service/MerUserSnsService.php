<?php namespace apps\common\service;
/**
 * MerUserSns Service
 *
 * @author  Zix
 * @version 2.0 2016-10-21
 */


class MerUserSnsService extends BaseService {
  
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
      self::$instance        = new MerUserSnsService();
      self::$instance->model = db( 'MerUserSns' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'         => '' ,
      'platform'   => 'qq' ,
      'user_id'    => '' ,
      'sns_uid'    => '' ,
      'username'   => '' ,
      'icon'       => '' ,
      'gender'     => '' ,
      'location'   => '' ,
      'province'   => '' ,
      'city'       => '' ,
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
   * 根据第三方账户ID 取用户数据
   *
   * @param $snsUid
   * @param $platform
   *
   * @return array
   */
  public function getBySnsUid( $snsUid , $platform ) {
    $data = $this->model
      ->where( 'sns_uid' , $snsUid )
      ->where( 'platform' , $platform )
      ->find();
    
    return $data ? $data : [];
    
  }
  
}