<?php namespace apps\common\service;
/**
 * AskArticles Service
 *
 * @author  Zix
 * @version 2.0 2016-09-14
 */


class AskArticlesService extends BaseService {

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
            self::$instance        = new AskArticlesService();
            self::$instance->model = db( 'AskArticles' );
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

    /**
     * @param $id
     */
    public function incPv( $id ) {
        $this->model->where( 'id' , $id )->setInc( 'pv' );
    }

    public function decPv( $id ) {
        $this->model->where( 'id' , $id )->setDec( 'pv' );
    }
}