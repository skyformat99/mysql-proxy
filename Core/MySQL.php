<?php

namespace Core;

class MySQL {

    const DEFAULT_PORT = 3306;

    private $protocal = null;
    public $onResult = null;
    /*
     * 链接池最大连接数
     */
    private $poolSize = 0;
    /*
     * 已经建立的连接数
     */
    private $usedSize = 0;
    /*
     * 空闲链接
     */
    public $idelPool = array();
    /*
     * 排队的请求
     */
    public $taskQueue = array();
    /*
     * swoole_table用于存储连接数汇总信息
     */
    public $table = null;

    /*
     * 是否正在发送ping命令
     */
    private $pingFlag = false;
    public $datasource = null;

    const RESP_OK = 0;
    const RESP_ERROR = -1;
    const RESP_EOF = -2;

    function __construct($config, $maxConnection = 100, $table, callable $onResutl) {
        if (empty($config['host'])) {
            throw new \Exception("require mysql host option.");
        }
        if (empty($config['port'])) {
            $config['port'] = self::DEFAULT_PORT;
        }
        $this->protocal = new \MysqlProtocol();
        $this->onResult = $onResutl;
        $this->config = $config;
        $this->table = $table;
        $this->poolSize = $maxConnection;
        $this->datasource = $config['host'] . ":" . $config['port'] . ":" . $config['database'];
        $this->protocal = new \MysqlProtocol();
    }

    public function onClose($db) {
        echo "close with mysql\n";
        $this->remove($db);
        $binaryData = $this->protocal->packErrorData(ERROR_CONN, "close with mysql");
        return call_user_func($this->onResult, $binaryData, $db->clientFd);
    }

    public function onReceive($db, $data = "") {
        if ($db->status == "CONNECT") {
            $binary = $this->protocal->responseAuth($data, $this->config['database'], $this->config['user'], $this->config['password'], $this->config['charset']);
            if (is_array($binary)) {//error??
                $binaryData = $this->protocal->packErrorData(ERROR_CONN, $binary['error_msg']);
                echo "链接mysql 失败 {$binary['error_msg']}\n";
                return call_user_func($this->onResult, $binaryData, $db->clientFd);
            }
            $db->status = "AUTH";
            return $db->send($binary);
        } else if ($db->status == "AUTH") {
            $ret = $this->protocal->getConnResult($data);
            if ($ret == 1) {
                $db->status = "EST";
                echo "链接mysql 成功 $ret\n";
                return $this->join($db);
            } else {
                echo "链接mysql 失败 $ret\n";
                $binaryData = $this->protocal->packErrorData(ERROR_AUTH, "auth error when connect");
                call_user_func($this->onResult, $binaryData, $db->clientFd);
            }
        } else {
            $ret = $this->protocal->getSql($data);
            switch ($ret['cmd']) {
                case self::RESP_EOF:
                    if (( ++$db->eofCnt) == 2) {//第二次的eof才是[row] eof
                        $db->buffer .= $data;
                        call_user_func($this->onResult, $db->buffer, $db->clientFd);
                        $this->release($db);
                    } else {//pack the [Field] eof data
                        $db->buffer .= $data;
                    }
                    break;
                case self::RESP_OK:
                case self::RESP_ERROR:
                    call_user_func($this->onResult, $data, $db->clientFd);
                    $this->release($db);
                    break;

                default://result
                    $db->buffer .= $data;//pack result
                    break;
            }
        }
    }

    public function onError($db) {
        echo "something error {$db->errCode}\n";
        $binaryData = $this->protocal->packErrorData(ERROR_QUERY, "something error {$db->errCode}");
        return call_user_func($this->onResult, $binaryData, $db->clientFd);
    }

    protected function connect($fd) {
        $db = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $db->set([
            'open_length_check' => 1,
            'package_length_func' => 'mysql_proxy_get_length'
                ]
        );
        $db->on('close', array($this, 'onClose'));
        $db->on('receive', array($this, 'onReceive'));
        $db->on('error', array($this, 'onError'));
        $db->on("connect", function($cli) {
            echo "connect to mysql\n";
        });
        $db->status = "CONNECT";
        $db->clientFd = $fd; //提前设置，为了出错时候可以发送给客户端
        $db->buffer = '';
        $db->eofCnt = 0;
        $db->connect($this->config['host'], $this->config['port']);
    }

    public function query($data, $fd) {
        if (count($this->idelPool) > 0) {
            //从空闲队列中取出可用的资源
            $db = array_shift($this->idelPool);
            $db->clientFd = $fd; //当前连接服务于那个客户端fd
            $db->buffer = '';
            $db->eofCnt = 0;
            return $db->send($data); //发送数据到mysql
        } else if ($this->usedSize < $this->poolSize) {
            array_push($this->taskQueue, array('fd' => $fd, 'data' => $data));
            $this->connect($fd);
        } else {
            echo "out of pool size\n";
        }
    }

    /**
     * 加入到连接池中
     * @param $resource
     */
    private function join($db) {
        //保存到空闲连接池中
        $this->usedSize++;
        $this->table->incr(MYSQL_CONN_KEY, $this->datasource);
        array_push($this->idelPool, $db);
        $this->doTask();
    }

    protected function doTask() {
        while (count($this->taskQueue) > 0 && count($this->idelPool) > 0) {
            //从空闲队列中取出可用的资源
            $db = array_shift($this->idelPool);
            //从队列取出排队的
            $task = array_shift($this->taskQueue);
            $db->clientFd = $task['fd'];
            $db->buffer = '';
            $db->eofCnt = 0;
            $db->send($task['data']);
        }
    }

    /**
     * 释放资源
     * @param $resource
     */
    public function release($db) {
        $db->clientFd = 0;
        $db->buffer = '';
        $db->eofCnt = 0;
        array_push($this->idelPool, $db);
        $this->doTask();
    }

    /**
     * 移除资源
     * @param $resource
     * @return bool
     */
    function remove($db) {
        foreach ($this->idelPool as $k => $res) {
            if ($res === $db) {
                unset($this->idelPool[$k]);
                $this->usedSize--;
                $this->table->decr(MYSQL_CONN_KEY, $this->datasource);
                return true;
            }
        }
    }

    /**
     * 移除排队
     * @param $resource
     * @return bool
     */
    function removeTask($fd) {
        foreach ($this->taskQueue as $k => $arr) {
            if ($arr['fd'] === $fd) {
                unset($this->taskQueue[$k]);
                return true;
            }
        }
    }

    public function pingPool() {
        $this->pingFlag = true;
        foreach ($this->idelPool as $db) {
            $binaryData = $this->protocal->packPingData();
            $db->send($binaryData);
        }
    }

    function isFree() {
        return $this->taskQueue->count() == 0 and $this->idlePool->count() == count($this->resourcePool);
    }

    /**
     * 关闭连接池
     */
    function close() {
        foreach ($this->resourcePool as $conn) {
            /**
             * @var $conn \swoole_mysql
             */
            $conn->close();
        }
    }

    function __destruct() {
        $this->close();
    }

}
