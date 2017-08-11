<?php namespace apps\common\service;
/**
 * AskUserFavorites Service
 *
 * @author Zix
 * @version 2.0 2017-08-09
 */



class AskUserFavoritesService extends BaseService {

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
			self::$instance = new AskUserFavoritesService();
			self::$instance->model = db('AskUserFavorites');
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

    public function getByTypeAndIdWithUser( $type, $typeId, $userId ) {
        $data = $this->model
            ->where( 'type', $type )
            ->where( 'type_id', $typeId )
            ->where( 'user_id', $userId )
            ->find();

        return $data ? $data : [];
    }

    /**
     * 添加或取消收藏
     *
     * @param $type
     * @param $typeId
     * @param $userId
     *
     * @return array
     */
    public function post( $type, $typeId, $userId ) {
        $oldData = $this->getByTypeAndIdWithUser( $type, $typeId, $userId );
        if ( ! empty( $oldData ) ) {
            //删除
            $result = $this->destroy( $oldData['id'] );
            if ( $result['code'] == 0 ) {
                return ajax_arr( '已取消收藏', 0 );
            }
        } else {
            //insert
            $newData = [
                'user_id' => $userId,
                'type'    => $type,
                'type_id' => $typeId,
            ];
            $result  = $this->insert( $newData );
            if ( $result['code'] == 0 ) {
                return ajax_arr( '收藏成功', 0 );
            }
        }

        return $result;
    }

    public function getByUserWithType( $params ) {
        $default = [
            'userId'   => '',
            'type'     => 'article',
            'page'     => 1,
            'pageSize' => 10,
        ];

        $params = extend( $default, $params );

        if ( $params['type'] == 'article' ) {

            return $this->getArticleByUser( $params );
        } elseif ( $params[ 'type' == 'event' ] ) {
            return $this->getEventByUser( $params );
        } elseif ( $params['type'] == 'goods' ) {
            return $this->getGoodsByUser( $params );
        }

        return [];

    }

    /**
     * 取收藏的文章
     *
     * @param $params
     *
     * @return mixed
     */
    private function getArticleByUser( $params ) {
        $field = [
            'a.id',
            'a.title',
            'a.desc',
            'a.icon',
            'a.created_at',
            'uf.id fav_id',
            'uf.created_at fav_created_at'
        ];

        return $this->model
            ->alias( 'uf' )
            ->field( $field )
            ->where( 'uf.user_id', $params['userId'] )
            ->where( 'uf.type', 'article' )
            ->join( 'mer_articles a', 'a.id = uf.type_id', 'left' )
            ->limit( ( $params['page'] - 1 ) * $params['pageSize'], $params['pageSize'] )
            ->order( 'uf.id DESC' )
            ->select();
    }

    /**
     * 取收藏的活动
     *
     * @param $params
     *
     * @return array
     */
    private function getEventByUser( $params ) {
        $data = [];

        return $data;
    }

    /**
     * 取收藏的商品
     *
     * @param $params
     *
     * @return array
     */
    private function getGoodsByUser( $params ) {
        $data = [];

        return $data;
    }
}