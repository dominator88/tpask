<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: pengyong <i@pengyong.info>
// +----------------------------------------------------------------------

namespace apps\common\service;

class FtpService {
	
	//FTP 连接资源
	private $link;
	//FTP连接时间
	public $link_time;
	//错误代码
	private $err_code = 0;
	private $root_dir = '/';
	//传送模式{文本模式:FTP_ASCII, 二进制模式:FTP_BINARY}
	public $mode = FTP_BINARY;
	
	/**
	 * 初始化类
	 *
	 * @param $data
	 */
	public function __construct( $data ) {
		if ( empty( $data['port'] ) ) {
			$data['port'] = '21';
		}
		if ( empty( $data['pasv'] ) ) {
			$data['pasv'] = FALSE;
		}
		if ( empty( $data['ssl'] ) ) {
			$data['ssl'] = FALSE;
		}
		if ( empty( $data['timeout'] ) ) {
			$data['timeout'] = 30;
		}
		if ( empty( $data['root_dir'] ) ) {
			$this->root_dir = '/';
		} else {
			$this->root_dir = $data['root_dir'];
		}
		
		return $this->connect( $data['host'], $data['username'], $data['password'], $data['port'], $data['pasv'], $data['ssl'], $data['timeout'] );
	}
	
	/**
	 * 连接FTP服务器
	 *
	 * @param string $host 　　 //服务器地址
	 * @param string $username 　　　//用户名
	 * @param string $password 　　　//密码
	 * @param integer $port 　　　　 //服务器端口，默认值为21
	 * @param boolean $pasv //是否开启被动模式
	 * @param boolean $ssl 　　　　 　//是否使用SSL连接
	 * @param integer $timeout //超时时间　
	 *
	 * @return bool
	 */
	public function connect( $host, $username = '', $password = '', $port = 21, $pasv = FALSE, $ssl = FALSE, $timeout = 30 ) {
		$start = time();
		if ( $ssl ) {
			if ( ! $this->link = @ftp_ssl_connect( $host, $port, $timeout ) ) {
				$this->err_code = 1;
				
				return FALSE;
			}
		} else {
			if ( ! $this->link = @ftp_connect( $host, $port, $timeout ) ) {
				$this->err_code = 1;
				
				return FALSE;
			}
		}
		
		if ( @ftp_login( $this->link, $username, $password ) ) {
			if ( $pasv ) {
				ftp_pasv( $this->link, TRUE );
			}
			$this->link_time = time() - $start;
			
			return TRUE;
		} else {
			$this->err_code = 1;
			
			return FALSE;
		}
		register_shutdown_function( [ &$this, 'close' ] );
	}
	
	/**
	 * 创建文件夹
	 *
	 * @param string $dirName 目录名
	 *
	 * @return bool
	 */
	public function mkdir( $dirName ) {
		if ( ! $this->link ) {
			$this->err_code = 2;
			
			return FALSE;
		}
		$dirName  = $this->ck_dirname( $dirName );
		$root_dir = $this->root_dir;
		$this->chdir( $root_dir );
		foreach ( $dirName as $v ) {
			if ( $v && ! $this->chdir( $v ) ) {
				ftp_mkdir( $this->link, $v );
				$this->chdir( $v );
			}
		}
		$this->chdir( $root_dir );
		
		return TRUE;
	}
	
	/**
	 * 上传文件
	 *
	 * @param string $remote 远程存放地址
	 * @param string $local 本地存放地址
	 *
	 * @return bool
	 */
	public function put( $remote, $local ) {
		if ( ! $this->link ) {
			$this->err_code = 2;
			
			return FALSE;
		}
		
		$this->chdir( $this->root_dir );
		$dirName = pathinfo( $remote, PATHINFO_DIRNAME );
		
		if ( ! $this->chdir( $dirName ) ) {
			$this->mkdir( $dirName );
		} else {
			$this->chdir( $this->root_dir );
		}
		
		if ( ftp_put( $this->link, $remote, $local, $this->mode ) ) {
			return TRUE;
		} else {
			$this->err_code = 7;
			
			return FALSE;
		}
	}
	
	/**
	 * 删除文件夹
	 *
	 * @param string $dirName 目录地址
	 * @param boolean $enforce 强制删除
	 *
	 * @return bool
	 */
	public function rmdir( $dirName, $enforce = FALSE ) {
		if ( ! $this->link ) {
			$this->err_code = 2;
			
			return FALSE;
		}
		$list = $this->nlist( $dirName );
		if ( $list && $enforce ) {
			$this->chdir( $dirName );
			foreach ( $list as $v ) {
				$this->f_delete( $v );
			}
		} elseif ( $list && ! $enforce ) {
			$this->err_code = 3;
			
			return FALSE;
		}
		@ftp_rmdir( $this->link, $dirName );
		
		return TRUE;
	}
	
	/**
	 * 删除指定文件
	 *
	 * @param string $filename 文件名
	 *
	 * @return bool
	 */
	public function delete( $filename ) {
		if ( ! $this->link ) {
			$this->err_code = 2;
			
			return FALSE;
		}
		if ( @ftp_delete( $this->link, $filename ) ) {
			return TRUE;
		} else {
			$this->err_code = 4;
			
			return FALSE;
		}
	}
	
	/**
	 * 返回给定目录的文件列表
	 *
	 * @param string $dirName 目录地址
	 *
	 * @return bool
	 */
	public function nlist( $dirName ) {
		if ( ! $this->link ) {
			$this->err_code = 2;
			
			return FALSE;
		}
		if ( $list = @ftp_nlist( $this->link, $dirName ) ) {
			return $list;
		} else {
			$this->err_code = 5;
			
			return FALSE;
		}
	}
	
	/**
	 * 在 FTP 服务器上改变当前目录
	 *
	 * @param string $dirName 修改服务器上当前目录
	 *
	 * @return bool
	 */
	public function chdir( $dirName ) {
		if ( ! $this->link ) {
			$this->err_code = 2;
			
			return FALSE;
		}
		if ( @ftp_chdir( $this->link, $dirName ) ) {
			return TRUE;
		} else {
			$this->err_code = 6;
			
			return FALSE;
		}
	}
	
	/**
	 * 获取错误信息
	 */
	public function get_error() {
		if ( ! $this->err_code ) {
			return FALSE;
		}
		$err_msg = [
			'1' => 'Server can not connect',
			'2' => 'Not connect to server',
			'3' => 'Can not delete non-empty folder',
			'4' => 'Can not delete file',
			'5' => 'Can not get file list',
			'6' => 'Can not change the current directory on the server',
			'7' => 'Can not upload files'
		];
		
		return $err_msg[ $this->err_code ];
	}
	
	/**
	 * 检测目录名
	 *
	 * @param string $url 目录
	 *
	 * @return 由 / 分开的返回数组
	 */
	private function ck_dirname( $url ) {
		$url  = str_replace( '', '/', $url );
		$urls = explode( '/', $url );
		
		return $urls;
	}
	
	/**
	 * 关闭FTP连接
	 */
	public function close() {
		return @ftp_close( $this->link );
	}
	
}
