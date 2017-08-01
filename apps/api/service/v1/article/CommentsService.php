<?php
namespace apps\api\service\v1\article;

/**
 * 取文章评论1
 *
 * @author Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-22
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerUserCommentsService;

class CommentsService extends ApiService {
	
	//允许的请求方式
	public $allowRequestMethod = [
		'get' => 'GET - 取文章评论',
	];
	
	/**
	 * 传参 如:
	 * 'title' => ['标题' , '默认值' , '验证方法']
	 * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
	 */
	public $defaultParams = [
		'get' => [
			'articleId' => [ '文章ID', '', PARAM_REQUIRED ],
			'token'     => [ '用户Token', '' ],
		],
	];
	
	/**
	 * 返回结果示例 如:
	 *
	 * 'user_id'     => '用户ID',
	 */
	public $defaultResponse = [
		'get' => [
			"id"         => "评论ID",
			"pid"        => "父ID",
			"type"       => "评论对象",
			"type_id"    => "评论对象ID",
			"user_id"    => "用户ID",
			"content"    => "评论内容",
			"status"     => "状态",
			"created_at" => "评论发布时间",
			"nickname"   => "用户名",
			"phone"      => [ "手机号", 'formatPhone' ],
			"icon"       => [ "头像", "formatIcon" ]
		],
	];
	
	private static $instance;
	
	public static function instance( $params = [] ) {
		if ( self::$instance == NULL ) {
			self::$instance         = new CommentsService();
			self::$instance->params = $params;
		}
		
		return self::$instance;
	}
	
	//接口响应方法
	public function response() {
		//验证用户
		$this->validToken();
		
		if ( ! $this->validParams() ) {
			return api_result( $this->error, 500 );
		}
		
		$MerUserComments = MerUserCommentsService::instance();
		
		$data = $MerUserComments->getByCond( [
			'type'    => 'article',
			'type_id' => $this->params['articleId'],
			'status'  => 1
		] );
		$data = $this->formatData( $data );
		
		return api_result( '查询成功', 0, [ 'rows' => $data ] );
	}
	
	
}
