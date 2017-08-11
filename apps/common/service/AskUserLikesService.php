<?php namespace apps\common\service;
/**
 * AskUserLikes Service
 *
 * @author Zix
 * @version 2.0 2017-08-09
 */



class AskUserLikesService extends BaseService {

  	//引入 GridTable trait
	use \apps\common\traits\service\GridTable;
  

  //状态
	public $status = [
		0 => '禁用',
		1 => '启用',
	];

  //类实例
	private static $instance;

	//生成类单例
	public static function instance() {
		if ( self::$instance == NULL ) {
			self::$instance = new AskUserLikesService();
			self::$instance->model = db('AskUserLikes');
		}
		return self::$instance;
	}

  //取默认值
	function getDefaultRow() {
		return [
			'id' => '' , 
'user_id' => '' , 
'type' => 'article' , 
'type_id' => '' , 
'created_at' => date('Y-m-d H:i:s') , 
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
    'field'    => [ ],
    'keyword'  => '',
    'status'   => '',
    'page'     => 1,
    'pageSize' => 10,
    'sort'     => 'id',
    'order'    => 'DESC',
    'count'    => FALSE,
    'getAll'   => FALSE
  ];

  $param = extend( $default, $param );

  if ( ! empty( $param['keyword'] ) ) {
    $this->model->where('name' , 'like' , "%{$param['keyword']}%" );
  }

  if ( $param['status'] !== '' ) {
    $this->model->where( 'status' , $param['status'] );
  }

  if ( $param['count'] ) {
    return $this->model->count();
  }

  $this->model->field( $param['field'] );

  if ( ! $param['getAll'] ) {
    $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'], $param['pageSize'] );
  }

  $order[] = "{$param['sort']} {$param['order']}";
  $this->model->order( $order );

  $data = $this->model->select();
  //echo $this->model->getLastSql();

  return $data ? $data : [ ];
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
                $AskArticles = AskArticlesService::instance();
                $AskArticles->incLikes( $typeId );

            } elseif ( $type == 'goods' ) {
                //
            } elseif ( $type == 'event' ) {

            }elseif( $type == 'question' ){
                $AskQuestions = AskQuestionsService::instance();
                $AskQuestions->incLikes( $typeId );
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