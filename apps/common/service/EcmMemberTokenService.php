<?php namespace apps\common\service;
use apps\api\service\v1\ApiService;
use apps\common\model\EcmMemberToken;

/**
 * EcmMemberToken Service
 *
 * @author Zix
 * @version 2.0 2017-05-10
 */



class EcmMemberTokenService extends BaseService {

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
			self::$instance = new EcmMemberTokenService();
			self::$instance->model = db('EcmMemberToken');
		}
		return self::$instance;
	}

  //取默认值
	function getDefaultRow() {
		return [
			'id' => '' , 
'user_id' => '' , 
'token' => '' , 
'expires_in' => '' , 
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

public function getToken($user_id){
    $usertoken = $this->model->where('user_id',$user_id)->find();

    settype($user_id,'integer');
    if($usertoken && $usertoken['expires_in'] - time() > 30*3600 * 24){
        $data['uid'] = $user_id;
        $data['token'] = $usertoken['token'];
        $data['expiresIn'] = $usertoken['expires_in'];
        $data['behalf'] = $usertoken['behalf_id'];
        return $data;
    }else{
        return false;
    }

}

public function setToken($user_id){
    $data = [
        'expires_in' => time() + 30 * 3600 * 24,
        'user_id'  => $user_id,
    ];
   $EcmMemberService =  EcmMemberService::instance();
    $user = $EcmMemberService->getById([
        'user_id' => $user_id
    ]);
//echo $EcmMemberService->model->getLastSql();
    //ApiService::instance()->log('sql',$EcmMemberService->model->getLastSql());
    if($user['token']){
        $data['id'] = $user['id'];
        $data['token'] = $user['token'];
        $data['behalf_id'] = $user['behalf_goods_taker'];
        $this->model->update($data);
    }else{
        $data['token'] = md5(implode('&',$data));
        $data['behalf_id'] = $user['behalf_goods_taker'];
        $this->model->insert($data);
    }
    unset($data['id']);
    settype($data['user_id'],'integer');
    $data_r =  [
        'expiresId' => $data['expires_in'],
        'uid'    => $data['user_id'],
        'token' => $user['token'],
        'name' => $user['user_name'],
        'behalf' => $user['behalf_goods_taker'],
    ];

    return $data_r;


}


public function getByToken($token){
    if(!$token){
        return;
    }

    return $this->model->where('token',$token)->find();
}
}