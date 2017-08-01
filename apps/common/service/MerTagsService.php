<?php namespace apps\common\service;
/**
 * MerTags Service
 *
 * @author  Zix
 * @version 2.0 2016-09-23
 */


class MerTagsService extends BaseService {
  
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
      self::$instance        = new MerTagsService();
      self::$instance->model = db( 'MerTags' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'   => '' ,
      'text' => '' ,
      'type' => 'article' ,
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
      'field'    => [] ,
      'keyword'  => '' ,
      'merId'    => '' ,
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
    
    if ( $param['merId'] !== '' ) {
      $this->model->where( 'mer_id' , $param['merId'] );
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
    
    if ( $param['sort'] == 'rand' ) {
      $this->model->order( 'rand()' );
    } else {
      $order[] = "{$param['sort']} {$param['order']}";
      $this->model->order( $order );
    }
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  public function getIdByTypeAndTags( $merId , $type , $tags ) {
    //取数据
    $data = $this->model
      ->where( 'mer_id' , $merId )
      ->where( 'type' , $type )
      ->where( 'text' , 'in' , $tags )
      ->select();
    
    $oldTags = [];
    foreach ( $data as $item ) {
      $oldTags[] = $item['text'];
    }
    
    //需要添加的新tags
    $needAdd = array_diff( $tags , $oldTags );
    
    if ( ! empty( $needAdd ) ) {
      //如果未找到 则添加
      $newTags = [];
      foreach ( $needAdd as $tag ) {
        $newTags[] = [
          'mer_id' => $merId ,
          'type'   => $type ,
          'text'   => $tag
        ];
      }
      try {
        $row = $this->model->insertAll( $newTags );
        if ( $row == 0 ) {
          throw new \Exception( '添加新tags未成功' );
        }
        
        $data = $this->model
          ->where( 'type' , $type )
          ->where( 'text' , 'in' , $tags )
          ->select();
        
      } catch ( \Exception $e ) {
        return [];
      }
      
    }
    
    //如果找到 则返回
    $newData = [];
    foreach ( $data as $item ) {
      $newData[] = $item['id'];
    }
    
    return $newData;
  }
  
  public function setCountByIds( $ids , $action = 'dec' ) {
    $this->model->where( 'id' , 'in' , $ids );
    if ( $action == 'dec' ) {
      $this->model->setDec( 'count' );
    } else {
      $this->model->setInc( 'count' );
    }
    
    return TRUE;
  }
  
}