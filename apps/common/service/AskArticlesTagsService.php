<?php namespace apps\common\service;
/**
 * AskArticlesTags Service
 *
 * @author  Zix
 * @version 2.0 2016-09-23
 */


class AskArticlesTagsService extends BaseService {
  
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
      self::$instance        = new AskArticlesTagsService();
      self::$instance->model = db( 'AskArticlesTags' );
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
  
  public function getIdByTypeAndTags( $type , $tags ) {
    //取数据
    $data = $this->model
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


    /**
     * 添加文章标签
     *
     * @param $articleId
     * @param $tags
     *
     * @return bool
     */
    public function addArticleTags( $articleId , $tags ) {
        $AskTags = AskTagsService::instance();

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

                $AskTags->setCountByIds( $oldTags , 'dec' );

                return TRUE;
            }

        }

        $tagsArr = explode( ' ' , trim( $tags ) );
        foreach ( $tagsArr as &$t ) {
            $t = trim( $t );
        }

        //查询tag id
        $tagIds = $AskTags->getIdByTypeAndTags(  'article' , $tagsArr );

        //比较 看是否有删除 或 添加
        $needDelete = array_diff( $oldTags , $tagIds );
        $needAdd    = array_diff( $tagIds , $oldTags );

        //如果有删除
        if ( ! empty( $needDelete ) ) {
            $this->model
                ->where( 'article_id' , $articleId )
                ->where( 'tag_id' , 'in' , $needDelete )
                ->delete();

            $AskTags->setCountByIds( $needDelete , 'dec' );
        }

        //如果有添加
        if ( ! empty( $needAdd ) ) {
            $newData = [];
            foreach ( $needAdd as $tagId ) {
                $newData[] = [
                    'article_id' => $articleId ,
                    'tag_id'     => $tagId ,
                ];
            }

            $this->model->insertAll( $newData );
            $AskTags->setCountByIds( $needAdd , 'inc' );
        }

        return TRUE;
    }
  
}