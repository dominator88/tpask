<?php namespace apps\common\service;
/**
 * MerUserCart Service
 *
 * @author  Zix
 * @version 2.0 2016-10-19
 */


class MerUserCartService extends BaseService {
  
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
      self::$instance        = new MerUserCartService();
      self::$instance->model = db( 'MerUserCart' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'         => '' ,
      'mer_id'     => '' ,
      'user_id'    => '' ,
      'goods_id'   => '' ,
      'goods_name' => '' ,
      'qty'        => '0' ,
      'price'      => '0.00' ,
      'sub_total'  => '0.00' ,
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
      'field'            => [ 'c.*' ] ,
      'merId'            => '' ,
      'userId'           => '' ,
      'keyword'          => '' ,
      'status'           => '' ,
      'page'             => 1 ,
      'pageSize'         => 10 ,
      'sort'             => 'id' ,
      'order'            => 'DESC' ,
      'count'            => FALSE ,
      'getAll'           => FALSE ,
      'withCurrentPrice' => FALSE ,
    ];
    
    $param = extend( $default , $param );
    
    $this->model->alias( 'c' );
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'c.name' , 'like' , "%{$param['keyword']}%" );
    }
    
    if ( $param['merId'] !== '' ) {
      $this->model->where( 'c.mer_id' , $param['merId'] );
    }
    
    if ( $param['userId'] !== '' ) {
      $this->model->where( 'c.user_id' , $param['userId'] );
    }
    if ( $param['withCurrentPrice'] ) {
      $this->model->join( 'mer_goods g' , 'g.id = c.goods_id' , 'left' );
      $param['field'] = array_merge( $param['field'] , [ 'g.price current_price' , 'g.status current_status' ] );
    }
    $this->model->field( $param['field'] );
    
    $order[] = "c.{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  
  public function insert( $data ) {
    $newData = [];
    
    //检查老数据
    $oldData     = $this->getByCond( [
      'merId'  => $data['merId'] ,
      'userId' => $data['userId']
    ] );
    $oldGoodsIds = [];
    foreach ( $oldData as $item ) {
      $oldGoodsIds[] = $item['goods_id'];
    }
    
    try {
      //检查商品并添加
      $newGoodsIds     = array_keys( $data['items'] );
      $needUpdateGoods = array_intersect( $oldGoodsIds , $newGoodsIds );
      $needInsertGoods = array_diff( $newGoodsIds , $oldGoodsIds );
      
      if ( ! empty( $needInsertGoods ) ) {
        //如果有需要添加到购物车的商品
        $MerGoods = MerGoodsService::instance();
        
        $goodsData = $MerGoods->getByCond( [
          'ids'    => $needInsertGoods ,
          'merId'  => $data['merId'] ,
          'status' => 1
        ] );
        if ( empty( $goodsData ) ) {
          throw new \Exception( '商品未找到' );
        }
        
        foreach ( $goodsData as $item ) {
          $newData[] = [
            'user_id'    => $data['userId'] ,
            'mer_id'     => $data['merId'] ,
            'goods_id'   => $item['id'] ,
            'goods_name' => $item['name'] ,
            'goods_icon' => $item['icon'] ,
            'qty'        => $data['items'][ $item['id'] ] ,
            'price'      => $item['price'] ,
            'sub_total'  => $data['items'][ $item['id'] ] * $item['price']
          ];
        }
        
        $rows = $this->model->insertAll( $newData );
        if ( $rows <= 0 ) {
          return ajax_arr( '添加购物车失败' , 500 );
        }
      }
      
      if ( ! empty( $needUpdateGoods ) ) {
        //如果有要更新的商品
        foreach ( $oldData as $item ) {
          if ( in_array( $item['goods_id'] , $needUpdateGoods ) ) {
            $this->model->where( 'id' , $item['id'] )->update( [
              'qty'       => $data['items'][ $item['goods_id'] ] ,
              'sub_total' => [ 'exp' , $data['items'][ $item['goods_id'] ] . '* price' ]
            ] );
          }
        }
      }
      
      return ajax_arr( '添加购物车成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( '添加购物车失败, ' . $e->getMessage() , 500 );
    }
  }
  
  /**
   * 从购物车删除商品
   *
   * @param $ids
   * @param $merId
   * @param $userId
   *
   * @return array
   */
  public function delete( $ids , $merId , $userId ) {
    
    try {
      $this->model
        ->where( 'id' , 'in' , $ids )
        ->where( 'user_id' , $userId )
        ->where( 'mer_id' , $merId )
        ->delete();
      
      return ajax_arr( '删除购物车商品成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 清空购物车
   *
   * @param $merId
   * @param $userId
   *
   * @return array
   */
  public function destroy( $merId , $userId ) {
    try {
      $this->model
        ->where( 'user_id' , $userId )
        ->where( 'mer_id' , $merId )
        ->delete();
      
      return ajax_arr( '清空购物车成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
}