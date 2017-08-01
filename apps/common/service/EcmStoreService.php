<?php namespace apps\common\service;
/**
 * EcmStore Service
 *
 * @author Zix
 * @version 2.0 2017-05-09
 */



class EcmStoreService extends BaseService {

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
			self::$instance = new EcmStoreService();
			self::$instance->model = db('EcmStore');
		}
		return self::$instance;
	}

  //取默认值
	function getDefaultRow() {
		return [
			'store_id' => '0' , 
'store_name' => '' , 
'owner_name' => '' , 
'owner_card' => '' , 
'region_id' => '' , 
'region_name' => '' , 
'address' => '' , 
'zipcode' => '' , 
'tel' => '' , 
'sgrade' => '0' , 
'apply_remark' => '' , 
'credit_value' => '0' , 
'praise_rate' => '0.00' , 
'domain' => '' , 
'state' => '0' , 
'close_reason' => '' , 
'add_time' => '' , 
'end_time' => '0' , 
'certification' => '' , 
'sort_order' => '0' , 
'recommended' => '0' , 
'theme' => '' , 
'store_banner' => '' , 
'store_logo' => '' , 
'description' => '' , 
'image_1' => '' , 
'image_2' => '' , 
'image_3' => '' , 
'im_qq' => '' , 
'im_ww' => '' , 
'im_wx' => '' , 
'im_msn' => '' , 
'hot_search' => '' , 
'business_scope' => '' , 
'online_service' => '' , 
'hotline' => '' , 
'pic_slides' => '' , 
'enable_groupbuy' => '0' , 
'enable_radar' => '1' , 
'service_daifa' => '0' , 
'service_tuixian' => '0' , 
'service_huankuan' => '0' , 
'serv_refund' => '0' , 
'serv_exchgoods' => '0' , 
'serv_sendgoods' => '0' , 
'serv_realpic' => '0' , 
'serv_addred' => '0' , 
'serv_modpic' => '0' , 
'serv_deltpic' => '0' , 
'serv_probexch' => '0' , 
'serv_golden' => '' , 
'shop_mall' => '' , 
'floor' => '' , 
'see_price' => '' , 
'shop_http' => '' , 
'has_link' => '0' , 
'cate_content' => '' , 
'mk_id' => '' , 
'mk_name' => '' , 
'dangkou_address' => '' , 
'last_update' => '' , 
'auto_sync' => '0' , 
'real_address' => '' , 
'datapack' => '' , 
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
    'sort'     => 'store_id',
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
  
}