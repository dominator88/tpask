<?php


/**
 * api 返回数据
 *
 * @param $msg
 * @param mixed $code_or_data
 * @param array $data
 *
 * @return array
 */
function api_result( $msg, $code_or_data = 500, $data = [] ) {
	$result = [
		'msg' => $msg
	];
	
	if ( is_array( $code_or_data ) ) {
		$result['code'] = 0;
		$data           = array_merge( $code_or_data, $data );
	} else {
		$result['code'] = $code_or_data;
	}
	
	if ( ! empty( $data ) ) {
		$result['data'] = $data;
	}
	
	return $result;
}