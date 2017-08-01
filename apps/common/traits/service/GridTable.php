<?php
/**
 * GridTable Trait for Service
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 1.0 @ 2016-09-08
 */

namespace apps\common\traits\service;

trait GridTable {
  /**
   * 根据id 查询
   *
   * @param $id
   *
   * @return mixed
   */
  public function getById( $id ) {
    return $this->model->find( $id );
  }
  
  /**
   * 添加数据
   *
   * @param $data
   *
   * @return array
   */
  public function insert( $data ) {
    try {
      if ( empty( $data ) ) {
        throw new \Exception( '数据不能为空' );
      }
      
      $id = $this->model->insertGetId( $data );
      
      return ajax_arr( '创建成功' , 0 , [ 'id' => $id ] );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 根据ID 更新数据
   *
   * @param $id
   * @param $data
   *
   * @return array
   */
  public function update( $id , $data ) {
    try {
      $rows = $this->model->where( 'id' , $id )->update( $data );
      if ( $rows == 0 ) {
        return ajax_arr( "未更新任何数据" , 0 );
      }
      
      return ajax_arr( "更新成功" , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 根据ID 删除数据
   *
   * @param $ids //string | array
   *
   * @return array
   */
  public function destroy( $ids ) {
    try {
      $rows = $this->model->delete( $ids );
      if ( $rows == 0 ) {
        return ajax_arr( "未删除任何数据" , 0 );
      }
      
      return ajax_arr( "成功删除{$rows}行数据" , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
}