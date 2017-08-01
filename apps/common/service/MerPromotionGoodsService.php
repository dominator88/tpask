<?php namespace apps\common\service;
/**
 * MerPromotionGoods Service
 *
 * @author  Zix
 * @version 2.0 2016-10-24
 */


class MerPromotionGoodsService extends BaseService {
  
  //引入 GridTable trait
  use \apps\common\traits\service\GridTable;
  
  public $form = [
    'discount' => '折扣' ,
    'off'      => '减免' ,
    'gift'     => '礼品' ,
  ];
  
  public $condition = [
    'no'     => '无条件' ,
    'amount' => '价格' ,
    'count'  => '数量'
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
      self::$instance        = new MerPromotionGoodsService();
      self::$instance->model = db( 'MerPromotionGoods' );
    }
    
    return self::$instance;
  }
  
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'              => '' ,
      'promotion_id'    => '' ,
      'goods_id'        => '' ,
      'condition'       => 'no' ,
      'condition_value' => 0.00 ,
      'form'            => 'discount' ,
      'form_value'      => 0.00 ,
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
      'field'       => [ 'pg.*' ] ,
      'keyword'     => '' ,
      'promotionId' => '' ,
      'status'      => '' ,
      'page'        => 1 ,
      'pageSize'    => 10 ,
      'sort'        => 'id' ,
      'order'       => 'DESC' ,
      'count'       => FALSE ,
      'getAll'      => FALSE ,
      'withGoods'   => FALSE
    ];
    
    $param = extend( $default , $param );
    
    $this->model->alias( 'pg' );
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'pg.name' , 'like' , "%{$param['keyword']}%" );
    }
    
    if ( $param['promotionId'] !== '' ) {
      $this->model->where( 'pg.promotion_id' , $param['promotionId'] );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'pg.status' , $param['status'] );
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    if ( $param['withGoods'] ) {
      $this->model->join( 'mer_goods g' , 'g.id = pg.goods_id' , 'left' );
      $param['field'] = array_merge( $param['field'] , [ 'g.name goods_name' , 'g.icon goods_icon' ] );
    }
    
    $this->model->field( $param['field'] );
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "pg.{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  /**
   * 根据 促销ID 和 商品ID
   *
   * @param $promotionId
   * @param $goodsId
   *
   * @return array
   */
  public function getByPromotionGoods( $promotionId , $goodsId ) {
    $data = $this->model
      ->where( 'promotion_id' , $promotionId )
      ->where( 'goods_id' , $goodsId )
      ->find();
    
    return $data ? $data : [];
  }
  
  /**
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
      
      if ( empty( $data['goods_id'] ) ) {
        throw new \Exception( '请选择商品' );
      }
      
      if ( ! is_array( $data['goods_id'] ) ) {
        //如果只有添加一件商品
        $oldData = $this->getByPromotionGoods( $data['promotion_id'] , $data['goods_id'] );
        if ( ! empty( $oldData ) ) {
          throw new \Exception( '商品已经添加了' );
        }
        $id = $this->model->insertGetId( $data );
        
        return ajax_arr( '创建成功' , 0 , [ 'id' => $id ] );
      } else {
        //如果有多个商品
        $oldData  = $this->getByCond( [
          'promotionId' => $data['promotion_id'] ,
          'getAll'      => TRUE
        ] );
        $oldGoods = [];
        foreach ( $oldData as $item ) {
          $oldGoods[] = $item['id'];
        }
        $needAddGoods = array_diff( $data['goods_id'] , $oldGoods );
        if ( empty( $needAddGoods ) ) {
          throw new \Exception( '商品都添加过了' );
        }
        
        $newData = [];
        foreach ( $needAddGoods as $goodsId ) {
          $newData[] = [
            'promotion_id'    => $data['promotion_id'] ,
            'goods_id'        => $goodsId ,
            'condition'       => $data['condition'] ,
            'condition_value' => $data['condition_value'] ,
            'form'            => $data['form'] ,
            'form_value'      => $data['form_value'] ,
          ];
        }
        $rows = $this->model->insertAll( $newData );
        if ( $rows <= 0 ) {
          throw new \Exception( '添加商品失败' );
        }
        
        return ajax_arr( '添加商品成功' , 0 );
      }
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
      //不能更新商品
      if ( isset( $data['goods_id'] ) ) {
        unset( $data['goods_id'] );
      }
      
      $rows = $this->model->where( 'id' , $id )->update( $data );
      if ( $rows == 0 ) {
        return ajax_arr( "未更新任何数据" , 0 );
      }
      
      return ajax_arr( "更新成功" , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
}