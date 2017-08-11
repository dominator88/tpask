<?php
namespace apps\index\controller;

use apps\common\controller\IndexBase;


use apps\common\service\AskAnswersService;
use apps\common\service\AskQuestionsService;
use apps\common\service\AskUserCommentsService;
use apps\common\service\MerUserFavoritesService;
use apps\common\service\AskUserLikesService;
use cebe\markdown\Markdown;



class Questions extends IndexBase {
  
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

    $AskQuestions = AskQuestionsService::instance();
    
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


      $count = $AskQuestions->getByCond( $param );
      unset($param['count']);
      $list  = $AskQuestions->getPaginatorByCond( $param ,$count);
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

      $AskQuestions = AskQuestionsService::instance();
    
    //取文章详情
    $data = $AskQuestions->getDetailById( $id );
//echo $id;
    if ( empty( $data ) ) {
      game_over( '文章未知道' );
    }
    
    //添加文章PV
      $AskQuestions->incPv( $id );
    $data['pv'] ++;
    
    $this->_init( $data['title'] );
    $this->_addParam( 'uri' , [
      'this'      => "/question/$id" ,
        'answers'   => "/question/answers/$id",
        'answercomments'   => "question/comments/answers/$id",
      'comments'  => "/question/comments/$id" ,

      'likes'     => "/question/likes/$id" ,
      'favorites' => "/question/favorites/$id"
    ] );
    $data['from']    = input( 'get.from' , 'web' );
    $data['content'] = $this->_parseMarkdown( $data['content'] );
    $this->_addData( 'data' , $data );

    if ( $data['from'] == 'api' ) {
      return view( 'api' , $this->data );
    }
    
    //print_arr( $this->data );
  //  $this->_addCssLib( 'node_modules/animate.css/animate.min.css' );
      $this->_addCssLib( 'node_modules/simplemde/dist/simplemde.min.css' );
      $this->_addJsLib( 'node_modules/simplemde/dist/simplemde.min.js' );
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



  public function answers($id){
      $method = strtolower(request()->method());
      $AskAnswersService = AskAnswersService::instance();
      switch($method){
        case 'get':
            $data = $AskAnswersService->getByCond([
                'qid'   => $id,
                'page'     => input( 'get.page' , 1 ) ,
                'pageSize' => input( 'get.pageSize' , 6 ) ,
                'statusGT' => - 1 ,
                'sort'     => 'id' ,
                'order'    => 'desc'
            ]);
            return ajax_arr( '查询成功' , 0 , [ 'rows' => $data ] );
            break;
          case 'post':
              //发表文章评论
              $content = input( 'post.content' , '' , 'trim' );

              $result  = $AskAnswersService->post( $id , $this->userId , $content );

              return $result;
              break;
      }
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
                $MerArticles = MerArticlesService::instance();
                $MerArticles->incComments( $typeId );

            } elseif ( $type == 'goods' ) {
                //
            } elseif ( $type == 'event' ) {

            }
        }

        return $result;
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

    $AskUserCommentsService = AskUserCommentsService::instance();
    
    switch ( $method ) {
      case 'get' :
        //取文章评论
        $data = $AskUserCommentsService->getByCond( [
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
        $type  =  input ('param.type' ,'' , 'trim');

        $result  = $AskUserCommentsService->post( $type , $id , $this->userId , $content );
        
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
    $AskUserLikes = AskUserLikesService::instance();
    
    switch ( $method ) {
      case 'post' :
        //发表文章评论
        $result = $AskUserLikes->post( 'question' , $id , $this->userId );
        
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
