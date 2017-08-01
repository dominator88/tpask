<?php
namespace apps\api\service\v1\system;

/**
 * 图片文件上传
 *
 * @author Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-21
 */

use apps\api\service\v1\ApiService;

class UploadService extends ApiService {
	
	//允许的请求方式
	public $allowRequestMethod = [
		'post' => 'POST 文件图片上传'
	];
	
	/**
	 * 传参 如:
	 * 'title' => ['标题' , '默认值' ]
	 * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
	 */
	public $defaultParams = [
		'post' => [
			'type'     => [ '类型', 'img', [ 'img' => '图片', 'file' => '文件' ] ],
			'fileData' => [ '要上传的图片或文件', '', 'file' ],
		]
	];
	
	/**
	 * 返回结果示例 如:
	 *
	 * 'user_id'     => '用户ID',
	 */
	public $defaultResponse = [
		'post' => [
			"uri"      => "可访问的uri",
			"savePath" => "相对路径",
			"mimes"    => "mime类型",
			"size"     => "文件尺寸",
			"name"     => "文件名",
			"width"    => "宽度",
			"height"   => "高度"
		]
	];
	
	private static $instance;
	
	public static function instance( $params = [] ) {
		if ( self::$instance == NULL ) {
			self::$instance         = new UploadService();
			self::$instance->params = $params;
		}
		
		return self::$instance;
	}
	
	//接口响应方法
	function response() {
		if ( ! $this->validParams() ) {
			return api_result( $this->error, 500 );
		}
		$Upload = \apps\common\service\UploadService::instance( 'fileData' );
		
		return $Upload->doUpload( $this->params );
	}
	
}
