<?php namespace apps\common\service;

/**
 * AskSysUser Service
 *
 * @author  Zix
 * @version 2.0 2016-09-27
 */


class AskSysUserService extends BaseService {
  
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
      self::$instance        = new AskSysUserService();
      self::$instance->model = db( 'AskSysUser' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'          => '' ,
      'ask_id'      => '' ,
      'sys_user_id' => '' ,
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
   * 新增机构管理员
   *
   * @param $askId
   * @param $data
   *
   * @return array
   */
  function insert(  $data ) {
    try {
      $SysUser = SysUserService::instance();
      $result  = $SysUser->insert( $data );
      
      if ( $result['code'] != 0 ) {
        throw new \Exception( $result['msg'] );
      }
      

      
      
      return ajax_arr( '创建系统管理用户成功' , 0 );
    } catch ( \Exception $e ) {
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 根据ID 更新数据
   *
   * @param $id
   * @param $data
   *
   * @return array
   */
  public function update( $id , $data ) {
    try {
      
      $SysUser = SysUserService::instance();
      $result  = $SysUser->update( $id , $data );
      
      if ( $result['code'] != 0 ) {
        throw new \Exception( $result['msg'] );
      }
      
      return $result;
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 删除机构管理用户
   *
   * @param $askId
   * @param $sys_user_id
   *
   * @return array
   */
  public function destroy( $askId , $sys_user_id ) {
    try {
      $this->model
        ->where( 'ask_id' , $askId )
        ->where( 'sys_user_id' , $sys_user_id )
        ->delete();
      
      $SysUser = SysUserService::instance();
      $result  = $SysUser->destroy( $sys_user_id );
      
      if ( $result['code'] != 0 ) {
        throw new \Exception( $result['msg'] );
      }
      
      return $result;
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
}