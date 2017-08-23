<?php
namespace apps\index\controller;

use apps\common\controller\IndexBase;


use apps\common\service\AskAnswersService;
use apps\common\service\AskQuestionsService;
use apps\common\service\AskUserCommentsService;
use apps\common\service\AskUserService;
use apps\common\service\MerUserFavoritesService;
use apps\common\service\AskUserLikesService;
use cebe\markdown\Markdown;
use think\Db;



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
    $this->_init('问题列表页');

      $this->data['initPageJs'] = false;
      $this->data['jsLib'][]  = 'static/js/index/QuestionsCreate.js';
      $this->data['jsCode']  = [
          'QuestionsCreate.init();'
      ];
      $this->_addCssLib( 'node_modules/simplemde/dist/simplemde.min.css' );
      $this->_addJsLib( 'node_modules/simplemde/dist/simplemde.min.js' );

    $AskQuestions = AskQuestionsService::instance();
    
    $param = [
      'catalogId'      => input( 'get.catalog' , '' ) ,
      'tag'            => input( 'get.tag' , '' ) ,
      'keyword'        => input( 'get.keyword' , '' ) ,
      'page'           => input( 'param.page' , 1 ) ,
      'withoutContent' => TRUE ,
        'pageSize'      => 5 ,
        'count'     => TRUE
    ];



      $count = $AskQuestions->getByCond( $param );
      unset($param['count']);
      $list  = $AskQuestions->getPaginatorByCond( $param ,$count);
        $this->_addData('list',$list);

      return $this->_displayWithLayout( 'index' );
   // return json( ajax_arr( '查询成功' , 0 , $response ) );
  }

  public function create($id = 0){
      if(!$this->userId){
        return  redirect('/auth/signin');
      }

      $method = strtolower(request()->method());
      switch($method){
          case 'get' :
              $this->_init('创建问题');
              $this->_addParam('uri' , [ 'questioncreate' => full_uri( "index/question/create/{$id}") ,'questioncategory' => full_uri( 'index/category/index')]);
              if($id){
                  $AskQuestions = AskQuestionsService::instance();
                  $data = $AskQuestions->getById($id);
                  $this->_addData('data',$data);
              }
              $this->data['initPageJs'] = false;
              $this->data['jsLib'][]  = 'static/js/index/QuestionsCreate.js';
              $this->data['jsCode']  = [
                  'QuestionsCreate.init();'
              ];
              $this->_addCssLib( 'node_modules/simplemde/dist/simplemde.min.css' );
              $this->_addJsLib( 'node_modules/simplemde/dist/simplemde.min.js' );
              //悬赏
              $price_arr = [ 3 , 5 , 8 , 10 , 20 , 30 ,50];
              $this->_addParam('prices' , $price_arr);
              return $this->_displayWithLayout('create');
             break;
          case 'post' :
              $title = input('post.title' , '' , 'trim');
              $content = input('post.content' , '' , 'trim');
              $catalog_id = input('post.category' ,'' , 'trim');
                $price = input('post.price' ,0 , 'intval');
              $hide = input('post.hide' ,0);
              $id = input('param.id' , 0 ,'intval');

              $data = [
                  'title' => $title,
                  'content' => $content ,
                  'userId'  => $this->userId ,
                  'catalog_id' => $catalog_id ,
                  'price' => $price ,
                  'hide'  => $hide ,
                  'status' => 1 ,
                  'created_at' => date('Y-m-d H:i:s' , time()) ,
                  'updated_at' => date('Y-m-d H:i:s' , time()) ,

              ];
              $AskQuestions = AskQuestionsService::instance();
              if($id){

                  $result = $AskQuestions->update($id , $data);
              }else{
                  $result = $AskQuestions->insert($data);
              }
              break;


      }

      return $result;
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
        $AskAnswers = AskAnswersService::instance();
    //取文章详情
    $data = $AskQuestions->getDetailById( $id );
      //取最佳答案
      $answer = $AskAnswers->getById($data['adopt']);

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
        'pricearr' => "question/pricearr",
        'adopt'     => "question/adopt/$id",
      'comments'  => "/question/comments/$id" ,
        'addPrice' => "/question/addPrice/$id",
      'likes'     => "/question/likes/$id" ,
      'favorites' => "/question/favorites/$id"
    ] );
    $data['from']    = input( 'get.from' , 'web' );
    $data['content'] = $this->_parseMarkdown( $data['content'] );
    $this->_addData( 'data' , $data );
    $this->_addData('answer' , $answer);
      $this->_addData('userData' , $this->userData);
    if ( $data['from'] == 'api' ) {
      return ajax_arr( '查询成功' , 0 ,   $data  );
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
                'status' => 1 ,
                'adopt' => 0,
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
  public function comments($type , $rec_id ) {

    $method = strtolower( request()->method() );

    $AskUserCommentsService = AskUserCommentsService::instance();
    
    switch ( $method ) {
      case 'get' :
        //取文章评论
        $data = $AskUserCommentsService->getByCond( [
          'typeId'   => $rec_id ,
            'type' => $type ,
          'page'     => input( 'get.page' , 1 ) ,
          'pageSize' => input( 'get.pageSize' , 6 ) ,
          'statusGT' => - 1 ,
          'sort'     => 'id' ,
          'order'    => 'desc'
        ] );
       //  echo $AskUserCommentsService->model->getLastSql();
        return ajax_arr( '查询成功' , 0 , [ 'rows' => $data ] );
      case 'post' :
        //发表文章评论
        $content = input( 'post.content' , '' , 'trim' );
        $type  =  input ('param.type' ,'' , 'trim');

        $result  = $AskUserCommentsService->post( $type , $rec_id , $this->userId , $content );
        
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

  /**
   * 采纳为正确答案
     *
     * @param $id
    *
    * * @return array
   */
  public function adopt( $id , $rec_id){
      $method = strtolower( request()->method() );
        $AskQuestions = AskQuestionsService::instance();
      switch($method){
          case 'post' :
            $result = $AskQuestions->adopt($id , $rec_id);
            return $result;
              break;
          default :
              return ajax_arr( '未知请求' , 500 );
      }
  }

    /**
     * 追加悬赏
     * @param $id
     * @return array
     */
  public function addPrice($id){
      $method = strtolower( request()->method() );
      $AskQuestions = AskQuestionsService::instance();
      if($method == 'post'){
          $price = input('param.price');
          $AskUser = AskUserService::instance();
          Db::startTrans();
          try{
               $AskUser->decPrice($this->userId ,  $price);
              $AskQuestions->addPrice($id , $price);
              Db::commit();
          }catch (\Exception $e){
              Db::rollback();
              return ajax_arr( $e->getMessage() , $e->getCode());
          }
          return ajax_arr('追加悬赏成功' , 0);

      }
      return ajax_arr( '未知请求' , 500 );

  }

    /**
     * 悬赏金币列表
     * @return array
     */
  public function price()
  {

      $method = strtolower(request()->method());

      $AskUser = AskUserService::instance();
      $user = $AskUser->getById($this->userId);
      if(empty($user)){
          return ajax_arr('用户未登陆!', 500);
      }

      switch ($method) {
          case 'get' :
              $price_arr = [3, 5, 8, 10, 20, 30, 50];
              return ajax_arr('查询成功', 0, $price_arr);
          default :
              return ajax_arr('未知请求', 500);
      }
  }



}
