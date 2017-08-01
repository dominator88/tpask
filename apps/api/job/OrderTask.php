<?php namespace apps\api\job;

/**
 * Created by PhpStorm.
 * User: Zix
 * Date: 2016/10/14
 * Time: 下午5:01
 */

use think\queue\Job;
use apps\common\service\MerOrderService;

class OrderTask {
  
  const ticketPrefix = 'dmgapp_order_ticket_';
  const ticketExpire = 3600;
  
  public $debug = FALSE;
  
  public function fire( Job $job , $data ) {
    
    if ( $this->debug ) {
      $this->logs( $data );
    }
    
    //通过这个方法可以检查这个任务已经重试了几次了
    if ( $job->attempts() > 10 ) {
      $this->logs( $data , 'error' );
      $job->delete();
      
    }
    
    $ticket   = $data['ticket'];
    $MerOrder = MerOrderService::instance();
    $result   = $MerOrder->insert( $data );
    
    $cacheName = self::ticketPrefix . $ticket;
    cache( $cacheName , $result , self::ticketExpire );
    
    //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
    $job->delete();
    
    //....这里执行具体的任务
//    if ( $job->attempts() > 3 ) {
//      //通过这个方法可以检查这个任务已经重试了几次了
//    }
    
    // 也可以重新发布这个任务
//    $job->release( $delay ); //$delay为延迟时间
    
  }
  
  public function failed( $data ) {
    // ...任务达到最大重试次数后，失败了
    $this->logs( $data , 'error' );
  }
  
  private function logs( $data , $type = 'log' ) {
    $date = date( 'Y_m_d_' );
    if ( $type == 'error' ) {
      $logPath = "./public/logs/{$date}order_queue_error.txt";
    } else {
      $logPath = "./public/logs/{$date}order_queue.txt";
    }
    
    file_put_contents( $logPath , print_r( $data , TRUE ) , FILE_APPEND );
  }
  
}