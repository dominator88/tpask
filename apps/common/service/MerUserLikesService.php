<?php namespace apps\common\service;
/**
 * MerUserLikes Service
 *
 * @author  Zix
 * @version 2.0 2016-09-27
 */


class MerUserLikesService extends BaseService {
  
  //引入 GridTable trait
  use \apps\common\traits\service\GridTable;
  
  public $type = [
    'article' => '文章' ,
    'goods'   => '商品' ,
    'event'   => '活动' ,
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
      self::$instance        = new MerUserLikesService();
      self::$instance->model = db( 'MerUserLikes' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'         => '' ,
      'user_id'    => '' ,
      'type'       => 'article' ,
      'type_id'    => '' ,
      'created_at' => '' ,
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
      'field'    => [ 'ul.*' ] ,
      'keyword'  => '' ,
      'status'   => '' ,
      'userId'   => '' ,
      'type'     => '' ,
      'typeId'   => '' ,
      'page'     => 1 ,
      'pageSize' => 10 ,
      'sort'     => 'id' ,
      'order'    => 'DESC' ,
      'count'    => FALSE ,
      'getAll'   => FALSE ,
      'withUser' => FALSE ,
    ];
    
    $param = extend( $default , $param );
    
    $this->model->alias( 'ul' );
    
    if ( $param['userId'] !== '' ) {
      $this->model->where( 'ul.user_id' , $param['userId'] );
    }
    
    if ( $param['type'] !== '' ) {
      $this->model->where( 'ul.type' , $param['type'] );
    }
    
    if ( $param['typeId'] !== '' ) {
      $this->model->where( 'ul.type_id' , $param['typeId'] );
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "ul.{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    if ( $param['withUser'] ) {
      $param['field'][] = 'u.nickname';
      $param['field'][] = 'u.phone';
      $param['field'][] = 'u.icon';
      $this->model->field( $param['field'] );
      $this->model->join( 'mer_user u' , 'u.id = ul.user_id' , 'left' );
    } else {
      $this->model->field( $param['field'] );
    }
    
    $data = $this->model->select();
    
    //print_r( $this->model->getLastSql() );
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  /**
   * 添加或取消 点赞
   *
   * @param $type
   * @param $typeId
   * @param $userId
   *
   * @return array
   */
  public function post( $type , $typeId , $userId ) {
    if ( empty( $userId ) ) {
      return ajax_arr( '请先登录' , 403 );
    }
    
    $oldData = $this->model
      ->where( 'user_id' , $userId )
      ->where( 'type' , $type )
      ->where( 'type_id' , $typeId )
      ->select();
    
    if ( ! empty( $oldData ) ) {
      return ajax_arr( '已经赞过了' , 500 );
    }
    
    //没有赞过
    $data = [
      'user_id' => $userId ,
      'type'    => $type ,
      'type_id' => $typeId
    ];
    
    $result = $this->insert( $data );
    
    if ( $result['code'] == 0 ) {
      $result['msg'] = '成功点赞';
      if ( $type == 'article' ) {
        //添加 article
        $MerArticles = MerArticlesService::instance();
        $MerArticles->incLikes( $typeId );
        
      } elseif ( $type == 'goods' ) {
        //
      } elseif ( $type == 'event' ) {
        
      }
    }
    
    return $result;
  }
  
  
  public function delete( $userId , $type , $typeId ) {
    try {
      
      $row = $this->model
        ->where( 'user_id' , $userId )
        ->where( 'type' , $type )
        ->where( 'type_id' , $typeId )
        ->delete();
      if ( $row <= 0 ) {
        throw new \Exception( '取消点赞失败' );
      }
      
      if ( $type == 'article' ) {
        //添加 article
        $MerArticles = MerArticlesService::instance();
        $MerArticles->decLikes( $typeId );
        
      } elseif ( $type == 'goods' ) {
        //
      } elseif ( $type == 'event' ) {
        
      }
      
      return ajax_arr( '取消点赞成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
    
  }
  
}