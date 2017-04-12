<?php

/**
 * @file Api.php
 * @brief  api请求  
 * @author QingYu.Sun
 * @version 1.0
 * @copyright mazhan
 * @link http://www.mazhan.com
 * @date 2014-10-15
 * */
class API {

    public static $debug = false;
    public static $conn = array();
    public static $result;
    public static $version_lt_720;

    /**
     * @brief    post请求 
     * @param    $url
     * @param    $params
     * @param    $isJump 值为true时执行跳转操作
     * @return   
     * */
    public static function post($url, $params = array(), $isJump = false) {
        if (!is_array($params)) {
            return false;
        }
        $url = API_URL . '/' . ltrim($url, '/');
        return self::request($url, $params, 'post', $isJump);
    }

    /**
     * @brief    java提供服务post请求
     * @param    $url
     * @param    $params
     * @param    $isJump 值为true时执行跳转操作
     * @return
     * */
    public static function serverPost($url, $params = array(), $serverApi = SERVER_API_URL, $isJump = false) {
        if (!is_array($params)) {
            return false;
        }
        $url = $serverApi . '/' . ltrim($url, '/');
        return self::request($url, $params, 'post', $isJump);
    }

    /**
     * @brief    get请求
     * @param    $url
     * @param    $params
     * @param    $isJump 值为true时执行跳转操作
     * @return   
     * */
    public static function get($url, $params = array(), $isJump = false) {
        if (!is_array($params)) {
            return false;
        }
        $url = API_URL . '/' . ltrim($url, '/');
        return self::request($url, $params, 'get', $isJump);
    }

    /**
     * 请求API
     * 
     * @param string $api_url
     * @param string $auth_key
     * @param array  $params
     * @param string $method post | get
     * @param string $isJump 值为true时执行跳转操作
     */
    private static function request($api_url, $params = array(), $method = 'post', $isJump = false) {
        $params = self::getSignParam($params);
        $params_str = http_build_query($params);
        if ($method == 'post') {
            $request_url = $api_url;
            $post_string = $params_str;
        } else {
            $request_url = $api_url . "?{$params_str}";
        }

        //curl配制
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        }
        $response = curl_exec($ch);
        $curl_info = curl_getinfo($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        //处理直接跳转操作
        if ($isJump) {
            echo $response;
            return true;
        }
        $responseArr = json_decode($response, true);

        //返回值判断
        if ($errno || empty($response) || json_last_error() !== JSON_ERROR_NONE) {
            $result = array();
            $result['status'] = -1;
            $result['code'] = 500;
            $result['message'] = 'service error';
            $result['data']['response'] = $response;
            $result['data']['response_decode'] = json_decode($response, true);
            $result['data']['curl_error'] = $errno;
            $result['data']['curl_info'] = $curl_info;
            $result['data']['api_url'] = $api_url;
            $result['data']['request_params'] = $params;
            $result['data']['params_str'] = $params_str;
            $result['data']['json_last_error'] = json_last_error();
            MLog::error_log($result, 'API::request curl response error');

            if (API_PRINT_DEBUG && (Yii::app()->request->getParam('debug') || self::$debug)) {
                echo '<br />***********error debug info*************<br />';
                print_r($result);
                print_r($request_url);
                echo '<hr /><hr />';
            }
            return $result;
        }

        //data数据重写
        $responseArr['data'] = isset($responseArr['data']['result']) ? $responseArr['data']['result'] : $responseArr['data'];
        if (API_PRINT_DEBUG && (Yii::app()->request->getParam('debug') || self::$debug)) {
            echo '<br />***********data debug info*************<br />';
            print_r($responseArr);
            echo '<br /><hr />';
        }
        return $responseArr;
    }

    /**
     * @brief    获取签名
     * @param    $params
     * @return   
     * */
    private static function getSignParam($params) {
        $params['signType'] = 'md5';
        $params['acceptType'] = 'json';
        $params['ip'] = Fn::getIp();
        $params['timestamp'] = time() . '';

        //签名验证
        ksort($params);
        $paramsStr = http_build_query($params);
        $params['sign'] = md5($paramsStr . DyCrypt::getSignKey());
        return $params;
    }

    /**
     * 添加URL，并设置相应配置
     *
     * @param string $url
     * @param array $curl_option_array 具体配置请查看curl_setopt_array
     * @return int|false int代表成功；false代表失败（一般是配置配错了，请查看$this->errorInfo()）。
     */
    public static function addUrl($url, $params) {
        $url = API_URL . '/' . ltrim($url, '/');
        $curl_version = curl_version();
        self::$version_lt_720 = 1 == version_compare('7.20.0', $curl_version['version']);
        $ch = curl_init();
        $params = self::getSignParam($params);
        $params_str = http_build_query($params);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_str);

        //注意，也会返回null，当url参数不对的时候
        if (false === $ch || null === $ch) {
            return false;
        }
        self::$conn[] = $ch;
        return count(self::$conn) - 1;
    }

    /**
     * 当一个请求执行完毕后，调用回调函数
     *
     * @param resource $multi_handler
     * @param callable $callback
     */
    private static function doneAndCallback($multi_handler) {
        while (true) {
            $info = curl_multi_info_read($multi_handler);
            if (false === $info) {
                break;
            }
            if ($info['msg'] != CURLMSG_DONE) {
                continue;
            }
            $handle = $info['handle'];
            $key = array_search($handle, self::$conn);
            if (false === $key) {
                continue;
            }
            self::$result[$key] = curl_multi_getcontent($handle);
        };
    }

    /**
     * 执行请求，并在每个请求执行完毕后调用callback
     *
     * 回调函数的定义为：function demo($key, $curl_info, $body) {...}
     * $key,        int,    $this->addUrl返回的索引
     * $curl_info,  array,  curl_getinfo函数返回的结果，包含了请求过程中的一些细节数据
     * $body,       string, 请求返回的结果
     *
     * @param callable $callback 回调函数
     * @return bool true:成功；false:失败
     */
    public static function doAndCallback() {
        $multi_handler = curl_multi_init();
        foreach (self::$conn as $value) {
            curl_multi_add_handle($multi_handler, $value);
        }
        $still_running = true;
        if (self::$version_lt_720) {
            do {
                $re = curl_multi_exec($multi_handler, $still_running);
            } while (CURLM_CALL_MULTI_PERFORM == $re);
            while ($still_running) {
                if (-1 != curl_multi_select($multi_handler, 1)) {
                    do {
                        $re = curl_multi_exec($multi_handler, $still_running);
                    } while (CURLM_CALL_MULTI_PERFORM == $re || $still_running);
                }
            }
            self::doneAndCallback($multi_handler);
        } else {
            curl_multi_exec($multi_handler, $still_running);
            while ($still_running) {
                if (-1 != curl_multi_select($multi_handler, 1)) {
                    curl_multi_exec($multi_handler, $still_running);
                }
            }
            self::doneAndCallback($multi_handler);
        }
        foreach (self::$conn as $key => $value) {
            curl_multi_remove_handle($multi_handler, $value);
            curl_close($value);
        }
        curl_multi_close($multi_handler);
    }

    /**
     * 执行请求，并获取每个链接的内容
     *
     * 只能等待所有链接执行完毕后，才能返回。
     *
     * 返回值的结构：
     * array(
     *   $key => array('curl_info'=>array(...), 'body'=>string),
     *   $key => array('curl_info'=>array(...), 'body'=>string),
     *   $key => array('curl_info'=>array(...), 'body'=>string),
     *   ......
     * );
     * $key为$this->addUrl返回的索引
     *
     * @return array
     */
    public static function MultiGetResult() {
        self::$result = array();
        self::doAndCallback();
        self::$conn = array();
        return self::$result;
    }

}
