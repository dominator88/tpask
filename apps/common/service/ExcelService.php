<?php
/**
 * Excel Service
 *
 * @author  Zix
 * @version 2.0 2016-10-21
 */

namespace apps\common\service;

use PHPExcel;

class ExcelService {
  
  public $error          = '';
  public $allowFileExt   = [ 'xls' , 'xlsx' ];
  public $fileExt        = 'xls';
  public $withTitle      = TRUE;
  public $withHeader     = TRUE;
  public $withFooter     = TRUE;
  public $exportTitle    = '';
  public $metaData       = [];
  public $format         = [];
  public $exportFilename = '';
  public $importFilePath = '';
  public $cellName       = [
    'A' ,
    'B' ,
    'C' ,
    'D' ,
    'E' ,
    'F' ,
    'G' ,
    'H' ,
    'I' ,
    'J' ,
    'K' ,
    'L' ,
    'M' ,
    'N' ,
    'O' ,
    'P' ,
    'Q' ,
    'R' ,
    'S' ,
    'T' ,
    'U' ,
    'V' ,
    'W' ,
    'X' ,
    'Y' ,
    'Z' ,
    'AA' ,
    'AB' ,
    'AC' ,
    'AD' ,
    'AE' ,
    'AF' ,
    'AG' ,
    'AH' ,
    'AI' ,
    'AJ' ,
    'AK' ,
    'AL' ,
    'AM' ,
    'AN' ,
    'AO' ,
    'AP' ,
    'AQ' ,
    'AR' ,
    'AS' ,
    'AT' ,
    'AU' ,
    'AV' ,
    'AW' ,
    'AX' ,
    'AY' ,
    'AZ'
  ];
  
  //类实例
  private static $instance;
  
  //单例化类
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance = new ExcelService();
    }
    
    return self::$instance;
  }
  
  public function getError() {
    return $this->error;
  }
  
  public function init() {
    $this->error          = '';
    $this->fileExt        = 'xls';
    $this->withTitle      = TRUE;
    $this->withHeader     = TRUE;
    $this->withFooter     = TRUE;
    $this->exportTitle    = '';
    $this->metaData       = [];
    $this->format         = [];
    $this->exportFilename = '';
    $this->importFilePath = '';
    
    return $this;
  }
  
  /**
   * 设置文件后缀
   *
   * @param string $type
   *
   * @return $this
   */
  public function setFileExt( $type = 'xls' ) {
    if ( in_array( $type , $this->allowFileExt ) ) {
      $this->fileExt = $type;
    } else {
      $this->fileExt = 'xls';
    }
    
    return $this;
  }
  
  /**
   * 是否显示标题
   *
   * @param bool $isWithTitle
   *
   * @return $this
   */
  public function isWithTitle( $isWithTitle = TRUE ) {
    $this->withTitle = $isWithTitle;
    
    return $this;
  }
  
  /**
   * 是否显示表头
   *
   * @param bool $isWithHeader
   *
   * @return $this
   */
  public function isWithHeader( $isWithHeader = TRUE ) {
    $this->withHeader = $isWithHeader;
    
    return $this;
  }
  
  /**
   * 设置title
   *
   * @param $title
   *
   * @return $this
   */
  public function setTitle( $title ) {
    $this->exportTitle = $title;
    
    return $this;
  }
  
  /**
   * 设置导出数据
   *
   * @param $data
   *
   * @return $this
   */
  public function setData( $data ) {
    $this->metaData = $data;
    
    return $this;
  }
  
  /**
   * 设置格式化数组
   *
   * @param $format
   *
   * @return $this
   */
  public function setFormat( $format ) {
    $this->format = $format;
    
    return $this;
  }
  
  private function setFilename( $filename = '' ) {
    if ( empty( $filename ) ) {
      if ( ! empty( $this->exportTitle ) ) {
        $this->exportFilename = $this->exportTitle . '-' . date( 'Ymd-Hi' ) . '.' . $this->fileExt;
      } else {
        $this->exportFilename = '未命名' . '-' . date( 'Ymd-Hi' ) . '.' . $this->fileExt;
      }
      
    } else {
      $this->exportFilename = $filename . '.' . $this->fileExt;
    }
  }
  
  //设置导入文件的路径
  public function setImportFilePath( $filePath ) {
    $this->importFilePath = $filePath;
    
    return $this;
  }
  
  /**
   * 导出 Excel文件
   * $ExcelService = ExcelService::instance();
   *
   * $data = [
   *  [
   *    'id'   => 1 ,
   *    'name' => 'John Doe' ,
   *    'icon' => 'imgUri'
   *  ] ,
   *  [
   *    'id'   => 2 ,
   *    'name' => 'Jane Doe' ,
   *    'icon' => NULL ,
   *  ] ,
   * ];
   *
   * //格式化方法
   * $formatIcon = function ( $value , $row = [] ) {
   *  return config( 'custom.imgUri' ) . $value;
   * };
   *
   * $format = [
   *  'id'   => '编号' ,
   *  'name' => '姓名' ,
   *  'icon' => [
   *    '头像' ,
   *    $formatIcon
   *  ]
   * ];
   *
   * $ExcelService
   *  ->init()
   *  ->setData( $data )
   *  ->setFormat( $format )
   *  ->download();
   *
   * @param string $filename
   */
  public function download( $filename = '' ) {
    $this->setFilename( $filename );
    
    $cols = count( $this->format );
    $rows = count( $this->metaData );
    $row  = 1;
    
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->setActiveSheetIndex( 0 );
    
    $objSheet = $objPHPExcel->getActiveSheet();
    
    //设置title
    if ( $this->withTitle ) {
      $objSheet->mergeCells( 'A1:' . $this->cellName[ $cols - 1 ] . '1' ); //合并单元格
      $objSheet->setCellValue( 'A1' , $this->exportTitle );
      $objSheet->getStyle( 'A1' )->getFont()->setSize( 20 );
      $objSheet->getStyle( 'A1' )->getAlignment()
               ->setHorizontal( \PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
      
      //设置时间
      $objSheet->mergeCells( 'A2:' . $this->cellName[ $cols - 1 ] . '2' ); //合并单元格
      $objSheet->setCellValue( 'A2' , '导出时间:' . date( 'Y-m-d H:i:s' ) );
      $objSheet->getStyle( 'A2' )->getAlignment()
               ->setHorizontal( \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT );
      
      $row += 2;
    }
    
    //表格 header
    if ( $this->withHeader ) {
      $i = 0;
      foreach ( $this->format as $field => $text ) {
        $realText = is_array( $text ) ? $text[0] : $text;
        $objSheet->setCellValue( $this->cellName[ $i ] . $row , $realText );
        $i ++;
      }
      
      $row += 1;
    }
    
    //表格内容
    foreach ( $this->metaData as $j => $item ) {
      $i = 0;
      foreach ( $this->format as $field => $text ) {
        //检查是否需要格式化数据
        $value = is_array( $text ) ? $text[1]( $item[ $field ] , $item ) : $item[ $field ];
        $objSheet->setCellValue( $this->cellName[ $i ] . $row , $value );
        $i ++;
      }
      $row ++;
    }
    
    if ( $this->withFooter ) {
      //设置文件尾
      $objSheet->mergeCells( 'A' . $row . ':' . $this->cellName[ $cols - 1 ] . $row ); //合并单元格
      $objSheet->setCellValue( 'A' . $row , '共导出 ' . $rows . ' 行数据' );
      $objSheet->getStyle( 'A' . $row )->getAlignment()
               ->setHorizontal( \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT );
    }
    
    // Set document properties
    $objPHPExcel->getProperties()
                ->setCreator( config( 'custom.author' ) )
                ->setTitle( $this->exportTitle );
    
    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex( 0 );
    
    $method = 'export' . strtoupper( $this->fileExt );
    $this->$method( $objPHPExcel );
  }
  
  /**
   * 导出 xls excel 97格式 文件
   *
   * @param $objPHPExcel
   */
  private function exportXLS( $objPHPExcel ) {
    // Redirect output to a client’s web browser (Excel5)
    header( 'Content-Type: application/vnd.ms-excel' );
    header( 'Content-Disposition: attachment;filename="' . $this->exportFilename . '"' );
    header( 'Cache-Control: max-age=0' );
    // If you're serving to IE 9, then the following may be needed
    header( 'Cache-Control: max-age=1' );
    
    // If you're serving to IE over SSL, then the following may be needed
    header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ); // always modified
    header( 'Cache-Control: cache, must-revalidate' ); // HTTP/1.1
    header( 'Pragma: public' ); // HTTP/1.0
    
    $objWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel , 'Excel5' );
    $objWriter->save( 'php://output' );
    exit;
  }
  
  /**
   * 导出 xlsx excel 2007 格式 文件
   *
   * @param $objPHPExcel
   */
  private function exportXLSX( $objPHPExcel ) {
    // Redirect output to a client’s web browser (Excel2007)
    header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
    header( 'Content-Disposition: attachment;filename="' . $this->exportFilename . '"' );
    header( 'Cache-Control: max-age=0' );
    // If you're serving to IE 9, then the following may be needed
    header( 'Cache-Control: max-age=1' );
    
    // If you're serving to IE over SSL, then the following may be needed
    header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ); // always modified
    header( 'Cache-Control: cache, must-revalidate' ); // HTTP/1.1
    header( 'Pragma: public' ); // HTTP/1.0
    
    $objWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel , 'Excel2007' );
    $objWriter->save( 'php://output' );
    exit;
  }
  
  
  /**
   * 文件转 数组 用于批量插入
   * Example :
   *
   * $ExcelService = ExcelService::instance();
   *
   * $filePath = './upload/test.xls';
   * //格式化方法
   * $formatIcon = function ( $value , $row = [] ) {
   *   return config( 'custom.imgUri' ) . $value;
   * };
   *
   * $format = [
   *  'A' => 'id' ,
   *  'B' => 'name' ,
   *  'C' => [
   *  'icon' ,
   *    $formatIcon
   *  ]
   * ];
   *
   * $data = $ExcelService
   *  ->init()
   *  ->setImportFilePath( $filePath )
   *  ->setFormat( $format )
   *  ->importToArray();
   *
   * @return array
   */
  public function importToArray() {
    $ext = strtolower( pathinfo( $this->importFilePath , PATHINFO_EXTENSION ) );
    
    //创建PHPExcel对象，注意，不能少了\
    //如果excel文件后缀名为.xls，导入这个类
    if ( $ext == 'xls' ) {
      $PHPReader = new \PHPExcel_Reader_Excel5();
    } else if ( $ext == 'xlsx' ) {
      $PHPReader = new \PHPExcel_Reader_Excel2007();
    } else {
      $this->error = '未知文件类型';
      
      return [];
    }
    
    
    //载入文件
    $PHPExcel = $PHPReader->load( $this->importFilePath );
    //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
    $currentSheet = $PHPExcel->getSheet( 0 );
    //获取总列数
    $allColumn = $currentSheet->getHighestColumn();
    //获取总行数
    $allRow = $currentSheet->getHighestRow();
    $data   = [];
    //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
    for ( $currentRow = 2; $currentRow <= $allRow; $currentRow ++ ) {
      //从哪列开始，A表示第一列
      for ( $currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn ++ ) {
        //数据坐标
        $address = $currentColumn . $currentRow;
        //读取到的数据，保存到数组$arr中
        $data[ $currentRow ][ $currentColumn ] = $currentSheet->getCell( $address )->getValue();
      }
    }
    
    if ( empty( $data ) ) {
      $this->error = '没有数据';
      
      return [];
    }
    
    if ( ! empty( $this->format ) ) {
      $newData = [];
      foreach ( $data as $item ) {
        $newItem = [];
        foreach ( $this->format as $text => $field ) {
          if ( is_array( $field ) ) {
            $newItem[ $field[0] ] = $field[1]( $item[ $text ] , $item );
          } else {
            $newItem[ $field ] = $item[ $text ];
          }
        }
        $newData[] = $newItem;
      }
      
      return $newData;
    }
    
    return $data;
  }
  
}