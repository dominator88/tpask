<?php namespace apps\common\service;
use think\Request;

/**
 * AskQuestions Service
 *
 * @author  Zix
 * @version 2.0 2016-09-14
 */


class AskQuestionsService extends BaseService {

    //引入 GridTable trait
    use \apps\common\traits\service\GridTable;


    //状态
    public $status = [
        0 => '未审核' ,
        1 => '待解决' ,
        2 => '已解决' ,
    ];

    //类实例
    private static $instance;

    //生成类单例
    public static function instance() {
        if ( self::$instance == NULL ) {
            self::$instance        = new AskQuestionsService();
            self::$instance->model = db( 'AskQuestions' );
        }

        return self::$instance;
    }

    //取默认值
    function getDefaultRow() {
        return [
            'id'         => '' ,
            'sort'       => 999 ,
            'title'      => '' ,
            'tag'        => '' ,
            'desc'       => '' ,
            'content'    => '' ,
            'status'     => '1' ,
            'start_at'   => '' ,
            'end_at'     => '' ,
            'comments'   => 0 ,
            'likes'      => 0 ,
            'pv'         => 0 ,
            'created_at' => date( 'Y-m-d H:i:s' ) ,
            'updated_at' => date( 'Y-m-d H:i:s' ) ,
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
            'field'          => [ 'a.*' , 'ac.text catalog_text' , 'ac.icon catalog_icon' ] ,
            'keyword'        => '' ,
            'tag'            => '' ,
            'status'         => '' ,
            'catalogId'      => '' ,
            'page'           => 1 ,
            'pageSize'       => 10 ,
            'sort'           => 'id' ,
            'order'          => 'DESC' ,
            'count'          => FALSE ,
            'getAll'         => FALSE ,
            'withoutContent' => FALSE ,
        ];

        $param = extend( $default , $param );

        $this->model->alias( 'a' );

        if ( ! empty( $param['keyword'] ) ) {
            $this->model->where( 'a.title' , 'like' , "%{$param['keyword']}%" );
        }

        if ( ! empty( $param['tag'] ) ) {
            $this->model->where( 'a.tags' , 'like' , "%{$param['tag']}%" );
        }


        if ( $param['catalogId'] !== '' ) {
            $MerArticlesCatalog = MerArticlesCatalogService::instance();
            $catalog            = $MerArticlesCatalog->getFamilyId( [ 'pid' => intval( $param['catalogId'] ) ] );

            $this->model->where( 'a.catalog_id' , 'in' , $catalog );
        }

        if ( $param['status'] !== '' ) {
            $this->model->where( 'a.status' , $param['status'] );
        }


        $this->model->join( 'ask_category ac' , 'ac.id = a.catalog_id' );

        if ( $param['count'] ) {
            return $this->model->count();
        }

        $this->model->field( $param['field'] );

        if ( ! $param['getAll'] ) {
            $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
        }

        $order[] = "a.{$param['sort']} {$param['order']}";
        $this->model->order( $order );

        $data = $this->model->select();

        if ( $param['withoutContent'] ) {
            foreach ( $data as &$item ) {
                unset( $item['content'] );
            }
        }

//		echo $this->model->getLastSql();

        return $data;
    }

    public function getPaginatorByCond($param , $count){
        $default = [
            'field'          => [ 'a.*' , 'ac.text catalog_text' , 'ac.icon catalog_icon' ] ,
            'keyword'        => '' ,
            'tag'            => '' ,
            'status'         => '' ,
            'catalogId'      => '' ,
            'page'           => 1 ,
            'pageSize'       => 10 ,
            'sort'           => 'id' ,
            'order'          => 'DESC' ,
            'count'          => FALSE ,
            'getAll'         => FALSE ,
            'withoutContent' => FALSE ,
        ];
        $param = extend( $default , $param );

        $this->model->alias( 'a' );

        if ( ! empty( $param['keyword'] ) ) {
            $this->model->where( 'a.title' , 'like' , "%{$param['keyword']}%" );
        }

        if ( ! empty( $param['tag'] ) ) {
            $this->model->where( 'a.tags' , 'like' , "%{$param['tag']}%" );
        }


        if ( $param['catalogId'] !== '' ) {
            $Askcategory = AskcategoryService::instance();
            $catalog            = $Askcategory->getFamilyId( [ 'pid' => intval( $param['catalogId'] ) ] );

            $this->model->where( 'a.catalog_id' , 'in' , $catalog );
        }

        if ( $param['status'] !== '' ) {
            $this->model->where( 'a.status' , $param['status'] );
        }


        $this->model->join( 'ask_category ac' , 'ac.id = a.catalog_id' );

        if ( $param['count'] ) {
            return $this->model->count();
        }

        $this->model->field( $param['field'] );


        $data = $this->model->paginate($param['pageSize'] ,$count,['var_page' => 'page','path' => 'question/index/[PAGE]' , 'page' => $param['page']]);

        $order[] = "a.{$param['sort']} {$param['order']}";
        $this->model->order( $order );



        if ( $param['withoutContent'] ) {
            foreach ( $data as &$item ) {
                unset( $item['content'] );
            }
        }

//		echo $this->model->getLastSql();

        return $data;

    }

    /**
     * 取数据 和 包含用户收藏信息
     *
     * @param $id
     * @param string $userId
     *
     * @return array
     */
    public function getByIdWithMerAndUser( $id  , $userId = '' ) {
        $this->model
            ->alias( 'a' )
            ->where( 'a.id' , $id );
        if ( ! empty( $userId ) ) {
            $this->model->field( [
                    'a.*' ,
                    'ifnull( uf.user_id , 0 ) as is_favorite' ,
                    'ifnull( ul.user_id , 0 ) as is_like'
                ]
            );
            $this->model->join(
                'mer_user_favorites uf' ,
                'uf.type_id = a.id and uf.type="article" and uf.user_id = ' . $userId ,
                'left'
            );

            $this->model->join(
                'mer_user_likes ul' ,
                'ul.type_id = a.id and ul.type="article" and ul.user_id = ' . $userId ,
                'left'
            );
        } else {
            $this->model->field( [ 'a.*' , '0 is_favorite' , '0 is_like' ] );
        }

        $data = $this->model->find();

//    echo $this->model->getLastSql();

        return $data ? $data : [];
    }

    public function withComments( $data ) {
        $ids = [];
        foreach ( $data as $item ) {
            $ids[] = $item['id'];
        }

        $comments = db( 'mer_user_comments' )
            ->where( 'type_id' , 'in' , $ids )
            ->where( 'type' , 'article' )
            ->where( 'status' , 1 )
            ->limit( 6 )
            ->order( 'id DESC' );

        $commentsData = [];
        foreach ( $comments as $item ) {
            $commentsData[ $item['type_id'] ][] = $item;
        }

        foreach ( $data as &$item ) {
            if ( isset( $commentsData[ $item['id'] ] ) ) {
                $item['comments'] = $commentsData[ $item['id'] ];
            } else {
                $item['comments'] = [];
            }
        }

        return $data;
    }

    /**
     * 增加 评论数
     *
     * @param $id
     */
    public function incComments( $id ) {
        $this->model->where( 'id' , $id )->setInc( 'comments' );
    }

    public function decComments( $id ) {
        $this->model->where( 'id' , $id )->setDec( 'comments' );
    }

    /**
     * 增加
     *
     * @param $id
     */
    public function incLikes( $id ) {
        $this->model->where( 'id' , $id )->setInc( 'likes' );
    }

    public function decLikes( $id ) {
        $this->model->where( 'id' , $id )->setDec( 'likes' );
    }

    public function incAnswers($id){
        $this->model->where( 'id' , $id )->setInc( 'answers' );
    }

    public function decAnswers($id){
        $this->model->where( 'id' , $id )->setDec( 'answers' );
    }

    /**
     * @param $id
     */
    public function incPv( $id ) {
        $this->model->where( 'id' , $id )->setInc( 'pv' );
    }

    public function decPv( $id ) {
        $this->model->where( 'id' , $id )->setDec( 'pv' );
    }


    public function getDetailById($id){
        $param = [
            'field' => ['q.*' ,'u.nickname'],
        ];
        $this->model->alias('q');
        $this->model->where('q.id' , $id);
        $this->model->field($param['field'])->join('ask_user u' , 'q.userId=u.id' , 'left');
        $data = $this->model->find();

        return $data;
    }

    public function  adopt($id , $rec_id ){

  

        $flag = $this->model->where('id' ,$id)->update(['adopt' => $rec_id , 'status' => 2]);
        $AskAnswersService = AskAnswersService::instance();
        if($flag){
            $AskAnswersService->setAdopt($rec_id);
        }
        if(! $flag){
            return ajax_arr('采纳失败' , 500);
        }
        return ajax_arr('采纳成功' , 0);
    }

    public function addPrice($id , $price){

        $flag =  $this->model->where('id' , $id)->setInc('price' ,$price);
        if( !$flag ){
            exception('追加悬赏失败' , 500);
        }
        return $flag;
    }
}