<?php namespace apps\common\service;
/**
 * AskUserComments Service
 *
 * @author  Zix
 * @version 2.0 2016-09-22
 */


class AskUserCommentsService extends BaseService {
  
  //引入 GridTable trait
  use \apps\common\traits\service\GridTable;
  
  
  public $type = [
    'article' => '文章' ,
    'answer'   => '商品' ,
    'question'   => '活动'
  ];
  
  //状态
  public $status = [
    - 1 => '未审核通过' ,
    0   => '未审核' ,
    1   => '审核通过' ,
  ];
  
  //类实例
  private static $instance;
  
  //生成类单例
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance        = new AskUserCommentsService();
      self::$instance->model = db( 'AskUserComments' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'          => '' ,
      'type'        => 'article' ,
      'type_id'     => '' ,
      'user_id'     => '' ,
      'content'     => '' ,
      'status'      => '0' ,
      'create_time' => date( 'Y-m-d H:i:s' ) ,
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
      'field'    => [ 'c.*' , 'u.nickname' , 'u.phone' , 'u.icon' ] ,
      'keyword'  => '' ,
      'status'   => '' ,
      'statusGT' => '' ,
      'type'     => '' ,
      'typeId'   => '' ,
      'userId'   => '' ,
      'page'     => 1 ,
      'pageSize' => 10 ,
      'sort'     => 'id' ,
      'order'    => 'DESC' ,
      'count'    => FALSE ,
      'getAll'   => FALSE ,
    ];
    
    $param = extend( $default , $param );
    $this->model->alias( 'c' );
    
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'c.content' , 'like' , "%{$param['keyword']}%" );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'c.status' , $param['status'] );
    }
    
    if ( $param['statusGT'] !== '' ) {
      $this->model->where( 'c.status' , 'GT' , $param['statusGT'] );
    }
    
    if ( $param['type'] !== '' ) {
      $this->model->where( 'c.type' , $param['type'] );
    }
    
    if ( $param['typeId'] !== '' ) {
      $this->model->where( 'c.type_id' , $param['typeId'] );
    }
    
    if ( $param['userId'] !== '' ) {
      $this->model->where( 'c.user_id' , $param['userId'] );
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    $this->model
      ->field( $param['field'] )
      ->join( 'ask_user u' , 'u.id = c.user_id' , 'left' );
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "c.{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data;
  }
  
  /**
   * 设置评论状态
   *
   * @param $id
   * @param $status
   *
   * @return array
   */
  public function setStatus( $id , $status ) {
    
    $result = $this->update( $id , [
      'status' => $status
    ] );
    
    if ( $result['code'] == 0 ) {
      if ( $status == 0 ) {
        return ajax_arr( '取消审核成功' , 0 );
      }
      
      return ajax_arr( '审核成功' , 0 );
    }
    
    return $result;
  }
  
  /**
   * 发表评论
   *
   * @param $type
   * @param $typeId
   * @param $userId
   * @param $content
   *
   * @return array
   */
  public function post( $type , $typeId , $userId , $content ) {
    
    if ( empty( $typeId ) ) {
      return ajax_arr( '请填写文章ID' , 500 );
    }
    
    if ( empty( $userId ) ) {
      return ajax_arr( '请先登录' , 500 );
    }
    
    if ( empty( $content ) ) {
      return ajax_arr( '请填写评论内容' , 500 );
    }
    
    $oldData = $this->model
      ->where( 'type' , $type )
      ->where( 'type_id' , $typeId )
      ->where( 'user_id' , $userId )
      ->select();
    
    if ( ! empty( $oldData ) ) {
      return ajax_arr( '已经评论过了' , 500 );
    }
    
    
    $data = [
      'user_id' => $userId ,
      'type'    => $type ,
      'type_id' => $typeId ,
      'content' => $content
    ];
    
    $result = $this->insert( $data );
    
    if ( $result['code'] == 0 ) {
      if ( $type == 'article' ) {
        //添加 article
        $AskArticles = AskArticlesService::instance();
          $AskArticles->incComments( $typeId );
        
      } elseif ( $type == 'question' ) {
          $AskQuestions = AskQuestionsService::instance();
          $AskQuestions->incComments( $typeId );
      } elseif ( $type == 'answer' ) {
          $AskAnswers = AskAnswersService::instance();
          $AskAnswers->incComments( $typeId );
      }
    }
    
    return $result;
  }
  
}