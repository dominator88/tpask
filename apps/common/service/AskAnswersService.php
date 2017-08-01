<?php namespace apps\common\service;
/**
 * AskAnswers Service
 *
 * @author Zix
 * @version 2.0 2017-07-27
 */



class AskAnswersService extends BaseService {

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
			self::$instance = new AskAnswersService();
			self::$instance->model = db('AskAnswers');
		}
		return self::$instance;
	}

  //取默认值
	function getDefaultRow() {
		return [
			'id' => '' , 
'qid' => '' , 
'sort' => '999' , 
'desc' => '' , 
'content' => '' , 
'status' => '1' , 
'comments' => '0' , 
'likes' => '0' , 
'pv' => '0' , 
'favorites' => '0' , 
'userId' => '1' , 
'created_at' => '' , 
'updated_at' => date('Y-m-d H:i:s') , 
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
  
}