<?php
/**
 * 公共函数类
 *
 * @author wanglibing <toowind007@gmail.com>
 * @version $Id: Fn.php 29 2014-09-26 05:52:01Z wanglibing $
 */
class Fn
{
	/**
	 * 用 mb_strimwidth 来截取字符，使中英尽量对齐。
	 *
	 * @param string $str
	 * @param int $start
	 * @param int $width
	 * @param string $trimmarker
	 * @return string
	 */
	public static function wsubstr($str, $start, $width, $trimmarker = '...')
	{
		$_encoding = mb_detect_encoding($str, array('ASCII','UTF-8','GB2312','GBK','BIG5'));
		return mb_strimwidth($str, $start, $width, $trimmarker, $_encoding);
	}

	/**
	 * 过滤浮点数，如果是整数返回整数
	 *
	 * @param string $str
	 * @return string
	 */
	public static function filterFloat($str)
	{
		if ($str == intval($str)) {
			return $str;
		}
		return sprintf("%0.2f", $str);
	}

	/**
	 * 实现PHP内部函数 trim 处理多维数组。
	 *
	 * @param string|array &$data
	 * @param string $charlist
	 */
	public static function retrim($data, $charlist = null)
	{
		if (is_array($data)) {
			foreach ($data as $item) {
				$data = self::retrim($item);
			}
		} else {
			$data = trim($data, $charlist);
		}

		return $data;
	}

	/**
	 * 判断并转换字符编码，需 mb_string 模块支持。
	 *
	 * @param mixed $str 数据
	 * @param string $encoding 要转换的编码类型
	 * @return mixed 转换过的数据
	 */
	public static function encodingConvert($str, $encoding = 'UTF-8')
	{
		if (is_array($str)) {
			$arr = array();
			foreach ($str as $key => $val) {
				$arr[$key] = self::encodingConvert($val, $encoding);
			}

			return $arr;
		}

		$_encoding = mb_detect_encoding($str, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
		if ($_encoding == $encoding) {
			return $str;
		}

		return mb_convert_encoding($str, $encoding, $_encoding);
	}
	/**
	 * 生成静态资源版本号
	 * @return string
	 */
	public static function setVersion(){
		//$version=intval(date("His"));
		$version=160343;
		return $version;
	}
	/**
	 * 获取IP地址，可能获取代理IP地址。
	 *
	 * @return string
	 */
	public static function getIp()
	{
		static $ip = false;

		if (false != $ip) {
			return $ip;
		}

		$keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		);

		foreach ($keys as $item) {
			if (!isset($_SERVER[$item])) {
				continue;
			}

			$curIp = $_SERVER[$item];
			$curIp = explode('.', $curIp);
			if (count($curIp) != 4) {
				break;
			}

			foreach ($curIp as & $sub) {
				if (($sub = intval($sub)) < 0 || $sub > 255) {
					break 2;
				}
			}

			$curIpBin = $curIp[0] << 24 | $curIp[1] << 16 | $curIp[2] << 8 | $curIp[3];
			$masks = array( // hexadecimal ip  ip mask
				array(0x7F000001, 0xFFFF0000),  // 127.0.*.*
				array(0x0A000000, 0xFFFF0000),  // 10.0.*.*
				array(0xC0A80000, 0xFFFF0000) // 192.168.*.*
			);
			foreach ($masks as $ipMask) {
				if (($curIpBin & $ipMask[1]) === ($ipMask[0] & $ipMask[1])) {
					break 2;
				}
			}

			return $ip = implode('.', $curIp);
		}

		return $ip = $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 加密，解密方法。
	 *
	 * @param string $string
	 * @param string $key
	 * @param string $operation encode|decode
	 * @return string
	 */
	public static function crypt($string, $key, $operation = 'encode')
	{
		$keyLength = strlen($key);
		$string = (strtolower($operation) == 'decode')
				? base64_decode($string)
				: substr(md5($string . $key) , 0, 8) . $string;
		$stringLength = strlen($string);
		$rndkey = $box = array();
		$result = '';

		for ($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($key[$i % $keyLength]);
			$box[$i] = $i;
		}

		for ($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}

		for ($a = $j = $i = 0; $i < $stringLength; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}

		if (strtolower($operation) == 'decode') {
			if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key) , 0, 8)) {
				return substr($result, 8);
			} else {
				return '';
			}
		} else {
			return base64_encode($result);
		}
	}

	/**
	 * 通过CURL库进POST数据提交
	 *
	 * @param string $postUrl  url address
	 * @param array $data  post data
	 * @param int $timeout connect time out
	 * @param bool $debug 打开 header 数据
	 * @return string
	 */
	public static function curlPost($postUrl, $data = array(), $timeout = 30, $debug = false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $postUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, $debug);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, $debug);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, 'pre_', '&'));

		$result = curl_exec($ch);
		curl_close($ch);

		if ($result === false) {
			return $result;
		}

		return trim($result);
	}

	/**
	 * 对 SQL like 值进行转义。
	 *
	 * @param string $keyword
	 * @return string
	 */
	public function escapeKeyword($keyword)
	{
		return strtr($keyword, array('%' => '\%', '_' => '\_'));
	}

	/**
	 * 字符串转义
	 *
	 * @param string $string
	 * @return string
	 */
	public static function daddslashes($string)
	{

		static $magic = null;
		if ($magic === null) {
			$magic = (bool) get_magic_quotes_gpc();
		}

		if (is_array($string)) {
			foreach ($string as $key => $val) {
				$string[$key] = str_replace('%20','',$string[$key]);
				$string[$key] = str_replace('%27','',$string[$key]);
				$string[$key] = str_replace('%2527','',$string[$key]);
				$string[$key] = str_replace(' ','',$string[$key]);

				$string[$key] = self::daddslashes($val);
			}
		} else {
			$string = str_replace('%20','',$string);
			$string = str_replace('%27','',$string);
			$string = str_replace('%2527','',$string);
			$string = str_replace(' ','',$string);

			$string = $magic ? $string : addslashes($string);
		}
		return $string;
	}

	/**
	 * 输入过滤
	 *
	 * @param string $k
	 * @param string $var	分别代表不同超全局变量
	 * @return array
	 * @deprecated {@see superVar()}
	 */
	public static function getgpc($k, $var = 'R')
	{
		switch ($var) {
			case 'G':
				$var = &$_GET;
				break;
			case 'P':
				$var = &$_POST;
				break;
			case 'C':
				$var = &$_COOKIE;
				break;
			case 'R':
				$var = &$_REQUEST;
				break;
		}
		return isset($var[$k]) ? self::daddslashes($var[$k]) : null;
	}

	/**
	 * 对外部来源超全局变量进行转义
	 *
	 * @param string $key
	 * @param string $type
	 * @param mixed $default
	 * @return mixed
	 */
	public static function superVar($key = null, $type = 'R', $default = null)
	{
		switch ($type) {
			case 'G':
				$var = $_GET;
				break;
			case 'P':
				$var = $_POST;
				break;
			case 'C':
				$var = $_COOKIE;
				break;
			default:
				$var = $_REQUEST;
				break;
		}

		if ($key === null) {
			return self::daddslashes($var);
		}

		return isset($var[$key]) ? self::daddslashes($var[$key]) : $default;
	}

	/**
	 * 用于显示 yiidebugtb调试
	 *
	 * @param bool $on
	 * @param bool $return
	 * @return bool
	 */
	public static function debug($on = true, $return = false)
	{
		static $debug = false;
		if ($return) {
			return $debug;
		}

		$debug = $on;
	}

	/**
	 * 处理数组生成sql
	 *
	 * @param array $arr
	 * @param string $k
	 * @return string
	 */
	public static function createSqlIn($arr, $k)
	{
		$str = array();
		foreach ($arr as $v) {
			$str[]= $v[$k];
		}
		return implode(', ', $str);
	}

	/**
	 * 格式化价格
	 *
	 * @param float $price
	 * @param int $float 精确到小数点后$float位
	 * @return array
	 */
	public static function formatPrice($price, $float = 2)
	{
		return number_format($price, $float, '.', '');
	}

	public static function dump($object, $t = 0)
	{
		if ($t == 0) {
			print_r($object);
			exit();
		} elseif ($t == 1) {
			print_r(JHelper::toArray($object));
			exit();
		} elseif ($t == 2) {
			var_dump($object);
			exit();
		} elseif ($t == 3) {
			exit($object);
		}
	}

	/**
	 * create pagebreak
	 *
	 * @param string $format
	 * @param int $page
	 * @param bool $isReplace
	 * @return string
	 */
	public static function pagerLink($format, $page, $isReplace = false)
	{
		if ($isReplace) {
			return str_replace('{%d}', $page, $format);
		}

		return sprintf($format, $page);
	}

	/**
	 * 清除squid缓存
	 */
	public static function clearCache()
	{
		header('Expires: Thu, 01 Jan 1970 00:00:01 GMT');
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
	}

}

