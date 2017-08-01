<?php
/**
 * SysFuncPrivilege Service
 *
 * @author  Zix
 * @version 2.0 2016-05-09
 */

namespace apps\common\service;

use think\Db;

class SysFuncPrivilegeService extends BaseService {
  
  public $name = [
    'read'   => '查看' ,
    'create' => '创建' ,
    'update' => '更新' ,
    'delete' => '删除'
  ];
  
  //默认操作
  public $default = [
    [ 'sort' => '10' , 'name' => 'read' ] ,
    [ 'sort' => '20' , 'name' => 'create' ] ,
    [ 'sort' => '30' , 'name' => 'update' ] ,
    [ 'sort' => '40' , 'name' => 'delete' ] ,
  ];
  
  //操作别名
  public $alias = [
    'read'   => [ 'index' , 'read' , 'get' , 'search' , 'load' , 'download' , 'export' , 'preview' ] ,
    'create' => [ 'insert' , 'create' , 'add' , 'upload' , 'post' , 'import' , 'copy' ] ,
    'update' => [ 'update' , 'set' , 'reset' , 'save' , 'send' , 'change' , 'send' ] ,
    'delete' => [ 'destroy' , 'delete' , 'remove' ] ,
  ];
  
  //类实例
  private static $instance;
  
  //生成类单例
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance        = new SysFuncPrivilegeService();
      self::$instance->model = db( 'SysFuncPrivilege' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'      => '' ,
      'sort'    => '99' ,
      'func_id' => '' ,
      'name'    => '' ,
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
      'funcId'   => '' ,
      'field'    => '' ,
      'page'     => 1 ,
      'pageSize' => 10 ,
      'sort'     => 'id' ,
      'order'    => 'DESC' ,
      'count'    => FALSE ,
      'getAll'   => FALSE
    ];
    
    $param = extend( $default , $param );
    
    if ( $param['funcId'] !== '' ) {
      $this->model->where( 'func_id' , $param['funcId'] );
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    $this->model->field( $param['field'] );
    if ( $param['getAll'] !== TRUE ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['page_size'] , $param['page_size'] );
    }
    
    $order[] = "{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  public function getByAction( $actionName ) {
//		$matches = [];
//		preg_match( "/^[a-z]*/", $actionName, $matches );
    $matches = explode( '_' , $actionName );
    
    
    $alias = $matches[0];
    
    foreach ( $this->alias as $key => $val ) {
      if ( in_array( $alias , $val ) ) {
        return $key;
      }
    }
    
    return FALSE;
  }
  
  /**
   * 根据功能取权限
   *
   * @param $funcId
   *
   * @return array
   */
  public function getByFunc( $funcId ) {
    $data = $this->model->where( 'func_id' , $funcId )->select();
    
    return $data ? $data : [];
  }
  
  /**
   * 根据多个功能
   *
   * @param $funcIds
   *
   * @return array
   */
  public function getByFuncs( $funcIds ) {
    $data    = $this->model->where( 'func_id' , 'in' , $funcIds )->select();
    $newData = [];
    foreach ( $data as $item ) {
      $newData[ $item['func_id'] ][] = $item;
    }
    
    return $newData;
  }
  
  /**
   * 添加默认权限
   *
   * @param $funcId
   * @param int $pid
   *
   * @return array
   */
  public function createDefault( $funcId , $pid = 0 ) {
    $default = $this->default;
    
    try {
      if ( $pid == 0 ) {
        $data = [
          'func_id' => $funcId ,
          'name'    => $default[0]['name'] ,
        ];
        $this->model->insert( $data );
      } else {
        $data = [];
        foreach ( $default as $row ) {
          $data[] = [
            'func_id' => $funcId ,
            'name'    => $row['name']
          ];
        }
        $this->model->insertAll( $data );
      }
      
      return ajax_arr( '添加默认权限成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 更新
   *
   * @param $funcId
   * @param $data
   *
   * @return array
   */
  public function updateByFunc( $funcId , $data ) {
    
    $oldData = $this->getByFunc( $funcId );
    $p       = [];
    foreach ( $oldData as $item ) {
      $p[] = $item['name'];
    }
    
    
    $needAdd    = array_diff( $data['name'] , $p );
    $needDelete = array_diff( $p , $data['name'] );
    
    Db::startTrans();
    try {
      //如果有要添加的
      if ( ! empty( $needAdd ) ) {
        $addData = [];
        foreach ( $needAdd as $name ) {
          $addData[] = [
            'func_id' => $funcId ,
            'name'    => $name
          ];
        }
        $this->model->insertAll( $addData );
      }
      
      //如果有要删除的
      if ( ! empty( $needDelete ) ) {
        $this->model->where( 'func_id' , $funcId );
        $this->model->where( 'name' , 'in' , $needDelete );
        $this->model->delete();
      }
      
      Db::commit();
      
      return ajax_arr( '成功' , 0 );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  function destroyByFunc( $funcId ) {
    try {
      $this->model->where( 'func_id' , $funcId )->delete();
      
      return ajax_arr( '删除权限成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
}