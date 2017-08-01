<?php
/**
 * SysUser Service
 *
 * @author  Zix
 * @version 2.0 2016-09-08
 */

namespace apps\common\service;

use think\Db;

class SysUserService extends BaseService {
  
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
      self::$instance        = new SysUserService();
      self::$instance->model = db( 'SysUser' );
    }
    
    return self::$instance;
  }
  
  /**
   * 取默认值
   *
   * @return array
   */
  function getDefaultRow() {
    return [
      'id'         => '' ,
      'module'     => 'backend' ,
      'username'   => '' ,
      'password'   => '' ,
      'icon'       => '' ,
      'email'      => '' ,
      'phone'      => '' ,
      'status'     => '1' ,
      'token'      => '' ,
      'created_at' => date( 'Y-m-d H:i:s' )
    ];
  }
  
  /**
   * 根据条件查询
   *
   * @param $params
   *
   * @return array|number
   */
  public function getByCond( $params ) {
    $default = [
      'field'     => [ '*' ] ,
      'module'    => 'backend' ,
      'keyword'   => '' ,
      'status'    => '' ,
      'merId'     => '' ,
      'page'      => 1 ,
      'pageSize'  => 10 ,
      'sort'      => 'id' ,
      'order'     => 'DESC' ,
      'getAll'    => FALSE ,
      'count'     => FALSE ,
      'withPwd'   => FALSE ,
      'withRoles' => FALSE ,
      'merchant'  => FALSE
    ];
    
    $params = extend( $default , $params );
    
    if ( $params['merchant'] ) {
      return $this->getMerSysUserByCond( $params );
    }
    //$where[] = [ 'id', '<>', config( 'SUPER_ADMIN_ID' ) ];
    if ( ! empty( $params['keyword'] ) ) {
      $this->model->where( 'name' , 'like' , "%{$params['keyword']}%" );
    }
    
    if ( $params['module'] !== '' ) {
      $this->model->where( 'module' , '=' , $params['module'] );
    }
    
    if ( $params['status'] !== '' ) {
      $this->model->where( 'status' , '=' , $params['status'] );
    }
    
    if ( $params['count'] ) {
      return $this->model->count();
    } else {
      $this->model->field( $params['field'] );
      
      if ( ! $params['getAll'] ) {
        $this->model->limit( ( $params['page'] - 1 ) * $params['pageSize'] , $params['pageSize'] );
      }
      
      $order[] = "{$params['sort']} {$params['order']}";
      $this->model->order( $order );
      
      $data = $this->model->select();

    }

    if ( ! $params['withPwd'] ) {
      foreach ( $data as &$item ) {
        unset( $item['password'] );
      }
    }
    
    if ( $params['withRoles'] ) {
      $data = $this->getRoles( $data );
    }
    
    return $data ? $data : [];
  }
  
  /**
   * todo 需要优化
   *
   * @param $data
   *
   * @return mixed
   */
  private function getRoles( $data ) {
    $SysUserRole = SysUserRoleService::instance();
    
    foreach ( $data as &$item ) {
      $item['roles'] = $SysUserRole->getByUser( $item['id'] );
    }
    
    return $data;
  }
  
  /**
   * 获取 MP 平台用户
   *
   * @param $params
   *
   * @return array
   */
  private function getMerSysUserByCond( $params ) {
    $model = db( 'mer_sys_user' );
    
    $model->alias( 'msu' )
          ->where( 'msu.mer_id' , $params['merId'] )
          ->join( 'sys_user u' , 'u.id = msu.sys_user_id' , 'left' );
    
    if ( $params['status'] !== '' ) {
      $model->where( 'u.status' , $params['status'] );
    }
    
    if ( $params['count'] ) {
      return $model->count();
    } else {
      $model->field( 'u.*' );
      
      if ( ! $params['getAll'] ) {
        $model->limit( ( $params['page'] - 1 ) * $params['pageSize'] , $params['pageSize'] );
      }
      
      $order[] = "u.{$params['sort']} {$params['order']}";
      $model->order( $order );
      
      $data = $model->select();
//      echo $model->getLastSql();
    }
    
    if ( ! $params['withPwd'] ) {
      foreach ( $data as &$item ) {
        unset( $item['password'] );
      }
    }
    
    if ( $params['withRoles'] ) {
      $data = $this->getRoles( $data );
    }
    
    return $data ? $data : [];
    
  }
  
  /**
   * 更新密码
   *
   * @param $id
   * @param $data
   *
   * @return array
   */
  function uploadPwd( $id , $data ) {
    try {
      $this->model->where( 'id' , $id )->update( $data );
      
      return ajax_arr( '更新成功' , 0 );
    } catch ( \Exception $e ) {
      //echo $this->model->getLastSql();
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 添加数据
   *
   * @param $data
   *
   * @return array
   */
  public function insert( $data ) {
    Db::startTrans();
    try {
      if ( empty( $data ) ) {
        throw new \Exception( '数据不能为空' );
      }
      
      $roles = isset( $data['roles'] ) ? $data['roles'] : [];
      unset( $data['roles'] );
      $data['password'] = str2pwd( config( 'defaultPwd' ) );

      $id = $this->model->insertGetId( $data );
      if ( $id <= 0 ) {
        throw new \Exception( '创建用户失败' );
      }
      
      //更新用户角色
      if ( ! empty( $roles ) ) {
        $SysUserRole = SysUserRoleService::instance();
        $RoleResult  = $SysUserRole->updateByUser( $id , $roles );
        if ( $RoleResult['code'] > 0 ) {
          throw new \Exception( $RoleResult['msg'] );
        }
      }
      
      Db::commit();
      
      return ajax_arr( '创建用户成功' , 0 , [ 'id' => $id ] );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  
  //更新
  function update( $id , $data ) {
    Db::startTrans();
    try {
      $roles = [];
      if ( isset( $data['roles'] ) ) {
        $roles = $data['roles'];
      }
      
      unset( $data['roles'] );
      $ret = $this->model->where( 'id' , $id )->update( $data );
      
      //更新用户角色
      $SysUserRole = SysUserRoleService::instance();
      $RoleResult  = $SysUserRole->updateByUser( $id , $roles );
      if ( $RoleResult['code'] > 0 ) {
        throw new \Exception( $RoleResult['msg'] );
      }
      
      Db::commit();
      
      return ajax_arr( '更新成功' , 0 );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      //echo $this->model->getLastSql();
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 删除系统用户
   *
   * @param $id
   *
   * @return array
   */
  public function destroy( $id ) {
    try {
      if ( $id <= 2 ) {
        throw new \Exception( '系统用户不能删除' );
      }
      //删除用户角色
      db( 'sys_user_role' )->where( 'user_id' , $id )->delete();
      
      //删除用户
      $this->model->where( 'id' , $id )->delete();
      
      return ajax_arr( '删除成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 重置密码
   *
   * @param $id
   * @param $pwd
   *
   * @return array
   */
  public function resetPwd( $id , $pwd ) {
    try {
      $data['password'] = str2pwd( $pwd );
      $row              = $this->model->where( 'id' , $id )->update( $data );
      if ( $row <= 0 ) {
        return ajax_arr( '未修改任何记录' , 500 );
      }
      
      return ajax_arr( '重置密码成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
}