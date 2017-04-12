<?php
/**
 * 公共函数库
 *
 * @author 			WangYanmeng
 * @Date			2014-10-8
 * @lastmodify		2014-10-8
 */
class Tool {

   /**
	* 产生随机字符串
	*
	* @param    int        $length  输出长度
	* @param    string     $chars   可选的 ，默认为 0123456789
	* @return   string     字符串
	*/
	static function random($length, $chars = '0123456789') {
		$hash = '';
		$max = strlen($chars) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $chars[mt_rand(0, $max)];
		}
		return $hash;
	}
	/**
	 * 获得来源类型 post get
	 *
	 * @return unknown
	 */
	static public function method() {
		return strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' );
	}
    /**
	 * 安全过滤函数
	 *
	 * @param $string
	 * @return string
	 */
	public static function safe_replace($string)
	{
		$string = str_replace('%20','',$string);
		$string = str_replace('%27','',$string);
		$string = str_replace('%2527','',$string);
		$string = str_replace('*','',$string);
		$string = str_replace('"','&quot;',$string);
		$string = str_replace("'",'',$string);
		$string = str_replace('"','',$string);
		$string = str_replace(';','',$string);
		$string = str_replace('<','&lt;',$string);
		$string = str_replace('>','&gt;',$string);
		$string = str_replace("{",'',$string);
		$string = str_replace('}','',$string);
		$string = str_replace('\\','',$string);
		return $string;
	}

	/**
	 * 加密函数
	 * @param string $txt
	 * @param string $key
	 */
	public static function encrypt($txt, $key = '')
	{
		$rnd = md5(microtime());
		$len = strlen($txt);
		$ren = strlen($rnd);
		$ctr = 0;
		$str = '';
		for($i = 0; $i < $len; $i++)
		{
			$ctr = $ctr == $ren ? 0 : $ctr;
			$str .= $rnd[$ctr].($txt[$i] ^ $rnd[$ctr++]);
		}
		return str_replace('=', '', base64_encode(self::kecrypt($str, $key)));
	}

	/**
	 * 解密函数
	 * @param string $txt
	 * @param string $key
	 * @return string
	 */
	public static function decrypt($txt, $key = '')
	{
		$txt = self::kecrypt(base64_decode($txt), $key);
		$len = strlen($txt);
		$str = '';
		for($i = 0; $i < $len; $i++)
		{
			$tmp = $txt[$i];
			$str .= $txt[++$i] ^ $tmp;
		}
		return $str;
	}

	/**
	 * 密钥对明文生成密文
	 * @param string $txt
	 * @param string $key
	 * @return string
	 */
	private static function kecrypt($txt, $key)
	{
		$key = md5($key);
		$len = strlen($txt);
		$ken = strlen($key);
		$ctr = 0;
		$str = '';
		for($i = 0; $i < $len; $i++)
		{
			$ctr = $ctr == $ken ? 0 : $ctr;
			$str .= $txt[$i] ^ $key[$ctr++];
		}
		return $str;
	}

	/**
	 * 字符截取 支持UTF8/GBK
	 * @param $string
	 * @param $length
	 * @param $dot
	 */
	public static function str_cut($string, $length, $dot = '...')
	{
		//define('CHARSET','utf-8');
		$strlen = strlen($string);
		if($strlen <= $length) return $string;
		$string = str_replace(array(' ','&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array('∵',' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $string);
		$strcut = '';
		if(1) {
			$length = intval($length-strlen($dot)-$length/3);
			$n = $tn = $noc = 0;
			while($n < strlen($string)) {
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1; $n++; $noc++;
				} elseif(194 <= $t && $t <= 223) {
					$tn = 2; $n += 2; $noc += 2;
				} elseif(224 <= $t && $t <= 239) {
					$tn = 3; $n += 3; $noc += 2;
				} elseif(240 <= $t && $t <= 247) {
					$tn = 4; $n += 4; $noc += 2;
				} elseif(248 <= $t && $t <= 251) {
					$tn = 5; $n += 5; $noc += 2;
				} elseif($t == 252 || $t == 253) {
					$tn = 6; $n += 6; $noc += 2;
				} else {
					$n++;
				}
				if($noc >= $length) {
					break;
				}
			}
			if($noc > $length) {
				$n -= $tn;
			}
			$strcut = substr($string, 0, $n);
			$strcut = str_replace(array('∵', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), array(' ', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), $strcut);
		} else {
			$dotlen = strlen($dot);
			$maxi = $length - $dotlen - 1;
			$current_str = '';
			$search_arr = array('&',' ', '"', "'", '“', '”', '—', '<', '>', '·', '…','∵');
			$replace_arr = array('&amp;','&nbsp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;',' ');
			$search_flip = array_flip($search_arr);
			for ($i = 0; $i < $maxi; $i++) {
				$current_str = ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
				if (in_array($current_str, $search_arr)) {
					$key = $search_flip[$current_str];
					$current_str = str_replace($search_arr[$key], $replace_arr[$key], $current_str);
				}
				$strcut .= $current_str;
			}
		}
		return $strcut.$dot;
	}


	/**
	 * 转换字节数为其他单位
	 *
	 * @param	string	$filesize	字节大小
	 * @return	string	返回大小
	 */
	public static function sizecount($filesize)
	{
		if ($filesize >= 1073741824) {
			$filesize = round($filesize / 1073741824 * 100) / 100 .' GB';
		} elseif ($filesize >= 1048576) {
			$filesize = round($filesize / 1048576 * 100) / 100 .' MB';
		} elseif($filesize >= 1024) {
			$filesize = round($filesize / 1024 * 100) / 100 . ' KB';
		} else {
			$filesize = $filesize.' Bytes';
		}
		return $filesize;
	}

	/**
	 * 字符串加密、解密函数
	 *
	 * @param	string	$txt		字符串
	 * @param	string	$operation	ENCODE为加密，DECODE为解密，可选参数，默认为ENCODE，
	 * @param	string	$key		密钥：数字、字母、下划线
	 * @param	string	$expiry		过期时间
	 * @return	string
	 */
	public static function sys_auth($string, $operation = 'ENCODE', $key = '', $expiry = 0)
	{
		$key_length = 4;
		$key = md5($key != '' ? $key : 'auth_key');
		$fixedkey = md5($key);
		$egiskeys = md5(substr($fixedkey, 16, 16));
		$runtokey = $key_length ? ($operation == 'ENCODE' ? substr(md5(microtime(true)), -$key_length) : substr($string, 0, $key_length)) : '';
		$keys = md5(substr($runtokey, 0, 16) . substr($fixedkey, 0, 16) . substr($runtokey, 16) . substr($fixedkey, 16));
		$string = $operation == 'ENCODE' ? sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$egiskeys), 0, 16) . $string : base64_decode(substr($string, $key_length));

		$i = 0; $result = '';
		$string_length = strlen($string);
		for ($i = 0; $i < $string_length; $i++){
			$result .= chr(ord($string{$i}) ^ ord($keys{$i % 32}));
		}
		if($operation == 'ENCODE') {
			return $runtokey . str_replace('=', '', base64_encode($result));
		} else {
			if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$egiskeys), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		}
	}


	/**
	 * 设置cookie
	 * @param	string	$cname	 cookie name
	 * @param	string	$value	 cookie value   
	 * @return	null
	*/
	public static function set_Cookie($cname,$value){
        $ck = new CHttpCookie($cname,$value);
        $ck->expire = time()+60*60*2;
        $ck->httpOnly = true;
        Yii::app()->request->cookies[$cname] = $ck;
	}

	/**
	 * 删除cookie
	 * @param	string	$cname	 cookie name 
	 * @return	null
	*/
	public static function unset_Cookie($cname){
        $cookie = Yii::app()->request->getCookies();
		unset($cookie[$cname]);
	}

	/**
	*读取cookie
	*@param	  string	$cname	 cookie name 
	*@return  unknown
	*/
	public static function read_Cookie($cname){
		$cvalue = Yii::app()->request->cookies[$cname];
		return $cvalue;
	}
	

	//返回json数组的结果
    public static function jsonRetArray($message,$status,$code=0,$data=''){
        $arra=array("message"=>$message,"status"=>$status,"code"=>$code,"data"=>$data);//var_dump($arra);
        return CJSON::encode($arra);
    }

    //返回json数组的结果
    public static function arrayToJson($status =0,$code =500,$message = '',$arr = array()){
        $arra=array("status"=>$status,"code"=>$code,"message"=>$message,"data"=>$arr);//var_dump($arra);
        return CJSON::encode($arra);
    }


	/**
	 * 往API post数据函数
	 *
	 * @param	string	$url		路径
	 * @param	array	$data		参数
	 * @return	json
	 */
	public static function curl_post($url,$data){
		//$uri = "http://api.mazhan.com/test.php";
		// 参数数组
		// $data = array (
		//      'name' => 'tanteng',
		// 		'password' => 'password'
		// );
		$ch = curl_init();
		curl_setopt ( $ch, CURLOPT_URL, API_URL.$url );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
		$result = curl_exec ( $ch );
		curl_close ( $ch );
		return json_decode($result);
	}
	
	/**
	 * 昵称验证
	 *规则：中文(2-6个汉字)
	 * @param	string	$nickname	昵称
	 * @return	1符合规则 0不符合规则
	*/
	public static function CheckNickName($nickname){

		//$namere = preg_match('/^[\x{4e00}-\x{9fa5}]{2,6}$/u', $nickname)?1:0;
		$namere = preg_match('/^[A-Za-z0-9\x{4e00}-\x{9fa5}]{2,16}$/u', $nickname)?1:0;
		return $namere; 
	}

	/**
	* 邮箱验证
	* @param	string	$email	邮箱地址
	* @return	1合法 0不合法
	*/
    public static function CheckEmail($email){
    	
    	$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
    	$emailre = preg_match($pattern,$email)?1:0;

    	return $emailre;

    }

    /**
	 * 手机号码验证
	 *规则：手机号码就为11位数字，以13/14/15/17/18开
	 * @param	string	$mobilephone	电话号码
	 * @return	1正确 0错误
	*/
	public static function CheckPhone($mobilephone){
		
		$phonere = preg_match("/^13[0-9]{9}$|14[0-9]{9}$|15[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$/",$mobilephone)?1:0;   
	    
		return $phonere;
	}

	
	/**
	 * 密码强度验证
	 *规则：数字 字母 特殊符号 包含项越多强度越强
	 * @param	string	$pass  密码
	 * @return	int 值越大 密码强度越强
	*/
	public static function CheckPss($pass){
		
		$passre = 0;
		//包含数字
		if(preg_match("/[0-9]+/",$pass)){
			$passre++;
		}
		//包含字母
		if(preg_match("/[A-Za-z]+/",$pass)){
			$passre++;
		}
		//包含特殊符号
		if(preg_match("/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)|\||<|>|?|\,|\.|\{|\}|\[|\]|;|'|\"]+/",$pass)){
			$passre++;	
		}

		return $passre;

	}
	/**
	 * 身份证验证
	 *规则：15位数字或17位数字+X或18位数字
	 * @param	string	$idcardnum  身份证号码
	 * @return	1正确 0错误
	*/
	public static function CheckIdCard($idcardnum){

		$cnumre = preg_match("/^(?:\d{15}|\d{17}X|\d{18})$/", $idcardnum)?1:0;

		return $cnumre;
	}

	/**
	 * 根据身份证返回年龄
	 *规则：根据身份证返回年龄
	 * @param	string	$idcardnum  身份证号码
	 * @return	int
	*/
	public static function CheckAge($sfz){
		$sfzLen = strlen($sfz);
		$today = strtotime('today');
		if($sfzLen == 15){
			$cyear = '19'.substr($sfz,6,2);
			$birthday = strtotime('19'.substr($sfz,6,6));
		}else{
			$cyear = substr($sfz,6,4);
			$birthday = strtotime(substr($sfz,6,8));
		}
		$nyear = date('Y');
		if($nyear-$cyear == 18){
			$birthday += (86400*4);
		}

		$age = floor(($today-$birthday)/86400/365);
		return $age;
	}

    /**
	 * 银行卡号验证
	 *规则：16-19位数字
	 * @param	string	$idcardnum  银行卡号
	 * @return	1正确 0错误
	*/
	public static function CheckBankCard($idcardnum){

		$banRe = preg_match("/^[0-9]{16,19}$/", $idcardnum)?1:0;

		return $banRe;
	}





    /**发送短信验证码
	 * @param	string	$phone  电话号码
	 * @param	int	    $isreg  1注册 0非注册
	 * @return	0发送失败 1发送成功 2手机号为空 3非手机号码 
	*/
	public static function PostPhoneVer($phone,$isreg){
		
		if(strlen($phone)==0){
			return 2;
		}
		
		$phonere = self::CheckPhone($phone);
		if($phonere == 0){
			return 3;
		}

		$apimessre = API::POST(USER_SENDCODE,array('moblie'=>$phone,'type'=>$isreg));
		if($apimessre['status']<1){
			MLog::error_log($apimessre,'API-'.USER_SENDCODE.'-出现异常');
			return 0;
		}elseif($apimessre['status']==1&&$apimessre['code']==100){
			MLog::info_log($apimessre,'发送短信成功');
			return 1;
		}else{
			MLog::warning_log($apimessre,'发送短信失败');
			return 0;
		}
	}
	//生成18为编号函数
    public static function  createNumber($type = 0){
        $type = intval($type);
        if ($type<10) $type = '0' . $type;
        if ($type>99) $type ='99';
        $random = self::random(4);
        $number = substr(date('YmdHis'),2) . $type . $random;
        return $number;
    }

    public static function page($url,$total,$pageid=1,$psize=25,$half=3,$start=0,$end=0){  
	//分页函数,$total=总共的条数;$pageid=当前显示的页号;$psize=每页显示的条数;$half=$pageid前后显示的条数  
	    $totalpage=ceil($total/$psize);//总共的页数  
	    $i=0;  
	    $arr=array();  
	    $rand=rand(10,30);  
	    if($totalpage<2){//小于2页，不显示分页  
	        return;  
	    }  
	    if($pageid>1){//最前面几个  
	    	$arr[$i]["action"]="not";
	        $arr[$i]["msg"]="上一页";  
	        $arr[$i]["url"]=$url."?pageid=".($pageid-1)."&start=".$start."&end=".$end;  
	        $i++; 
	        $arr[$i]["action"]="not"; 
	        $arr[$i]["msg"]=1;  
	        $arr[$i]["url"]=$url."?pageid=1&start=".$start."&end=".$end;  
	        if($pageid-$half>2){//是否显示  ...  
	            $i++;  
	            $arr[$i]["msg"]="...";  
	        }  
	        $i++;/**/  
	    }  
	    for($j=0;$j<$half;$j++,$i++){//$pageid前面的[最多$half个]  
	        if($pageid-$half+$j<2){//  
	            $i--;//使$i保持不变  
	            continue;  
	        }/**/  
	        $arr[$i]["action"]="not";
	        $arr[$i]["msg"]=$pageid-$half+$j;  
	        $arr[$i]["url"]=$url."?pageid=".($pageid-$half+$j)."&start=".$start."&end=".$end;  
	    }  
	      
	    {//中间项  
	    	$arr[$i]["action"]="on";//选中项
	        $arr[$i]["msg"]= $pageid;  
	        $arr[$i]["url"]=$url."?pageid=".($pageid)."&start=".$start."&end=".$end;  
	        $i++;  
	    }  
	    for($j=0;$j<$half;$j++,$i++){//$pageid后面的[最多($half-1)个]  
	        if($pageid+$j+1>$totalpage){//  
	            $i++;  
	            break;  
	        }  
	        $arr[$i]["action"]="not";
	        $arr[$i]["msg"]=$pageid+$j+1;  
	        $arr[$i]["url"]=$url."?pageid=".($pageid+$j+1)."&start=".$start."&end=".$end;  
	    }  
	    if($pageid+$half+1<$totalpage){//最后一页，有省略号  
	        $arr[$i]["msg"]="...";  
	        $i++;  
	        $arr[$i]["action"]="not";
	        $arr[$i]["msg"]=$totalpage;  
	        $arr[$i]["url"]=$url."?pageid=".($totalpage)."&start=".$start."&end=".$end;  
	        $i++;  
	    }  
	    if($pageid+$half+1==$totalpage){//最后一页,无省略号
	    	$arr[$i]["action"]="not";  
	        $arr[$i]["msg"]=$totalpage;  
	        $arr[$i]["url"]=$url."?pageid=".($totalpage)."&start=".$start."&end=".$end;  
	        $i++;  
	    }  
	    if($pageid!=$totalpage){//是否显示下一页 
	    	$arr[$i]["action"]="not"; 
	        $arr[$i]["msg"]="下一页";  
	        $arr[$i]["url"]=$url."?pageid=".($pageid+1)."&start=".$start."&end=".$end;     
	    }  
	    $msg = "<ul>";
	    //$msg="<div><span>共".$total."条</span> ";  
	    foreach($arr as $value ){//转为html  
	        if(strcmp("...",$value["msg"])==0){  
	            $msg.="<li class='login_r6_page_li1'>".$value['msg']."</li>";            
	        }else{  
	            if($value["action"]=="on"){//选中状态
	            	$msg.="<li class='login_r6_page_li1'><a href='".$value["url"]."' class='on'>".$value["msg"]."</a></li>";   
	            }else{
	            	$msg.="<li class='login_r6_page_li1'><a href='".$value["url"]."'>".$value["msg"]."</a></li>";   
	            }
	        }  
	    }  

	    $msg.="<li class='login_r6_page_li2'>";
        $msg.="<form action='".$url."' method='get'>";
        $msg.="<span>到第</span>";
        $msg.="<input type='hidden' name='start' value='".$start."'>";
        $msg.="<input type='hidden' name='end' value='".$end."'>";
        $msg.="<input name='pageid' class='c_input2' type='text' />";
        $msg.="<span>页</span>";
        $msg.="</li>";
        $msg.="<li class='login_r6_page_li3'>";
        $msg.="<input  class='c_login_btn12' value='确定' type='submit' />";
        $msg.="</form>";
        $msg.="</li>";



	    return $msg."</ul>";  
	}  



	/*<ul>
	  <li class="login_r6_page_li1"><a href="#">上一页</a></li>
	  <li class="login_r6_page_li1"><a href="#">1</a></li>
	  <li class="login_r6_page_li1"><a href="#" class="on">2</a></li>
	  <li class="login_r6_page_li1"><a href="#">3</a></li>
	  <li class="login_r6_page_li1">···</li>
	  <li class="login_r6_page_li1"><a href="#">239</a></li>
	  <li class="login_r6_page_li1"><a href="#">下一页</a></li>
	  <li class="login_r6_page_li2">到第<input name="c_input2" class="c_input2" type="text" />页</li>
	  <li class="login_r6_page_li3"><input name="c_login_btn12" class="c_login_btn12" value="确定" type="button" /></li>
	</ul>*/ 

	/**
     * @brief    提示信息
     * @author   QingYu.Sun
     * @param    $obj
     * @param    $message
     * @param    $code
     * @return   
     **/
    public static function showMsg($obj,$message='',$code=''){
        $obj->layout = '';
        $obj->render('/site/notice',array('msg'=>$message,'code'=>$code));
        exit;  
    }


}//end of class
?>
