<?php
namespace apps\api\service\v1\auth;

/**
 * 用户登录
 *
 * @author Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-11-16
 */

use apps\api\service\v1\ApiService;

class LoginService extends ApiService {

  //允许的请求方式
	public $allowRequestMethod = [
	  'get'  => 'GET - 取用户登录',
	  'post' => 'POST - 设置用户登录',
	  'put' => 'PUT - 设置用户登录',
	  'delete' => 'DELETE - 设置用户登录'
	];

	/**
	 * 传参 如:
	 * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
	 * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
	 */
	public $defaultParams = [
	  'get' => [
	    'token' => ['用户Token' , '' , PARAM_REQUIRED ] ,'phone' => ['手机号' , '' , PARAM_REQUIRED] ,'status' => ['状态' , '' , [ "0" => "禁用" , "1" => "启用"] ] ,
	  ],
	  'post' => [
	    'token' => ['用户Token' , '' , PARAM_REQUIRED ] ,'phone' => ['手机号' , '' , PARAM_REQUIRED] ,'status' => ['状态' , '' , [ "0" => "禁用" , "1" => "启用"] ] ,
	  ],
	  'put' => [
	    'token' => ['用户Token' , '' , PARAM_REQUIRED ] ,'phone' => ['手机号' , '' , PARAM_REQUIRED] ,'status' => ['状态' , '' , [ "0" => "禁用" , "1" => "启用"] ] ,
	  ],
	  'delete' => [
	    'token' => ['用户Token' , '' , PARAM_REQUIRED ] ,'phone' => ['手机号' , '' , PARAM_REQUIRED] ,'status' => ['状态' , '' , [ "0" => "禁用" , "1" => "启用"] ] ,
	  ]
	];

	/**
	 * 返回结果示例 如:
	 *
	 * 'user_id'     => '用户ID',
	 * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
	 */
	public $defaultResponse = [
     'get'  => [],
     'post' => [],
     'put' => [],
     'delete' => []
	];

  private static $instance;
	public static function instance( $params = [] ) {
		if ( self::$instance == NULL ) {
			self::$instance = new LoginService();
			self::$instance->params = $params ;
		}

		return self::$instance;
	}

  /**
   * 接口响应方法
   *
   * @return array
   */
	public function response() {
    //验证用户
if ( ! $this->validToken() ) {
  return api_result( $this->error, $this->errCode );
}

		if ( ! $this->validParams() ) {
			return api_result( $this->error, 500 );
		}

    //处理业务
    switch ( request()->method() ) {
      case 'GET' :
        $data = $this->get();
        $data = $this->formatData( $data );

        return api_result( '查询成功' , 0 , [ 'rows' => $data ] );
      case 'POST' :
        return $this->post();
      case 'PUT' :
        return $this->put();
      case 'DELETE' :
        return $this->delete();
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
    return [
      ['id' => 1 , 'text' => '测试数据']
    ];
  }

  /**
   * post 的响应方法
   *
   * @return array
   */
  public function post() {
    $data = [];
    return api_result( 'post 成功', 0 , $data );
  }


  /**
   * post 的响应方法
   *
   * @return array
   */
  public function put() {
    $data = [];
    return api_result( 'put 成功', 0 , $data );
  }

  /**
   * post 的响应方法
   *
   * @return array
   */
  public function delete() {
    $data = [];
    return api_result( 'delete 成功', 0 , $data );
  }
}
