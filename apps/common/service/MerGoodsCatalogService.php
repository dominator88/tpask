<?php namespace apps\common\service;
/**
 * MerGoodsCatalog Service
 *
 * @author  Zix
 * @version 2.0 2016-10-11
 */


class MerGoodsCatalogService extends BaseService {
  
  //引入 TreeTable trait
  use \apps\common\traits\service\TreeTable;
  
  public $type = [
    'goods'   => '商品' ,
    'virtual' => '虚拟道具' ,
    'service' => '服务'
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
      self::$instance        = new MerGoodsCatalogService();
      self::$instance->model = db( 'MerGoodsCatalog' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'     => '' ,
      'mer_id' => '' ,
      'sort'   => '99' ,
      'type'   => 'goods' ,
      'pid'    => '0' ,
      'text'   => '' ,
      'icon'   => '' ,
      'desc'   => '' ,
      'level'  => '1' ,
      'status' => '1' ,
    ];
  }
  
  
  //根据条件查询
  public function getByCond( $param ) {
    $default = [
      'field'        => [] ,
      'pid'          => 0 ,
      'merId'        => '' ,
      'status'       => '' ,
      'withTypeText' => FALSE ,
      'key'          => 'children'
    ];
    $param   = extend( $default , $param );
    
    if ( $param['merId'] !== '' ) {
      $this->model->where( 'mer_id' , $param['merId'] );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'status' , $param['status'] );
    }
    
    $data = $this->model
      ->field( $param['field'] )
      ->order( 'level ASC , sort ASC ' )
      ->select();
    
    if ( $param['withTypeText'] ) {
      foreach ( $data as &$item ) {
        $item['type_text'] = $this->type[ $item['type'] ];
      }
    }
    //echo $this->model->_sql();
    
    $result = [];
    $index  = [];
    
    foreach ( $data as $row ) {
      if ( $row['pid'] == $param['pid'] ) {
        $result[ $row['id'] ] = $row;
        $index[ $row['id'] ]  = &$result[ $row['id'] ];
      } else {
        $index[ $row['pid'] ][ $param['key'] ][ $row['id'] ] = $row;
        $index[ $row['id'] ]                                 = &$index[ $row['pid'] ][ $param['key'] ][ $row['id'] ];
      }
    }
    
    return $this->treeToArray( $result , $param['key'] );
  }
  
}