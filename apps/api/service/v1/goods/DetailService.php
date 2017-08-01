<?php
namespace apps\api\service\v1\goods;

/**
 * 商品详情
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-21
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerGoodsService;


class DetailService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取商品详情' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'token'   => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'goodsId' => [ '商品ID' , '' , PARAM_REQUIRED ]
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get' => [
      "id"           => "商品ID" ,
      "pid"          => "父ID" ,
      "name"         => "名称" ,
      "highlight"    => "亮点" ,
      "desc"         => "简介" ,
      "tags"         => "标签" ,
      "start_time"   => "销售开始时间" ,
      "end_time"     => "销售结束时间" ,
      "currency"     => "货币种类" ,
      "price_market" => "市场价" ,
      "price"        => "销售价" ,
      "points"       => "可获积分" ,
      "status"       => "状态" ,
      "recommend"    => "是否推荐" ,
      "hot"          => "是否热卖" ,
      "cheap"        => "是否特惠" ,
      "sales"        => "销售量" ,
      "comments"     => "评论数" ,
      "pv"           => "浏览量" ,
      "catalog_id"   => "分类ID" ,
      'catalog_text' => '分类名称' ,
      'catalog_type' => '分类类型' ,
      'contentUri'   => '' ,
      "icon"         => [
        "uri"      => [ '图片' , 'formatIcon' ] ,
        "is_cover" => "是否封面"
      ] ,
      'commentsList' => [
        "id"         => "评论ID" ,
        "user_id"    => "用户ID" ,
        "content"    => "评论内容" ,
        "reply"      => "回复内容" ,
        "replied_at" => "回复时间" ,
        "created_at" => "创建时间" ,
        "nickname"   => "评论用户昵称" ,
        "icon"       => [ '用户头像' , 'formatIcon' ] ,
      ]
    ]
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new DetailService();
      self::$instance->params = $params;
    }
    
    return self::$instance;
  }
  
  /**
   * 接口响应方法
   *
   * @return array
   */
  public function response() {
    
    
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    //处理业务
    switch ( request()->method() ) {
      case 'GET' :
        $data = $this->get();
        
        $data = $this->formatData( $data );
        
        return api_result( '查询成功' , 0 , $data );
      default :
        return api_result( '未知请求类型' , 500 );
    }
  }
  
  /**
   * get 的响应方法
   *
   * @return array|number
   */
  public function get() {
    $MerGoods = MerGoodsService::instance();
    
    $data = $MerGoods->getDetailById( $this->params['goodsId'] , 'api' );
    
    return $data;
  }
}
