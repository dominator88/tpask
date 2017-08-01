<?php
namespace apps\api\service\v1\page;

/**
 * 首页
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-21
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerAdService;
use apps\common\service\MerArticlesService;
use apps\common\service\MerGoodsService;

class HomeService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取首页' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'merId' => [ '机构ID' , 1 , PARAM_REQUIRED ] ,
      'type'  => [ 'app类型' , 'news' , [ 'news' => '资讯类' , 'mall' => '商城类' ] ] ,
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
      'sliders'  => [
        "id"     => "slide id" ,
        "name"   => "标题" ,
        "sort"   => "99" ,
        "icon"   => [ "图片" , 'formatIcon' ] ,
        "uri"    => "链接 URL Schemes 如 dmgapp://article/1" ,
        "width"  => "图片宽" ,
        "height" => "图片高"
      ] ,
      'goods'    => [
        "id"           => "商品ID" ,
        "mer_id"       => "机构ID" ,
        "name"         => "商品名称" ,
        "highlight"    => "亮点" ,
        "icon"         => [ '图标' , 'formatIcon' ] ,
        "desc"         => "描述" ,
        "start_time"   => "销售开始时间" ,
        "end_time"     => "销售结束时间" ,
        "currency"     => "货币" ,
        "price_market" => "市场价" ,
        "price"        => "销售价" ,
        "points"       => "可获积分" ,
        "status"       => "状态" ,
        "recommend"    => "是否推荐" ,
        "hot"          => "是否热卖" ,
        "cheap"        => "是否优惠" ,
        "sales"        => "销售数" ,
        "comments"     => "评论数" ,
        "pv"           => "浏览量" ,
        "catalog_id"   => "分类ID" ,
        "catalog_text" => "分类名称" ,
        "catalog_type" => "分类类型"
      ] ,
      'articles' => [
        "id"           => 'ID' ,
        "catalog_id"   => "分类ID" ,
        "sort"         => "排序" ,
        "title"        => "标题" ,
        "icon"         => [ "图标" , 'formatIcon' ] ,
        "status"       => '状态' ,
        "created_at"   => "创建日期" ,
        "catalog_text" => "分类名称" ,
      ] ,
    ]
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new HomeService();
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
        //取滑动图片
        $data['sliders']['rows'] = $this->getSlider( 1 );
        
        if ( $this->params['type'] == 'news' ) {
          //取文章列表
          $data['articles']['rows'] = $this->getArticles();
        } else if ( $this->params['type'] == 'mall' ) {
          //推荐商品
          $data['goods']['rows'] = $this->getRecommendGoods();
        }
        
        return api_result( '查询成功' , 0 , $data );
      default :
        return api_result( '未知请求类型' , 500 );
    }
  }
  
  /**
   * 取 Slider
   *
   * @param int $adCatalog //广告分类
   *
   * @return array|mixed|number
   */
  private function getSlider( $adCatalog = 1 ) {
    $cacheName = 'DMGApp-PageSlider' . $adCatalog;
    
    $data = cache( $cacheName );
    if ( empty( $data ) ) {
      $MerAd = MerAdService::instance();
      
      $data = $MerAd->getByCond( [
        'merId'           => $this->params['merId'] ,
        'catalogId'       => 1 ,
        'getAll'          => TRUE ,
        'catalogWithSize' => TRUE
      ] );
      
      $data = $this->formatData( $data , $this->defaultResponse['get']['sliders'] );
      cache( $cacheName , $data , 3600 );
    }
    
    return $data;
  }
  
  /**
   * 取推荐商品
   *
   * @return array
   */
  private function getRecommendGoods() {
    $MerGoods = MerGoodsService::instance();
    
    $data = $MerGoods->getByCond( [
      'recommend' => 1 ,
      'getAll'    => TRUE ,
    ] );
    
    return $this->formatData( $data , $this->defaultResponse['get']['goods'] );
  }
  
  /**
   * 取文章列表
   *
   * @return array
   */
  private function getArticles() {
    $MerArticles = MerArticlesService::instance();
    
    $data = $MerArticles->getByCond( [
      'withoutContent' => TRUE ,
      'page'           => 1 ,
      'pageSize'       => 10 ,
    ] );
    
    return $this->formatData( $data , $this->defaultResponse['get']['articles'] );
  }
}
