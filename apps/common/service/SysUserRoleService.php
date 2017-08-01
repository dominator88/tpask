<?php
/**
 * SysUserRole Service
 *
 * @author  Zix
 * @version 2.0 2016-09-11
 */

namespace apps\common\service;

use think\Db;

class SysUserRoleService extends BaseService {
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
      self::$instance        = new SysUserRoleService();
      self::$instance->model = db( 'SysUserRole' );
    }
    
    return self::$instance;
  }
  
  /**
   * 取默认值
   *
   * @return array
   */
  public function getDefaultRow() {
    return [
      'id'      => '' ,
      'user_id' => '' ,
      'role_id' => '' ,
    ];
  }
  
  /**
   * 根据条件查询
   *
   * @param $param
   *
   * @return array
   */
  public function getByCond( $param ) {
    $default = [
      'field'    => '' ,
      'keyword'  => '' ,
      'status'   => '' ,
      'page'     => 1 ,
      'pageSize' => 10 ,
      'sort'     => 'id' ,
      'order'    => 'DESC' ,
      'count'    => FALSE ,
      'getAll'   => FALSE ,
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
    } else {
      $this->model->field( $param['field'] );
      
      if ( ! $param['getAll'] ) {
        $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
      }
      
      $order[] = "{$param['sort']} {$param['order']}";
      $this->model->order( $order );
      
      $data = $this->model->select();
      //echo $this->model->getLastSql();
    }
    
    return $data ? $data : [];
  }
  
  /**
   * 根据用户取角色
   *
   * @param $userId
   * @param bool $concatRole
   *
   * @return array
   */
  public function getByUser( $userId , $concatRole = FALSE ) {
    
    $this->model->alias( 'ur' );
    $this->model->join( 'sys_role r' , 'r.id = ur.role_id' );
    $this->model->where( 'ur.user_id' , $userId );
    
    if ( $concatRole ) {
      $this->model->field( [
        'GROUP_CONCAT( ur.role_id ) AS role_id' ,
        'GROUP_CONCAT( r.name ) AS role_name' ,
        'MAX(r.rank) AS role_rank'
      ] );
      $data = $this->model->find();
    } else {
      $this->model->field( [
        'ur.*' ,
        'r.name as role_name' ,
        'r.rank as role_rank'
      ] );
      $data = $this->model->select();
    }
    
    return $data ? $data : [];
  }
  
  /**
   * 删除用户角色
   *
   * @param $userId
   *
   * @return array
   */
  public function destroyByUser( $userId ) {
    try {
      $this->model->where( 'user_id' , $userId )->delete();
      
      return ajax_arr( '删除成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 更新用户角色
   *
   * @param $userId
   * @param array $roles
   *
   * @return array
   */
  public function updateByUser( $userId , $roles = [] ) {
    if ( $userId == config( 'superAdminId' ) ) {
      return ajax_arr( '修改成功' , 0 );
    }
    
    //查询老数据
    $oldData = $this->getByUser( $userId );
    
    $oldRoles = [];
    foreach ( $oldData as $row ) {
      $oldRoles[] = $row['role_id'];
    }
    
    //查询差值
    $needDelete = array_diff( $oldRoles , $roles );
    $needAdd    = array_diff( $roles , $oldRoles );
    
    if ( ! empty( $needDelete ) ) {
      //删除取消的角色
      $this->model->where( 'user_id' , $userId );
      $this->model->where( 'role_id' , 'in' , $needDelete );
      $this->model->delete();
    }
    
    if ( ! empty( $needAdd ) ) {
      //添加新加的角色
      $data = [];
      foreach ( $needAdd as $role ) {
        $data[] = [
          'user_id' => $userId ,
          'role_id' => $role
        ];
      }
      
      $this->model->insertAll( $data );
    }
    
    return ajax_arr( '更新成功' , 0 );
    
  }
}