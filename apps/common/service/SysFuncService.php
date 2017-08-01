<?php
/**
 * SysFunc Service
 *
 * @author  Zix
 * @version 2.0 2016-09-12
 */

namespace apps\common\service;

use think\Db;

class SysFuncService extends BaseService {
  
  use \apps\common\traits\service\TreeTable;
  
  public $isFunc = [
    0 => '否' ,
    1 => '是'
  ];
  
  public $isMenu = [
    0 => '否' ,
    1 => '是'
  ];
  
  //状态
  public $status = [
    0 => '禁用' ,
    1 => '启用' ,
  ];
  
  const DEFAULT_KEY = 'children';
  
  //类实例
  private static $instance;
  
  //生成类单例
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance        = new SysFuncService();
      self::$instance->model = db( 'SysFunc' );
    }
    
    return self::$instance;
  }
  
  //默认行
  public function getDefaultRow() {
    return [
      'sort'   => '99' ,
      'module' => 'backend' ,
      'isMenu' => '1' ,
      'isFunc' => '0' ,
      'color'  => 'default' ,
      'name'   => '' ,
      'icon'   => '' ,
      'uri'    => '' ,
      'desc'   => '' ,
      'level'  => '1' ,
      'status' => '1' ,
    ];
  }
  
  //根据条件查询
  public function getByCond( $param ) {
    $default = [
      'field'         => [ '*' ] ,
      'module'        => 'backend' ,
      'isMenu'        => '' ,
      'pid'           => 0 ,
      'status'        => '' ,
      'withPrivilege' => FALSE ,
      'key'           => self::DEFAULT_KEY
    ];
    $param   = extend( $default , $param );
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'status' , $param['status'] );
    }
    
    if ( $param['module'] !== '' ) {
      $this->model->where( 'module' , $param['module'] );
    }
    
    if ( $param['isMenu'] !== '' ) {
      $this->model->where( 'is_menu' , $param['isMenu'] );
    }
    
    $this->model->field( $param['field'] );
    $this->model->order( 'level ASC , sort ASC ' );
    $data = $this->model->select();
    
    if ( $param['withPrivilege'] ) {
      $data = $this->withPrivilege( $data );
    }
    
    //echo $this->model->getLastSql();
    $result = [];
    $index  = [];
    
    foreach ( $data as $row ) {
      if ( $row['pid'] == $param['pid'] ) {
        $result[ $row['id'] ] = $row;
        $index[ $row['id'] ]  = &$result[ $row['id'] ];
      } else {
        $index[ $row['pid'] ][ $param['key'] ][ $row['id'] ] = $row;
        
        $index[ $row['id'] ] = &$index[ $row['pid'] ][ $param['key'] ][ $row['id'] ];
      }
    }
    
    $tree_data = $this->treeToArray( $result , $param['key'] );
    
    return $tree_data;
  }
  
  /**
   * 获取功能权限
   *
   * @param $data
   *
   * @return mixed
   */
  private function withPrivilege( $data ) {
    $allId = [];
    foreach ( $data as $item ) {
      $allId[] = $item['id'];
    }
    
    $SysFuncPrivilege = SysFuncPrivilegeService::instance();
    $allPrivileges    = $SysFuncPrivilege->getByFuncs( $allId );
    
    foreach ( $data as &$item ) {
      if ( isset( $allPrivileges[ $item['id'] ] ) ) {
        $item['privilege'] = $allPrivileges[ $item['id'] ];
      } else {
        $item['privilege'] = [];
      }
    }
    
    return $data;
  }
  
  /**
   *
   * 添加数据
   *
   * @param $data
   *
   * @return array
   */
  public function insert( $data ) {
    try {
      if ( empty( $data ) ) {
        throw new \Exception( '数据不能为空' );
      }
      $data['level'] = $this->getLevel( $data['pid'] );
      $id   = $this->model->insertGetId( $data );
      
      $SysFuncPrivilege = SysFuncPrivilegeService::instance();
      $SysFuncPrivilege->createDefault( $id , $data['pid'] );
      
      return ajax_arr( '创建成功' , 0 , [ 'id' => $id ] );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 根据ID 删除数据
   *
   * @param $id //string | array
   *
   * @return array
   */
  public function destroy( $id ) {
    
    Db::startTrans();
    try {
      
      $oldData = $this->getByPid( $id );
      if ( ! empty( $oldData ) ) {
        throw new \Exception( '还有下级,不能删除' );
      }
      
      //先删除授权
      $SysRolePermission = SysRolePermissionService::instance();
      $SysRolePermission->destroyByFunc( $id );
      
      //再删除权限
      $SysFuncPrivilege = SysFuncPrivilegeService::instance();
      $SysFuncPrivilege->destroyByFunc( $id );
      
      $this->model->delete( $id );
      
      
      Db::commit();
      
      return ajax_arr( '删除成功' , 0 );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 根据角色取菜单
   *
   * @param $roleIds
   * @param $module
   *
   * @return array
   */
  public function getMenuByRoles( $roleIds , $module ) {
    $roleIds = explode( ',' , $roleIds );
    if ( $roleIds == config( 'superAdminId' ) || in_array( config( 'superAdminId' ) , $roleIds ) ) {
      //如果是系统管理员
      return $this->getByCond( [
        'isMenu' => 1 ,
        'status' => 1 ,
        'module' => $module ,
      ] );
    } else {
      //如果是普通用户
      return $this->_getMenuByRoles( $roleIds , $module );
    }
  }
  
  /**
   * 查找除非超级管理员的菜单
   *
   * @param $roleIds
   * @param $module
   *
   * @return array
   */
  private function _getMenuByRoles( $roleIds , $module ) {
    $key = self::DEFAULT_KEY;
    
    $data = $this->model
      ->alias( 'f' )
      ->field( 'DISTINCT f.id , f.sort , f.pid , f.name , f.icon , f.uri , f.level' )
      ->where( 'f.is_menu' , 1 )
      ->where( 'f.status' , 1 )
      ->where( 'f.module' , $module )
      ->where( 'rp.role_id' , 'in' , $roleIds )
      ->where( 'fp.name' , 'read' )
      ->join( 'sys_func_privilege fp' , 'fp.func_id = f.id' )
      ->join( 'sys_role_permission rp' , 'rp.privilege_id = fp.id' )
      ->order( 'f.level ASC , f.sort ASC' )
      ->select();
    
    $result = [];
    $index  = [];
    
    foreach ( $data as $row ) {
      if ( $row['pid'] == 0 ) {
        $result[ $row['id'] ] = $row;
        $index[ $row['id'] ]  = &$result[ $row['id'] ];
      } else {
        $index[ $row['pid'] ][ $key ][ $row['id'] ] = $row;
        
        $index[ $row['id'] ] = &$index[ $row['pid'] ][ $key ][ $row['id'] ];
      }
    }
    
    return $this->treeToArray( $result , self::DEFAULT_KEY );
  }
}