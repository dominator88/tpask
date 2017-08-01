<?php namespace apps\common\service;
/**
 * EcmOrder Service
 *
 * @author Zix
 * @version 2.0 2017-05-09
 */



class EcmOrderService extends BaseService {

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
			self::$instance = new EcmOrderService();
			self::$instance->model = db('EcmOrder');
		}
		return self::$instance;
	}

  //取默认值
	function getDefaultRow() {
		return [
			'order_id' => '' , 
'order_sn' => '' , 
'type' => 'material' , 
'extension' => '' , 
'seller_id' => '0' , 
'seller_name' => '' , 
'buyer_id' => '0' , 
'buyer_name' => '' , 
'buyer_email' => '' , 
'status' => '0' , 
'add_time' => '0' , 
'payment_id' => '' , 
'payment_name' => '' , 
'payment_code' => '' , 
'out_trade_sn' => '' , 
'pay_time' => '' , 
'pay_message' => '' , 
'ship_time' => '' , 
'invoice_no' => '' , 
'logistics' => '' , 
'finished_time' => '0' , 
'goods_amount' => '0.00' , 
'discount' => '0.00' , 
'order_amount' => '0.00' , 
'evaluation_status' => '0' , 
'evaluation_time' => '0' , 
'anonymous' => '0' , 
'postscript' => '' , 
'seller_message' => '' , 
'seller_message_flag' => '' , 
'pay_alter' => '0' , 
'bh_id' => '0' , 
'behalf_discount' => '0.00' , 
'quality_check_fee' => '0.00' , 
'tags_change_fee' => '0.00' , 
'packing_bag_change_fee' => '0.00' , 
'behalf_fee' => '0.00' , 
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


public function getById($param){
    if(!$param['id']){
        return;
    }
   return $this->model->where('order_id',$param['id'])->find();
}
  
}