<?php
namespace apps\index\controller;

use apps\common\controller\IndexBase;

use apps\common\service\AskArticlesService;
use apps\common\service\AskEmailService;
use apps\common\service\MerArticlesService;
use apps\common\service\AskUserCommentsService;
use apps\common\service\MerUserFavoritesService;
use apps\common\service\MerUserLikesService;
use cebe\markdown\Markdown;

use think\Db;

class Articles extends IndexBase {
  
  public function __construct() {
    parent::__construct();

    $this->_initClassName( __CLASS__ );
  }
  
  /**
   * 文章列表
   *
   * @return \think\response\Json
   */
  public function index() {
    $this->_init('文章列表页');

    $AskArticles = AskArticlesService::instance();
    
    $param = [
      'catalogId'      => input( 'get.catalog' , '' ) ,
      'tag'            => input( 'get.tag' , '' ) ,
      'keyword'        => input( 'get.keyword' , '' ) ,
      'page'           => input( 'get.page' , 1 ) ,
      'withoutContent' => TRUE ,
        'pageSize'      => 2 ,
        'count'     => TRUE
    ];
      $this->_addParam('uri',[
          'base' => '',
      ]);


      $count = $AskArticles->getByCond( $param );
      unset($param['count']);
      $list  = $AskArticles->getPaginatorByCond( $param ,$count);
        $this->_addData('list',$list);

      return $this->_displayWithLayout( 'index' );
   // return json( ajax_arr( '查询成功' , 0 , $response ) );
  }
  
  /**
   * 文章详情
   *
   * @param $id
   *
   * @return \think\response\View
   */
  public function detail( $id ) {

    $MerArticles = MerArticlesService::instance();
    
    //取文章详情
    $data = $MerArticles->getById( $id );
    if ( empty( $data ) ) {
      game_over( '文章未知道' );
    }
    
    //添加文章PV
    $MerArticles->incPv( $id );
    $data['pv'] ++;
    
    $this->_init( $data['title'] );
    $this->_addParam( 'uri' , [
      'this'      => "/article/$id" ,
      'comments'  => "/article/comments/$id" ,
      'likes'     => "/article/likes/$id" ,
      'favorites' => "/article/favorites/$id"
    ] );
    $data['from']    = input( 'get.from' , 'web' );
    $data['content'] = $this->_parseMarkdown( $data['content'] );
    $this->_addData( 'data' , $data );

    if ( $data['from'] == 'api' ) {
      return view( 'api' , $this->data );
    }
    
    //print_arr( $this->data );
    $this->_addCssLib( 'node_modules/animate.css/animate.min.css' );
    
    return $this->_displayWithLayout( 'detail' );
  }
  
  /**
   * 解析 markdown
   *
   * @param $content
   *
   * @return string
   */
  private function _parseMarkdown( $content ) {
    $parser = new Markdown();
    
    return $parser->parse( $content );
  }
  
  /**
   * 评论相关
   *
   * @param $id
   *
   * @return array
   */
  public function comments( $id ) {
    $method = strtolower( request()->method() );
    
    $AskUserComments = AskUserCommentsService::instance();
    
    switch ( $method ) {
      case 'get' :
        //取文章评论
        $data = $AskUserComments->getByCond( [
          'type'     => 'article' ,
          'typeId'   => $id ,
          'page'     => input( 'get.page' , 1 ) ,
          'pageSize' => input( 'get.pageSize' , 6 ) ,
          'statusGT' => - 1 ,
          'sort'     => 'id' ,
          'order'    => 'desc'
        ] );
        
        return ajax_arr( '查询成功' , 0 , [ 'rows' => $data ] );
      case 'post' :
        //发表文章评论
        $content = input( 'post.content' , '' , 'trim' );
        $result  = $AskUserComments->post( 'article' , $id , $this->userId , $content );
        
        return $result;
      default :
        return ajax_arr( '未知请求' , 500 );
    }
  }
  
  /**
   * 点赞相关
   *
   * @param $id
   *
   * @return array
   */
  public function likes( $id ) {
    $method       = strtolower( request()->method() );
    $MerUserLikes = MerUserLikesService::instance();
    
    switch ( $method ) {
      case 'post' :
        //发表文章评论
        $result = $MerUserLikes->post( 'article' , $id , $this->userId );
        
        return $result;
      default :
        return ajax_arr( '未知请求' , 500 );
    }
  }
  
  /**
   * 点赞相关
   *
   * @param $id
   *
   * @return array
   */
  public function favorites( $id ) {
    $method           = strtolower( request()->method() );
    $MerUserFavorites = MerUserFavoritesService::instance();
    
    switch ( $method ) {
      case 'post' :
        //发表文章评论
        $result = $MerUserFavorites->post( 'article' , $id , $this->userId );
        
        return $result;
      default :
        return ajax_arr( '未知请求' , 500 );
    }
  }


}
