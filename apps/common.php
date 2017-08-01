<?php
// 应用公共文件

if ( ! function_exists( 'game_over' ) ) {
  /**
   * 程序终止并提示
   *
   * @param $str
   */
  function game_over( $str ) {
    header( 'Content-Type:text/html; charset=utf-8' );
    exit( $str );
  }
}


if ( ! function_exists( 'print_arr' ) ) {
  /**
   * 打印数组
   *
   * @param $arr
   */
  function print_arr( $arr ) {
    if ( is_string( $arr ) ) {
      $arr = json_decode( $arr , TRUE );
    }
    
    header( 'Content-Type:text/html; charset=utf-8' );
    echo '<pre>' . print_r( $arr , TRUE ) . '</pre>';
  }
}

if ( ! function_exists( 'get_device' ) ) {
  function get_device() {
    $agent = strtolower( request()->header( 'user-agent' ) );
    if ( strpos( $agent , 'iphone' ) ) {
      return 'iphone';
    } else if ( strpos( $agent , 'ipad' ) ) {
      return 'ipad';
    } else if ( strpos( $agent , 'android' ) ) {
      return 'android';
    } else if ( strpos( $agent , 'macintosh' ) ) {
      return 'mac';
    } else if ( strpos( $agent , 'windows' ) ) {
      return 'pc';
    } else if ( strpos( $agent , 'unix' ) ) {
      return 'unix';
    } else if ( strpos( $agent , 'linux' ) ) {
      return 'linux';
    } else {
      return 'unknown';
    }
  }
}


if ( ! function_exists( 'is_https' ) ) {
  /**
   * 判断是否 https 访问
   * @return bool
   */
  function is_https() {
    if ( ! empty( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) !== 'off' ) {
      return TRUE;
    } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
      return TRUE;
    } elseif ( ! empty( $_SERVER['HTTP_FRONT_END_HTTPS'] ) && strtolower( $_SERVER['HTTP_FRONT_END_HTTPS'] ) !== 'off' ) {
      return TRUE;
    }
    
    return FALSE;
  }
}

if ( ! function_exists( 'base_uri' ) ) {
  /**
   * 当前域名
   * @return string
   */
  function base_uri() {
    $uri = getenv( 'HTTP_HOST' );
    if ( is_https() ) {
      $uri = 'https://' . $uri;
    } else {
      $uri = 'http://' . $uri;
    }
    
    return $uri . "/";
  }
}

if ( ! function_exists( 'extend' ) ) {
  /**
   * 扩展数组
   *
   * @param $config
   * @param $default
   *
   * @return mixed
   */
  function extend( $default , $config ) {
    foreach ( $default as $key => $val ) {
      if ( ! isset( $config [ $key ] ) || $config[ $key ] === '' ) {
        $config [ $key ] = $val;
      } else if ( is_array( $config [ $key ] ) ) {
        $config [ $key ] = extend( $val , $config [ $key ] );
      }
    }
    
    return $config;
  }
}

if ( ! function_exists( 'get_all_headers' ) ) {
  function get_all_headers() {
    $headers = [];
    foreach ( $_SERVER as $name => $value ) {
      if ( substr( $name , 0 , 5 ) == 'HTTP_' ) {
        $headers[ str_replace( ' ' , '-' , strtolower( str_replace( '_' , ' ' , substr( $name , 5 ) ) ) ) ] = $value;
      }
    }
    
    return $headers;
  }
}

if ( ! function_exists( "ajax_arr" ) ) {
  /**
   * 生成需要返回 ajax 数组
   *
   * @param $msg        //消息
   * @param int $code   //0 正常 , > 0 错误
   * @param array $data //需要传递的参数
   *
   * @return array
   */
  function ajax_arr( $msg , $code = 500 , $data = [] ) {
    $arr = [
      'msg'  => $msg ,
      'code' => $code ,
    ];
    
    if ( $data !== '' ) {
      $arr['data'] = $data;
    }
    
    return $arr;
  }
}

if ( ! function_exists( 'form_checkbox' ) ) {
  /**
   * 水平radios
   *
   * @param $name
   * @param $data
   * @param int $checked_value
   *
   * @return mixed|string
   */
  function form_checkbox( $name , $data , $checked_value = 0 ) {
    $html = '';
    foreach ( $data as $key => $val ) {
      $html .= '<label class="checkbox-inline"><input name="' . $name . '[]" type="checkbox" value="' . $key . '" >' . $val . '</label>';
    }
    
    if ( $checked_value >= 0 ) {
      $html = str_replace( 'value="' . $checked_value . '"' , "value='$checked_value' checked" , $html );
    }
    
    return $html;
  }
}

if ( ! function_exists( 'form_checkbox_rows' ) ) {
  /**
   * checkbox
   *
   * @param $name
   * @param $data
   * @param string $key
   * @param string $val
   * @param int $checked_value
   *
   * @return mixed|string
   */
  function form_checkbox_rows( $name , $data , $key = 'id' , $val = 'name' , $checked_value = 0 ) {
    $html = '';
    foreach ( $data as $item ) {
      $html .= '<label class="checkbox-inline"><input name="' . $name . '[]" type="checkbox" value="' . $item[ $key ] . '" >' .
               $item[ $val ] . '</label>';
    }
    
    if ( $checked_value >= 0 ) {
      $html = str_replace( 'value="' . $checked_value . '"' , "value='$checked_value' checked" , $html );
    }
    
    return $html;
  }
}

if ( ! function_exists( 'form_radios' ) ) {
  /**
   * 水平radios
   *
   * @param $name
   * @param $data
   * @param int $checked_value
   *
   * @return mixed|string
   */
  function form_radios( $name , $data , $checked_value = 0 ) {
    $html = '';
    foreach ( $data as $key => $val ) {
      $html .= '<label class="radio-inline"><input name="' . $name . '" type="radio" value="' . $key . '" >' . $val . '</label>';
    }
    
    if ( $checked_value >= 0 ) {
      $html = str_replace( 'value="' . $checked_value . '"' , "value='$checked_value' checked" , $html );
    }
    
    return $html;
  }
}


if ( ! function_exists( 'form_options' ) ) {
  /**
   * 生成下拉选项
   *
   * @param $data
   * @param int $selected_value
   *
   * @return mixed|string
   */
  function form_options( $data , $selected_value = - 1 ) {
    $html = '';
    foreach ( $data as $key => $val ) {
      $html .= "<option value='$key'>$val</option>";
    }
    
    if ( $selected_value >= 0 ) {
      $html = str_replace( "value='$selected_value'" , "value='$selected_value' selected" , $html );
    }
    
    return $html;
  }
  
}

if ( ! function_exists( 'form_options_rows' ) ) {
  /**
   * 生成下拉选项 from rows
   *
   * @param $data
   * @param string $id
   * @param string $text
   * @param string $node_field
   * @param int $selected_value
   * @param array $dat
   *
   * @return mixed|string
   */
  function form_options_rows( $data , $id = 'id' , $text = "name" , $node_field = "children" , $selected_value = 0 , $dat = [] ) {
    $html = '';
    foreach ( $data as $row ) {
      $value  = $row [ $id ];
      $prefix = '';
      if ( isset( $row ['level'] ) ) {
        $prefix = $row ['level'] - 1 > 0 ? str_repeat( '&nbsp;&nbsp;&nbsp;&nbsp;' , $row ['level'] - 1 ) . '└─ ' : ''; // ┗
      }
      $title = $prefix . $row [ $text ];
      $d     = '';
      foreach ( $dat as $p ) {
        $d .= sprintf( ' data-%s="%s"' , $p , $row [ $p ] );
      }
      $html .= sprintf( '<option value="%s" %s>%s</option>' , $value , $d , $title );
      
      if ( isset( $row [ $node_field ] ) ) {
        $html .= form_options_rows( $row [ $node_field ] , $id , $text , 0 , $row ['level'] + 1 );
      }
    }
    
    if ( ! empty( $selected_value ) ) {
      $html = str_replace( 'value="' . $selected_value . '"' , 'value="' . $selected_value . '" selected' , $html );
    }
    
    return $html;
  }
  
}

if ( ! function_exists( 'form_options_rows_group' ) ) {
  /**
   * optgroup 显示 options
   *
   * @param $data
   * @param $valueField
   * @param $textField
   * @param $groupField
   *
   * @return string
   */
  function form_options_rows_group( $data , $valueField = 'id' , $textField = 'text' , $groupField = 'type_text' ) {
    $newData = [];
    foreach ( $data as $item ) {
      $newData[ $item[ $groupField ] ][] = $item;
    }
    
    $html = '';
    foreach ( $newData as $key => $row ) {
      $html .= '<optgroup label="' . $key . '">';
      foreach ( $row as $r ) {
        $html .= '<option value="' . $r[ $valueField ] . '">' . $r[ $textField ] . '</option> ';
      }
      $html .= '</optgroup> ';
    }
    
    return $html;
  }
}


if ( ! function_exists( 'form_options_arr' ) ) {
  /**
   * 生成下拉选项 from array
   *
   * @param $data
   *
   * @return string
   */
  function form_options_arr( $data ) {
    $html = '';
    foreach ( $data as $val ) {
      $html .= '<option value="' . $val . '">' . $val . '</option>';
    }
    
    return $html;
  }
}

if ( ! function_exists( 'rand_string' ) ) {
  /**
   * 生成随机字符串
   *
   * @param $length
   *
   * @return string
   */
  function rand_string( $length = 6 ) {
    $str    = NULL;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max    = strlen( $strPol ) - 1;
    
    for ( $i = 0; $i < $length; $i ++ ) {
      $str .= $strPol [ rand( 0 , $max ) ]; // rand($min,$max)生成介于min和max两个数之间的一个随机整数
    }
    
    return $str;
  }
}

if ( ! function_exists( 'full_uri' ) ) {
  function full_uri( $uri , $param = [] ) {
    return url( $uri , $param , '' , TRUE );
  }
}

if ( ! function_exists( 'current_url' ) ) {
  /**
   * 获取当前uri
   * @return string
   */
  function current_url() {
    $sys_protocol = isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self     = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $path_info    = isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '';
    $relate_url   = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : $php_self . ( isset( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : $path_info );
    
    return $sys_protocol . ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' ) . $relate_url;
  }
}

if ( ! function_exists( 'str2pwd' ) ) {
  /**
   * 字符串加密
   *
   * @param $str
   *
   * @return bool|string
   */
  function str2pwd( $str ) {
    return password_hash( $str , PASSWORD_BCRYPT , [ "cost" => 10 ] );
  }
}

if ( ! function_exists( 'full_img_uri' ) ) {
  /**
   * 返回图片绝对路径
   *
   * @param $imgUri
   *
   * @return string
   */
  function full_img_uri( $imgUri ) {
    if ( config( 'custom.uploadType' ) == 'local' ) {
      return url( $imgUri , '' , '' , TRUE );
    }
    
    return config( 'custom.imgUri' ) . $imgUri;
  }
}

if ( ! function_exists( 'full_img_uri_data_fields' ) ) {
  /**
   * 添加数据中的图片 字段 uri
   * 兼容 数组类型 和 对象类型的 array
   *
   * @param $data
   * @param array $fields
   *
   * @return mixed
   */
  function full_img_uri_data_fields( $data , $fields = [] ) {
    if ( empty( $fields ) ) {
      return $data;
    }
    
    if ( ! is_array( $fields ) ) {
      $fields = [ $fields ];
    }
    
    if ( isset( $data[0] ) ) {
      foreach ( $data as &$item ) {
        foreach ( $fields as $field ) {
          $item[ $field ] = empty( $item[ $field ] ) ? '' : full_img_uri( $item[ $field ] );
        }
      }
    } else {
      foreach ( $fields as $field ) {
        $data[ $field ] = empty( $data[ $field ] ) ? '' : full_img_uri( $data[ $field ] );
      }
    }
    
    return $data;
  }
}

if ( ! function_exists( 'get_thumb' ) ) {
  /**
   * 获取缩略图
   *
   * @param $save_path
   * @param int $size
   *
   * @return string
   */
  function get_thumb( $save_path , $size = 220 ) {
    $path_info = pathinfo( $save_path );
    
    return $path_info ['dirname'] . '/' . $path_info ['filename'] . '.' . $size . '.' . $path_info ['extension'];
  }
}


if ( ! function_exists( 'file_size' ) ) {
  function file_size( $size ) {
    $unit  = [ 'B' , 'KB' , 'MB' , 'GB' , 'TB' , 'PB' ];
    $index = 0;
    do {
      $size = $size / 1024;
      $index ++;
    } while ( $size >= 1024 );
    
    return round( $size , 1 ) . $unit[ $index ];
  }
}





