<?php namespace apps\common\service;
/**
 * EcmOrderRefund Service
 *
 * @author Zix
 * @version 2.0 2017-05-24
 */

use apps\api\service\v1\ApiService;

class EcmOrderRefundService extends BaseService {

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
			self::$instance = new EcmOrderRefundService();
			self::$instance->model = db('EcmOrderRefund');
		}
		return self::$instance;
	}

  //取默认值
	function getDefaultRow() {
		return [
			'id' => '' , 
'order_id' => '' , 
'order_sn' => '' , 
'sender_id' => '' , 
'sender_name' => '' , 
'receiver_id' => '' , 
'receiver_name' => '' , 
'refund_reason' => '' , 
'refund_amount' => '' , 
'refund_intro' => '' , 
'create_time' => '' , 
'pay_time' => '' , 
'apply_amount' => '' , 
'status' => '0' , 
'closed' => '0' , 
'type' => '0' , 
'invoice_no' => '' , 
'dl_id' => '0' , 
'dl_code' => '' , 
'dl_name' => '' , 
'goods_ids' => '' , 
'refuse_reason' => '' , 
'goods_ids_flag' => '0' , 
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
      'receiver_id' => '',
    'status'   => '',
      'colsed' => 0,
      'type' => 0,
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
    if(! empty( $param['receiver_id'] ) ){
        $this->model->where('receiver_id',$param['receiver_id']);
    }

  if ( $param['status'] !== '' ) {
    $this->model->where( 'status' , $param['status'] );
  }
    if ( $param['closed'] !== '' ) {
        $this->model->where( 'closed' , $param['closed'] );
    }

    if ( $param['type'] !== '' ) {
        $this->model->where( 'type' , $param['type'] );
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
  //  ApiService::instance()->log('sql',$this->model->getLastSql());
  return $data ? $data : [ ];
}
  
}