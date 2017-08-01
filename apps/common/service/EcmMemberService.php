<?php namespace apps\common\service;
/**
 * EcmMember Service
 *
 * @author Zix
 * @version 2.0 2017-05-10
 */

use apps\common\service\EcmMemberTokenService;


class EcmMemberService extends BaseService {

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
			self::$instance = new EcmMemberService();
			self::$instance->model = db('EcmMember');
		}
		return self::$instance;
	}

  //取默认值
	function getDefaultRow() {
		return [
			'user_id' => '' , 
'user_name' => '' , 
'email' => '' , 
'password' => '' , 
'real_name' => '' , 
'gender' => '0' , 
'birthday' => '' , 
'phone_tel' => '' , 
'phone_mob' => '' , 
'im_qq' => '' , 
'im_msn' => '' , 
'im_skype' => '' , 
'im_yahoo' => '' , 
'im_aliww' => '' , 
'reg_time' => '0' , 
'last_login' => '' , 
'last_ip' => '' , 
'logins' => '0' , 
'ugrade' => '0' , 
'portrait' => '' , 
'outer_id' => '0' , 
'activation' => '' , 
'feed_config' => '' , 
'upload_goods' => '0' , 
'upload_goods_time' => '0' , 
'behalf_goods_taker' => '0' , 
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
    'field'    => [ 'm.*' ],
    'keyword'  => '',
    'status'   => '',
    'page'     => 1,
    'pageSize' => 10,
    'sort'     => 'user_id',
    'order'    => 'DESC',
    'count'    => FALSE,
    'getAll'   => FALSE
  ];

  $param = extend( $default, $param );

    $this->model->alias('m');

  if ( ! empty( $param['keyword'] ) ) {
    $this->model->where('m.user_name' , 'like' , "%{$param['keyword']}%" );
  }
  if ( $param['status'] !== '' ) {
    $this->model->where( 'status' , $param['status'] );
  }

  if ( $param['count'] ) {
    return $this->model->count();
  }

  if( $param['withToken']){
    $this->model->join('ecm_member_token mt','m.user_id=mt.user_id','left');
      $param['field'] = array_merge( $param['field'] , [ 'mt.token' , 'mt.expires_in' ] );
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

public function getById($param){
    $default = [
        'field'    => [  ],
        'user_id' => 0,
        'sort'     => 'user_id',
        'order'    => 'DESC',
    ];

    $param = extend( $default, $param );

    $this->model->alias('m');
    $this->model->join('ecm_member_token mt',' m.user_id = mt.user_id','left');
    if($param['user_id']){
        $this->model->where('m.user_id',$param['user_id']);
    }



    return $this->model->find();
}
    /**
     * 根据 token 取用户信息
     *
     * @param $token
     *
     * @return array
     */
    public function getByToken( $token) {

        $data = $this->model->alias('m')->join('ecm_member_token mt','m.user_id=mt.user_id','left')->where('mt.token',$token)->find();

        return $data ? $data : [];
    }
  
}