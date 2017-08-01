<?php namespace apps\common\service;
/**
 * EcmGoodsWarehouse Service
 *
 * @author Zix
 * @version 2.0 2017-05-09
 */



use apps\api\service\v1\ApiService;



define('BEHALF_GOODS_PREPARED',0);//代发商品准备中,下单后的默认状态
define('BEHALF_GOODS_DELIVERIES',5);//代发商品已派单
define('BEHALF_GOODS_READY_APP',9);//App已拿货
define('BEHALF_GOODS_READY',10);//代发商品准备好了
define('BEHALF_GOODS_SEND',11);//代发商品已发货
define('BEHALF_GOODS_TOMORROW',20);//代发商品明天有
define('BEHALF_GOODS_UNFORMED',30);//代发商品未出货
define('BEHALF_GOODS_UNSALE',40);//代发商品已下架
define('BEHALF_GOODS_ERROR',60);//代发商品信息有误
define('BEHALF_GOODS_ERROR2',61);//自定义缺货
define('BEHALF_GOODS_REBACK',50);//代发商品已退货

define('ORDER_SUBMITTED', 10);                 // 针对货到付款而言，他的下一个状态是卖家已发货
define('ORDER_PENDING', 11);                   // 等待买家付款
define('ORDER_ACCEPTED', 20);                  // 买家已付款，等待卖家发货
define('ORDER_SHIPPED', 30);                   // 卖家已发货
define('ORDER_FINISHED', 40);                  // 交易成功
define('ORDER_CANCELED', 0);                   // 交易已取消


class EcmGoodsWarehouseService extends BaseService {

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
			self::$instance = new EcmGoodsWarehouseService();
			self::$instance->model = db('EcmGoodsWarehouse');
		}
		return self::$instance;
	}

  //取默认值
	function getDefaultRow() {
		return [
			'id' => '' , 
'goods_no' => '' , 
'goods_id' => '' , 
'goods_name' => '' , 
'goods_price' => '0.00' , 
'goods_quantity' => '0' , 
'goods_sku' => '' , 
'goods_attr_value' => '' , 
'goods_image' => '' , 
'goods_status' => '0' , 
'goods_spec_id' => '' , 
'goods_specification' => '' , 
'store_id' => '0' , 
'store_name' => '' , 
'store_address' => '' , 
'store_bargin' => '0' , 
'market_id' => '0' , 
'market_name' => '' , 
'floor_id' => '0' , 
'floor_name' => '' , 
'order_id' => '' , 
'order_sn' => '' , 
'order_goods_quantity' => '0' , 
'order_add_time' => '' , 
'order_pay_time' => '' , 
'order_postscript' => '' , 
'delivery_id' => '' , 
'delivery_name' => '' , 
'taker_id' => '0' , 
'bh_id' => '' , 
'refund_id' => '0' , 
'behalf_to51_discount' => '0.00' , 
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
      //商品编号,（名称），价格，数量，货号，商家编码，（图片），状态，颜色尺码，（商家id），市场id,楼层，订单id，拿货员id
    'field'    => [ 'gw.id , gw.goods_no no,gw.goods_name name,gw.goods_price price,gw.goods_quantity,gw.goods_sku sku,gw.goods_attr_value code ,gw.goods_image img,gw.goods_status status,gw.goods_specification spec,gw.store_id storeId,gw.store_address storeNo,gw.market_id marketId,gw.market_name marketName,gw.floor_id floorId,gw.floor_name floorName,gw.order_id orderId,taker_id takerId,gw.taker_time time,gw.behalf_take_num num1,gw.real_price payment,gw.order_add_time created,o.pay_time payTime,1 as num' ],
    'keyword'  => '',
    'status'   => '',
      'goods_status' => [],
      'store_id' => '',
      'market_id' => '',
      'taker_id' => 0,
    'page'     => 1,
    'pageSize' => 10,
    'sort'     => 'id',
    'order'    => 'DESC',
      'startTime' => '',    //开始时间
      'endTime' => '',      //结束时间
      'order_id' => '',
      'bh_id' => '',    //商品所属代发id
      'notrefund' => false,  //是否包含申请售后的订单
      'ids' => 0,
      'purchases_time' => '',
      'withBuyer' => FALSE,
      'withToday' => '',
'withReady' => null,
      'orderStatus' => [ORDER_ACCEPTED],
      'purchases_status' => false,
    'count'    => FALSE,
    'getAll'   => FALSE
  ];

  $param = extend( $default, $param );
    $this->model->alias('gw');

    if(! empty(  $param['orderStatus'])){
        $this->model->join('ecm_order o','gw.order_id = o.order_id','right')->where('o.status','in', $param['orderStatus']);
    }
  if ( ! empty( $param['keyword'] ) ) {
    $this->model->where('name' , 'like' , "%{$param['keyword']}%" );
  }
    if ( ! empty( $param['ids'] ) ) {
        $this->model->where('id' , 'in' , $param['ids'] );
    }

    if($param['purchases_time'] !== ''){
        $this->model->where('purchases_time',$param['purchases_time']);
    }


    if( $param['startTime'] && $param['endTime']  ){
         $this->model->where('taker_time','between',[$param['startTime'] , $param['endTime']]);
    }
  if ( $param['status'] !== '' ) {
    $this->model->where( 'status' , $param['status'] );
  }
    if ( !empty($param['goods_status']) ) {
        if(!is_null($param['withReady'])){
            if($param['withReady'] == 0){
                 $this->model->where( function($query){
                        $query->where('goods_status' , BEHALF_GOODS_DELIVERIES)->whereOr(function($query){
                            $query->where('purchases_time','gt', strtotime(date('Ymd',time())))->where('goods_status', BEHALF_GOODS_READY_APP);
                        });
                  });
            }elseif($param['withReady']  == -1){
                $this->model->where( 'goods_status','in', $param['goods_status']);
            }elseif($param['withReady']  == 1){
                $this->model->where(function($query){
                    $query->where('purchases_time','gt', strtotime(date('Ymd',time())))->where('goods_status', BEHALF_GOODS_READY_APP);
                });
            }
        }else{

            $this->model->where( 'goods_status','in', $param['goods_status']);
        }
    }




    if ( $param['order_id'] !== '' ) {
        $this->model->where( 'order_id' , $param['order_id'] );
    }
    if ( $param['bh_id'] !== '' ) {
        $this->model->where( 'gw.bh_id' , $param['bh_id'] );
    }
    if ( $param['store_id'] !== '' ) {
        $this->model->where( 'store_id' , $param['store_id'] );
    }

    if ( !empty($param['taker_id']) ) {
        $this->model->where( 'taker_id' , $param['taker_id'] );
    }



    if ( $param['market_id'] !== '' ) {
        /*$EcmMarketService = EcmMarketService::instance();
        $market = $EcmMarketService->getById($param['market_id']);
        if($market['parent_id'] == 1){
            $sub_market = $EcmMarketService->getByCond([
                'parent_id' => $market['mk_id'],
            ]);

           $markets =  array_column($sub_market , 'id') ?: [$param['market_id']] ;

        }else{
            $markets = [$param['market_id']];
        }
        array_push($markets,$param['market_id']);
        */
        $market_arr = array();
        $this->getMarkets($param['market_id'], $market_arr );
        $market_arr = array_unique($market_arr);


        $this->model->where( 'floor_id|market_id' , 'in', $market_arr );



    }

    if($param['withToday'] !== ''){
        $time = strtotime(date('Y-m-d',time()));
        if($param['withToday'] == 1){
            $this->model->where('purchases_time','gt',$time);
      }elseif($param['withToday'] == 0){
          $this->model->where('purchases_time','lt',$time);
        }
    }

  if ( $param['count'] ) {
    return $this->model->count();
  }


  if($param['withBuyer']){
    $this->model->join('ecm_member m','o.buyer_id=m.user_id','inner');//->join('ecm_mem');
      $param['field'] = array_merge($param['field'],['m.user_name buyer']);
  }

    //为true不含有退款申请的订单
    if($param['notrefund']){
        $EcmOrderRefundService = EcmOrderRefundService::instance();

        $refunds = $EcmOrderRefundService->getByCond([
            'receiver_id' => $param['bh_id'],
            'status' => 0,
            'closed' => 0,
            'type' => 1,
            'getAll' =>true,
        ]);

        $order_ids = array_column($refunds,'order_id');

        $this->model->where('gw.order_id','NOT IN',$order_ids);
    }

  $this->model->field( $param['field'] );

  if ( ! $param['getAll'] ) {
    $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'], $param['pageSize'] );
  }
  $order[] = "floorName";
  $order[] = "{$param['sort']} {$param['order']}";
  $this->model->order( $order );

  $data = $this->model->select();

   // ApiService::instance()->log('sql',$this->model->getLastSql());

    $data = array_map(function($d){settype($d['price'],'float');return $d;},$data);
    $data = array_map(function($d){settype($d['payment'],'float');return $d;},$data);
    $data = array_map(function($d){if($d['payment'] == 0){$d['payment'] = $d['price'];}return $d;},$data);
  return $data ? $data : [ ];
}

private function getMarkets($market_id ,  &$market_arr){

    $EcmMarketService = EcmMarketService::instance();
    $markets = $EcmMarketService->getByCond([
        'parent_id' => $market_id,
        'getAll' =>true,
    ]);
    array_push($market_arr , $market_id);
   // $markets = reset($markets);

    if(!empty($markets)){

        foreach($markets as $market){
            array_push($market_arr , $market['id']);

            $this->getMarkets($market['id'],$market_arr);
        }
    }

    return $markets;
}


public function getById($param){

    if(!$param['id']){return;}
    $this->model->alias('gw');
    return $this->model->field([ 'gw.id , gw.goods_no no,gw.goods_name name,gw.goods_price price,gw.goods_quantity,gw.goods_sku sku,gw.goods_attr_value code ,gw.goods_image img,gw.goods_status status,gw.goods_specification spec,gw.store_id storeId,gw.store_address storeNo,gw.market_id marketId,gw.market_name marketName,gw.floor_id floorId,gw.floor_name floorName,gw.order_id orderId,taker_id takerId,1 as num' ])->where('id',$param['id'])->find();
}
    /**
     * 设置商品派单
     * @param $param
     * @return mixed
     */
public function setDeliveries($param){

    $data['taker_id'] = $param['taker_id'];
    $data['goods_status'] = $param['goods_status'];
    $data['taker_time'] = time();


    return $this->model->where('id','in',$param['ids'])->update($data);
}

    /**
     * 设置商品采购
     * @param $param
     * @return mixed
     */
public function setPurchases($param){
    $behalf_goods = $this->model->where('id','in',$param['ids'])->find();
    //当前拿到的数量 大于 商品总数 或者 小于已经拿到的商品数量报错
   /* if($param['behalf_take_num'] > $behalf_goods['goods_quantity'] ||  $param['behalf_take_num'] < $behalf_goods['behalf_take_num']){
        return FALSE;
    }elseif($param['behalf_take_num'] == $behalf_goods['goods_quantity']){
        $data['goods_status'] = $param['goods_status'];
        $data['behalf_take_num'] = $param['behalf_take_num'];
        $data['purchases_time'] = time();
    }else{
        $data['goods_status'] = BEHALF_GOODS_DELIVERIES;
        $data['behalf_take_num'] = $param['behalf_take_num'];
    }*/
   //如果状态为派单状态，说明是取消采购，则将已拿数量置0


    $data['goods_status'] = $param['goods_status'];
    $data['behalf_take_num'] = $param['purchases_status'] ? 1 : 0;
    $data['purchases_time'] = time();


    //通过flag 参数与识别当前操作是采购还是取消采购
   $data =  $this->model->where('id','in',$param['ids'])->update($data);
    ApiService::instance()->log('purchasesSQL',$this->model->getLastSql());
    return $data;
}

    /**
     * 设置缺货商品
     * @param $param
     * @return bool
     */
public function setLack($param){
    if(!in_array($param['goods_status'],[BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED,BEHALF_GOODS_UNSALE])){
        return FALSE;
    }
    $data['goods_status'] = $param['goods_status'];
    return $this->model->where('id','in',$param['ids'])->update($data);
}

    /**
     * @param $param
     * @return mixed
     */
public function setWarn($param){

    $data['goods_status'] = $param['goods_status'];

    $this->model->where('id','in',$param['goods_id'])->update($data);
    $EcmGoodsWarnService = EcmGoodsWarnService::instance();

    $data_w = [
        'status' => $param['goods_status'],
        'goods_id' => $param['goods_id'],
        'remark' => $param['remark'],
        'add_time' => time(),
    ];
    return $EcmGoodsWarnService->model->insert($data_w);
    //return
}

public function setDiffprice($param){
    $data['id'] = $param['id'];
    $data['real_price'] = $param['real_price'];
   return  $this->model->where('id',$param['id'])->update($data);
}

    /**
     * @param $marketid
     * @return mixed
     */
public function getMarketStatistics($param){
    $marketids = [];
    $withStatus = $param['withStatus'];
    if($withStatus == 1){
        $this->model->where('goods_status', BEHALF_GOODS_DELIVERIES);
    }elseif($withStatus == 2){
        $this->model->where('goods_status', BEHALF_GOODS_PREPARED);
    }elseif($withStatus == 3){
        $this->model->where('goods_status', 'in',[BEHALF_GOODS_READY_APP,BEHALF_GOODS_READY] )->where('purchases_time','gt',strtotime(date('Y-m-d',time())));
    }elseif($withStatus == 4){
        $this->model->where('goods_status', 'in',[BEHALF_GOODS_READY_APP,BEHALF_GOODS_READY])->where('purchases_time','lt',strtotime(date('Y-m-d',time())));
    }
    $this->getMarkets($param['marketid'],$marketids);
  //  print_r($marketids);
    return $this->model->alias('gw')->field('market_id id,count(*) count')->where('market_id','in',$marketids)->group('market_id')->select();
}

    /***
     * @param $marketid
     * @return mixed
     */
    public function getFloorStatistics($marketid){
        $marketids = [];
        $this->getMarkets($marketid,$marketids);
        return $this->model->field('floor_id id,count(*) count')->where('floor_id','in',$marketids)->group('floor_id')->select();
    }
}