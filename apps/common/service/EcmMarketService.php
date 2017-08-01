<?php namespace apps\common\service;
/**
 * EcmMarket Service
 *
 * @author Zix
 * @version 2.0 2017-05-09
 */



class EcmMarketService extends BaseService {

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
			self::$instance = new EcmMarketService();
			self::$instance->model = db('EcmMarket');
		}
		return self::$instance;
	}

  //取默认值
	function getDefaultRow() {
		return [
			'mk_id' => '' , 
'mk_name' => '' , 
'parent_id' => '0' , 
'sort_order' => '255' , 
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
    'field'    => [ 'mk_id id,mk_name name,parent_id'],
    'keyword'  => '',
    'status'   => '',
    'page'     => 1,
    'pageSize' => 10,
    'sort'     => 'sort_order',
    'order'    => 'DESC',
      'withMarket' => [],
    'count'    => FALSE,
    'getAll'   => FALSE
  ];

  $param = extend( $default, $param );

  if ( ! empty( $param['keyword'] ) ) {
    $this->model->where('name' , 'like' , "%{$param['keyword']}%" );
  }

    if ( ! empty( $param['parent_id'] ) ) {
        $this->model->where('parent_id' , $param['parent_id'] );
    }

  if ( $param['status'] !== '' ) {
    $this->model->where( 'status' , $param['status'] );
  }

  if(!empty($param['withMarket'])){
      $this->model->where('mk_name','in',$param['withMarket']);
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

    if($param['parent_id'] == 1){
       $data = array_map(function($d){
           $d['floors'] = $this->getfloors($d['id']);
            return $d;
       },$data);
    }
  return $data ? $data : [ ];
}

private function getfloors($market_id){
   return $this->getByCond([
        'parent_id' => $market_id,
        'getAll'    => true,
    ]);
}
}