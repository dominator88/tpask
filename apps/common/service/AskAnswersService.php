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
    'field'    => [ 'a.*' , 'u.nickname' , 'u.phone' , 'u.icon' ],
    'keyword'  => '',
      'qid' => '',
    'status'   => '',
      'adopt' => 0,
    'page'     => 1,
    'pageSize' => 10,
    'sort'     => 'id',
    'order'    => 'DESC',
      'withQuestion' => false,
    'count'    => FALSE,
    'getAll'   => FALSE
  ];

  $param = extend( $default, $param );
    $this->model->alias('a');
  if ( ! empty( $param['keyword'] ) ) {
    $this->model->where('a.name' , 'like' , "%{$param['keyword']}%" );
  }

  if ( $param['status'] !== '' ) {
    $this->model->where( 'a.status' , $param['status'] );
  }
    if ( $param['adopt'] !== '' ) {
        $this->model->where( 'a.adopt' , $param['adopt'] );
    }
    if ( $param['qid'] !== '' ) {
        $this->model->where( 'a.qid' , $param['qid'] );
    }

  if ( $param['count'] ) {
    return $this->model->count();
  }

  $this->model->field( $param['field'] )
      ->join( 'ask_user u' , 'a.userId=u.id' , 'left');

  if ( ! $param['getAll'] ) {
    $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'], $param['pageSize'] );
  }

  if($param['withQuestion']){
      $field_q = ['q.title'];
    $this->model->field(extend($param['field'] ,$field_q ))->join('ask_questions q','a.qid=q.id' ,'left');
  }

  $order[] = "{$param['sort']} {$param['order']}";
  $this->model->order( $order );

  $data = $this->model->select();
  //echo $this->model->getLastSql();

  return $data ? $data : [ ];
}

    /**
     * 发表回答
     *
     * @param $qid 问题id
     * @param $userId
     * @param $content
     *
     * @return array
     */
    public function post( $qid , $userId , $content ) {

        if ( empty( $qid ) ) {
            return ajax_arr( '请填写问题ID' , 500 );
        }

        if ( empty( $userId ) ) {
            return ajax_arr( '请先登录' , 500 );
        }

        if ( empty( $content ) ) {
            return ajax_arr( '请填写回答内容' , 500 );
        }

        $oldData = $this->model
            ->where( 'qid' , $qid )
            ->where( 'userId' , $userId )
            ->select();

        if ( ! empty( $oldData ) ) {
            return ajax_arr( '已经回答过了' , 500 );
        }


        $data = [
            'userId' => $userId ,
            'qid' => $qid ,
            'content' => $content
        ];

        $result = $this->insert( $data );

        if ( $result['code'] == 0 ) {

                //添加 article
                $AskQuestionsService = AskQuestionsService::instance();
                 $AskQuestionsService->incAnswers( $qid );

            }


        return $result;
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


    public function setAdopt($id){
        $this->model->where('id' ,$id)->update([ 'adopt' => 1 ]);
    }
}