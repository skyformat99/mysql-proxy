<?php

namespace Core;

/**
 * 通用的连接池框架
 * @package Swoole\Async
 */
class Pool
{

    /**
     * 连接池的尺寸，最大连接数
     * @var int $poolSize
     */
    protected $poolSize;

    /**
     * idle connection
     * @var array $resourcePool
     */
    protected $resourcePool = array();
    public $resourceNum = 0;
    private $datasource = '';
    protected $table = null;
    protected $failureCount = 0;

    /**
     * @var \SplQueue
     */
    protected $idlePool;

    /**
     * @var \SplQueue
     */
    protected $taskQueue;
    protected $createFunction;
    protected $config;

    /**
     * @param int $poolSize
     * @param array $config
     * @throws \Exception
     */
    public function __construct($config = array(), $poolSize = 100, $table)
    {
        $this->poolSize = $poolSize;
        $this->taskQueue = new \SplQueue();
        $this->idlePool = new \SplQueue();
        $this->config = $config;
        $this->table = $table;
        $this->datasource = $config['host'] . ":" . $config['port'] . ":" . $config['database'];
    }

//    public function connect()
//    {
//        $db = new \swoole_mysql;
//        $db->on('close', function ($db) {
//            $this->remove($db);
//        });
//        return $db->connect($this->config, function ($db, $result) {
//                    if ($result)
//                    {
//                        $this->join($db);
//                    } else
//                    {
//                        $this->failure();
//                        trigger_error("connect to mysql server[{$this->config['host']}:{$this->config['port']}] failed. Error: {$db->connect_error}[{$db->connect_errno}].");
//                    }
//                });
//    }

    /**
     * 加入到连接池中
     * @param $resource
     */
    function join($resource)
    {
        //保存到空闲连接池中
        $this->resourcePool[spl_object_hash($resource)] = $resource;
        $this->release($resource);
    }

    /**
     * 失败计数
     */
    function failure()
    {
        $this->resourceNum--;
        $this->table->decr(MYSQL_CONN_KEY,$this->datasource);
        $this->failureCount++;
    }

    /**
     * @param $callback
     */
    function create($callback)
    {
        $this->createFunction = $callback;
    }

    /**
     * 修改连接池尺寸
     * @param $newSize
     */
    function setPoolSize($newSize)
    {
        $this->poolSize = $newSize;
    }

    /**
     * 移除资源
     * @param $resource
     * @return bool
     */
    function remove($resource)
    {
        $rid = spl_object_hash($resource);
        if (!isset($this->resourcePool[$rid]))
        {
            return false;
        }
        //从resourcePool中删除
        unset($this->resourcePool[$rid]);
        $this->resourceNum--;
        $this->table->decr(MYSQL_CONN_KEY,$this->datasource);
        return true;
    }

    /**
     * 请求资源
     * @param callable $callback
     * @param fd  哪个客户端链接请求mysql链接
     * @return bool
     */
    public function request(callable $callback, $fd) {
        //入队列
        $this->taskQueue->enqueue($callback);
        //有可用资源
        if (count($this->idlePool) > 0) {
            $this->doTask();
        }
        //没有可用的资源, 创建新的连接
        elseif (count($this->resourcePool) < $this->poolSize and $this->resourceNum < $this->poolSize) {
            call_user_func($this->createFunction,array($fd));
            $this->connect($fd);
            $this->resourceNum++;
            $this->table->incr(MYSQL_CONN_KEY, $this->datasource);
        }
    }

    /**
     * 释放资源
     * @param $resource
     */
    public function release($resource)
    {
        $this->idlePool->enqueue($resource);
        //给排队的请求做任务
        if (count($this->taskQueue) > 0)
        {
            $this->doTask();
        }
    }

    protected function doTask()
    {
        $resource = null;
        //从空闲队列中取出可用的资源
        while (count($this->idlePool) > 0)
        {
            $_resource = $this->idlePool->dequeue();
            $rid = spl_object_hash($_resource);
            //资源已经不可用了，连接已关闭
            if (!isset($this->resourcePool[$rid]))
            {
                continue;
            } else
            {
                //找到可用连接
                $resource = $_resource;
                break;
            }
        }
        //没有可用连接，继续等待
        if (!$resource)
        {
            if (count($this->resourcePool) == 0)
            {
                call_user_func($this->createFunction);
                $this->resourceNum++;
                $this->table->incr(MYSQL_CONN_KEY,$this->datasource);
            }
            return;
        }
//        $callback = $this->taskQueue->dequeue();
//        call_user_func($callback, $resource);
    }

    /**
     * @return array
     */
    function getConfig()
    {
        return $this->config;
    }

}
