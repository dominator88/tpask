<?php namespace apps\common\service;

/**
 * MerGoodsIcon Service
 *
 * @author  Zix
 * @version 2.0 2016-10-11
 */

use think\Db;

class MerGoodsIconService extends BaseService {
  
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
      self::$instance        = new MerGoodsIconService();
      self::$instance->model = db( 'MerGoodsIcon' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'       => '' ,
      'sort'     => '99' ,
      'goods_id' => '' ,
      'uri'      => '' ,
      'is_cover' => '0' ,
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
      'goodsId'  => '' ,
      'status'   => '' ,
      'isCover'  => FALSE ,
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
    
    if ( $param['goodsId'] !== '' ) {
      $this->model->where( 'goods_id' , $param['goodsId'] );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'status' , $param['status'] );
    }
    
    if ( $param['isCover'] ) {
      $this->model->where( 'is_cover' , $param['isCover'] );
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    $this->model->field( $param['field'] );
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "is_cover DESC , {$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  public function getByGoodsId( $goodsId , $isCover = FALSE ) {
    
    return $this->getByCond( [
      'goodsId' => $goodsId ,
      'isCover' => $isCover ,
      'getAll'  => TRUE
    ] );
  }
  
  /**
   * 根据商品添加图片
   *
   * @param $data
   *
   * @return array
   */
  public function insert( $data ) {
    $oldData = $this->getByGoodsId( $data['goods_id'] , TRUE );
    if ( empty( $oldData ) ) {
      $data['is_cover'] = 1;
    }
    
    Db::startTrans();
    try {
      
      $id = $this->model->insertGetId( $data );
      if ( $id > 0 ) {
        //更新 商品封面
        db( 'mer_goods' )
          ->where( 'id' , $data['goods_id'] )
          ->update( [
            'icon' => $data['uri']
          ] );
      }
      
      Db::commit();
      
      return ajax_arr( '添加商品图片成功' , 0 );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 重新设置商品封面
   *
   * @param $id
   *
   * @return array
   */
  public function setCover( $id ) {
    $oldData = $this->getById( $id );
    
    Db::startTrans();
    try {
      if ( empty( $oldData ) ) {
        throw new \Exception( '图片未找到' );
      }
      
      if ( $oldData['is_cover'] == 1 ) {
        throw new \Exception( '已经设置过了' );
      }
      
      //更新封面标志
      $this->model
        ->where( 'id' , $id )
        ->update( [ 'is_cover' => 1 ] );
      
      //将其他图片设置为不是封面
      $this->model
        ->where( 'goods_id' , $oldData['goods_id'] )
        ->where( 'id' , 'neq' , $id )
        ->update( [ 'is_cover' => 0 ] );
      
      //更新商品封面
      db( 'mer_goods' )
        ->where( 'id' , $oldData['goods_id'] )
        ->update( [
          'icon' => $oldData['uri']
        ] );
      
      Db::commit();
      
      return ajax_arr( '设置封面成功' , 0 );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 删除商品图片
   *
   * @param $id
   *
   * @return array
   */
  public function destroy( $id ) {
    $oldData = $this->getById( $id );
    
    Db::startTrans();
    try {
      if ( empty( $oldData ) ) {
        throw new \Exception( '图片未找到' );
      }
      
      $this->model->delete( $id );
      if ( $oldData['is_cover'] == 1 ) {
        $newData = $this->getByGoodsId( $oldData['goods_id'] );
        $newUri  = '';
        if ( ! empty( $newData ) ) {
          $this->model->where( 'id' , $newData[0]['id'] )
                      ->update( [ 'is_cover' => 1 ] );
          $newUri = $newData[0]['uri'];
        }
        
        db( 'mer_goods' )
          ->where( 'id' , $newData[0]['id'] )
          ->update( [
            'icon' => $newUri
          ] );
      }
      
      Db::commit();
      
      return ajax_arr( '删除封面成功' , 0 );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
    
    
  }
  
}