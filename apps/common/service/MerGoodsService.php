<?php namespace apps\common\service;
/**
 * MerGoods Service
 *
 * @author  Zix
 * @version 2.0 2016-10-11
 */


class MerGoodsService extends BaseService {
  
  //引入 GridTable trait
  use \apps\common\traits\service\GridTable;
  
  public $currency = [
    'cny'    => '人民币' ,
    'points' => '积分'
  ];
  
  //状态
  public $status = [
    - 1 => '下架' ,
    0   => '缺货' ,
    1   => '上架' ,
  ];
  
  //类实例
  private static $instance;
  
  //生成类单例
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance        = new MerGoodsService();
      self::$instance->model = db( 'MerGoods' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'               => '' ,
      'mer_id'           => '' ,
      'pid'              => '0' ,
      'sort'             => '999' ,
      'sku'              => '' ,
      'name'             => '' ,
      'catalog_id'       => '' ,
      'highlight'        => '' ,
      'icon'             => '' ,
      'desc'             => '' ,
      'tags'             => '' ,
      'start_time'       => '' ,
      'end_time'         => '' ,
      'currency'         => 'cny' ,
      'price_market'     => '0.00' ,
      'price'            => '0.00' ,
      'points'           => '0' ,
      'status'           => - 1 ,
      'meta_title'       => '' ,
      'meta_keywords'    => '' ,
      'meta_description' => '' ,
      'recommend'        => '0' ,
      'hot'              => '0' ,
      'cheap'            => '0' ,
      'sales'            => '0' ,
      'comments'         => '0' ,
      'pv'               => '0' ,
      'created_at'       => date( 'Y-m-d H:i:s' ) ,
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
      'field'       => [ 'g.*' , 'gc.text catalog_text' , 'gc.type catalog_type' ] ,
      'ids'         => [] ,
      'keyword'     => '' ,
      'forSelect'   => '' ,
      'merId'       => '' ,
      'catalogId'   => '' ,
      'currency'    => '' ,
      'status'      => '' ,
      'page'        => 1 ,
      'pageSize'    => 10 ,
      'sort'        => 'id' ,
      'order'       => 'DESC' ,
      'count'       => FALSE ,
      'getAll'      => FALSE ,
      'withProfile' => FALSE
    ];
    
    $param = extend( $default , $param );
    
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'g.name' , 'like' , "%{$param['keyword']}%" );
    }
    
    if ( ! empty( $param['ids'] ) ) {
      $this->model->where( 'g.id' , 'in' , $param['ids'] );
    }
    
    if ( $param['merId'] !== '' ) {
      $this->model->where( 'g.mer_id' , $param['merId'] );
    }
    
    if ( $param['currency'] !== '' ) {
      $this->model->where( 'g.currency' , $param['currency'] );
    }
    
    if ( $param['catalogId'] !== '' ) {
      $this->model->where( 'g.catalog_id' , $param['catalogId'] );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'g.status' , $param['status'] );
    }
    
    $this->model->alias( 'g' );
    $this->model->join( 'mer_goods_catalog gc' , 'gc.id = g.catalog_id' , 'left' );
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    if ( $param['withProfile'] ) {
      $param['field'] = array_merge( $param['field'] , [ 'gp.package' , 'gp.content' ] );
      
      $this->model->join( 'mer_goods_profile gp' , 'gp.goods_id = g.id' , 'left' );
    }
    
    if ( $param['forSelect'] ) {
      $this->model->field( [ 'g.id' , 'g.name text' ] );
    } else {
      $this->model->field( $param['field'] );
    }
    
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "g.{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  /**
   * 批量更新
   *
   * @param $ids
   * @param $data
   *
   * @return array
   */
  public function updateByIds( $ids , $data ) {
    try {
      $rows = $this->model->where( 'id' , 'in' , $ids )->update( $data );
      if ( $rows <= 0 ) {
        return ajax_arr( '未更新任何数据' , 0 );
      }
      
      return ajax_arr( '更新成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  public function getByIds( $ids , $merId , $simpleField = FALSE ) {
    if ( $simpleField ) {
      $field = [
        "id" ,
        "mer_id" ,
        "sku" ,
        "name" ,
        "icon" ,
        "currency" ,
        "price" ,
        "points" ,
        "status"
      ];
    } else {
      $field = [ '*' ];
    }
    
    $this->model
      ->field( $field )
      ->where( 'id' , 'in' , $ids )
      ->where( 'price' , '>' , 0 )
      ->where( 'status' , 1 );
    
    if ( ! empty( $merId ) ) {
      $this->model->where( 'mer_id' , $merId );
    }
    
    $data = $this->model->select();
//    echo $this->model->getLastSql();
    
    //echo $this->model->_sql();
    return $data ? $data : [];
  }
  
  
  /**
   * 获取商品详情
   *
   * @param $id
   * @param string $from
   *
   * @return array
   */
  public function getDetailById( $id , $from = '' ) {
    $data = $this->model
      ->field( [ 'g.*' , 'gc.text catalog_text' , 'gc.type catalog_type' ] )
      ->alias( 'g' )
      ->join( 'mer_goods_catalog gc' , 'gc.id = g.catalog_id' , 'left' )
      ->where( 'g.id' , $id )
      ->find();
    
    if ( ! $data ) {
      return [];
    }
    
    $data['contentUri'] = full_uri( 'index/goods/detail_for_api' , [ "id" => $id ] );
    
    //查询商品图片
    $MerGoodsIcon = MerGoodsIconService::instance();
    $data['icon'] = $MerGoodsIcon->getByGoodsId( $id );
    
    $MerUserComments = MerUserCommentsService::instance();
    
    $data['commentsList'] = $MerUserComments->getByCond( [
      'type'     => 'goods' ,
      'typeId'   => $id ,
      'page'     => 1 ,
      'pageSize' => 6 ,
    ] );

//    print_arr( $data['commentsList'] );
    
    return $data;
  }
  
  public function incPv( $id ) {
    $this->model
      ->where( 'id' , $id )
      ->setInc( 'pv' );
  }
  
  public function incComments( $id ) {
    $this->model
      ->where( 'id' , $id )
      ->setInc( 'comments' );
  }
  
  public function incSales( $id ) {
    $this->model
      ->where( 'id' , $id )
      ->setInc( 'sales' );
  }
  
}