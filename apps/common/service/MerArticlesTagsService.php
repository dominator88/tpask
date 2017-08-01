<?php namespace apps\common\service;
/**
 * MerArticlesTags Service
 *
 * @author  Zix
 * @version 2.0 2016-09-23
 */


class MerArticlesTagsService extends BaseService {
  
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
      self::$instance        = new MerArticlesTagsService();
      self::$instance->model = db( 'MerArticlesTags' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'         => '' ,
      'tags_id'    => '' ,
      'article_id' => '' ,
      'created_at' => date( 'Y-m-d H:i:s' ) ,
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
    
    $order[] = "{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  /**
   * 添加文章标签
   *
   * @param $merId
   * @param $articleId
   * @param $tags
   *
   * @return bool
   */
  public function addArticleTags( $merId , $articleId , $tags ) {
    $MerTags = MerTagsService::instance();
    
    //查询已有的 tag id
    $oldData = $this->model
      ->where( 'article_id' , $articleId )
      ->select();
    
    $oldTags = [];
    foreach ( $oldData as $item ) {
      $oldTags[] = $item['tag_id'];
    }
    
    if ( empty( trim( $tags ) ) ) {
      if ( empty( $oldTags ) ) {
        return TRUE;
      } else {
        $this->model
          ->where( 'article_id' , $articleId )
          ->delete();
        
        $MerTags->setCountByIds( $oldTags , 'dec' );
        
        return TRUE;
      }
      
    }
    
    $tagsArr = explode( ' ' , trim( $tags ) );
    foreach ( $tagsArr as &$t ) {
      $t = trim( $t );
    }
    
    //查询tag id
    $tagIds = $MerTags->getIdByTypeAndTags( $merId , 'article' , $tagsArr );
    
    //比较 看是否有删除 或 添加
    $needDelete = array_diff( $oldTags , $tagIds );
    $needAdd    = array_diff( $tagIds , $oldTags );
    
    //如果有删除
    if ( ! empty( $needDelete ) ) {
      $this->model
        ->where( 'article_id' , $articleId )
        ->where( 'tag_id' , 'in' , $needDelete )
        ->delete();
      
      $MerTags->setCountByIds( $needDelete , 'dec' );
    }
    
    //如果有添加
    if ( ! empty( $needAdd ) ) {
      $newData = [];
      foreach ( $needAdd as $tagId ) {
        $newData[] = [
          'mer_id'     => $merId ,
          'article_id' => $articleId ,
          'tag_id'     => $tagId ,
        ];
      }
      $this->model->insertAll( $newData );
      $MerTags->setCountByIds( $needAdd , 'inc' );
    }
    
    return TRUE;
  }
  
}