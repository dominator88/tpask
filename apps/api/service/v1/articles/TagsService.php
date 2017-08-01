<?php
namespace apps\api\service\v1\articles;

/**
 * 文章标签
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-25
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerTagsService;

class TagsService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取文章标签' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'merId' => [ '商户ID' , '1' , PARAM_REQUIRED ]
    ] ,
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get' => [
      "text"  => "标签" ,
      "count" => "对应数量"
    ] ,
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new TagsService();
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
        
        return api_result( '查询成功' , 0 , [ 'rows' => $data ] );
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
    $MerTags = MerTagsService::instance();
    
    return $MerTags->getByCond( [
      'merId'    => $this->merId ,
      'page'     => 1 ,
      'pageSize' => 10 ,
      'sort'     => 'rand' ,
    ] );
  }
}
