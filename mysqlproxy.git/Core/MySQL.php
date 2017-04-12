<?php

namespace Core;

class MySQL extends Pool
{

    const DEFAULT_PORT = 3306;

    private $protocal = null;

    function __construct($config, $maxConnection = 100, $table)
    {
        if (empty($config['host']))
        {
            throw new \Exception("require mysql host option.");
        }
        if (empty($config['port']))
        {
            $config['port'] = self::DEFAULT_PORT;
        }
        $this->protocal = new \MysqlProtocol();
        parent::__construct($config, $maxConnection, $table);
        $this->create(array($this, 'connect'));
    }

    protected function connect()
    {
//        $db = new \swoole_mysql;
        $db = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $db->set([
            'package_length_func' => 'mysql_proxy_get_length'
                ]
        );
        $db->on('close', function ($db) {
            $this->remove($db);
        });
        $db->on("receive", function($cli, $data = "") use ($db) {
            if ($db->status == "CONNECT")
            {
                $binary = $this->protocal->responseAuth($data, $this->config['database'], $this->config['user'], $this->config['password'],$this->config['charset']);
                if (is_array($binary))
                {//error??
                    var_dump($binary);
                    return;
                }
                $db->status = "AUTH";
                $cli->send($binary);
                return;
            } else if ($db->status == "AUTH")
            {
                $ret = $this->protocal->getConnResult($data);
                if ($ret == 1)
                {
                    $db->status = "EST";
                    $this->join($db);
                } else
                {
                    echo "链接mysql 失败 $ret\n";
                }
                return;
            } else
            {
                call_user_func($db->onResult, $data, $db->fd);
                $this->release($db);
            }
        });
        $db->on("error", function(swoole_client $cli) {
            echo "something error\n";
        });
        $db->on("connect", function($cli) {
            echo "connect to mysql\n";
        });
        $db->status = "CONNECT";
        $db->connect($this->config['host'], $this->config['port']);
//        return $db->connect($this->config['host'], $this->config['port'], function ($db, $result) {
//                    if ($result)
//                    {
//                        $db->status = "CONNECT";
//                        //          $this->join($db);
//                    } else
//                    {
//                        //       $this->failure();
//                        trigger_error("connect to mysql server[{$this->config['host']}:{$this->config['port']}] failed. Error: {$db->connect_error}[{$db->connect_errno}].");
//                    }
//                });
    }

    function query($data, callable $callabck, $fd)
    {
        $this->request(function ($db) use ($callabck, $fd, $data) {
            $db->onResult = $callabck;
            $db->fd = $fd;//当前连接服务于那个客户端fd
            return $db->send($data);
//            return $db->query($sql, function (\swoole_mysql $db, $result) use ($callabck, $fd) {
//                        call_user_func($callabck, $db, $result, $fd);
//                        $this->release($db);
//                    });
        });
    }

    function isFree()
    {
        return $this->taskQueue->count() == 0 and $this->idlePool->count() == count($this->resourcePool);
    }

    /**
     * 关闭连接池
     */
    function close()
    {
        foreach ($this->resourcePool as $conn)
        {
            /**
             * @var $conn \swoole_mysql
             */
            $conn->close();
        }
    }

    function __destruct()
    {
        $this->close();
    }

}
