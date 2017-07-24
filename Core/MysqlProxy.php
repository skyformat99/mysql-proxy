<?php

namespace Core;

//require '/data/www/public/sdk/autoload.php';

class MysqlProxy {

    /**
     * @var \swoole_server
     */
    private $serv = null;

    /*
     * 上报sql数据到集中redis里面
     */
    private $redis = null;
    private $redisHost = REDIS_HOST;
    private $redisPort = REDIS_PORT;
    /*
     * PROXY的ip 用于proxy集群上报加到key里面
     */
    private $localip = null;

    /**
     * @var \swoole_table task 和worker之间共享数据用
     */
    private $table = null;

    /*
     * 刚连上
     */

    const CONNECT_START = 0;
    /*
     * 发送了auth挑战数据
     */
    const CONNECT_SEND_AUTH = 1;
    /*
     * 发送ok后握手成功
     */
    const CONNECT_SEND_ESTA = 2;
    const COM_QUERY = 3;
    const COM_INIT_DB = 2;
    const COM_QUIT = 1;
    const COM_PREPARE = 22;

    private $targetConfig = array();

    /**
     * @var \MysqlProtocol
     */
    private $protocol;

    /**
     * @var MySQL[]
     */
    private $pool;

    /**
     * @var array
     */
    private $clients = [];

    private function createTable() {
        $this->table = new \swoole_table(1024);
        $arr = [];
        foreach ($this->targetConfig as $dbname => $config) {
            $conf = $config['master'];
            $dataSource = $conf['host'] . ":" . $conf['port'] . ":" . $dbname;
            $this->table->column($dataSource, \swoole_table::TYPE_INT, 4);
            $arr[$dataSource] = 0;
            if (isset($config['slave'])) {
                foreach ($config['slave'] as $sconfig) {
                    $dataSource = $sconfig['host'] . ":" . $sconfig['port'] . ":" . $dbname;
                    $this->table->column($dataSource, \swoole_table::TYPE_INT, 4);
                    $arr[$dataSource] = 0;
                }
            }
        }
        $this->table->column("client_count", \swoole_table::TYPE_INT, 4);
        $this->table->create();
        // $this->table->set(MYSQL_CONN_KEY, $arr);
    }

    public function init() {

        $this->serv = new \swoole_server('0.0.0.0', PORT, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $this->serv->set([
            'worker_num' => WORKER_NUM,
            'task_worker_num' => TASK_WORKER_NUM,
            'dispatch_mode' => 2,
            'open_length_check' => 1,
            'open_tcp_nodelay' => true,
            'log_file' => SWOOLE_LOG,
            'daemonize' => DAEMON,
            'package_length_func' => 'mysql_proxy_get_length'
                ]
        );
        $this->getConfig();
        $this->createTable();
        $this->protocol = new \MysqlProtocol();
        $this->pool = array(); //mysql的池子
        $this->clients = array(); //连到proxy的客户端
        $this->serv->on('receive', array($this, 'OnReceive'));
        $this->serv->on('connect', array($this, 'OnConnect'));
        $this->serv->on('close', array($this, 'OnClose'));
        $this->serv->on('workerStart', array($this, 'OnWorkerStart'));
        $this->serv->on('task', array($this, 'OnTask'));
//        $this->serv->on('start', array($this, 'OnStart'));
        $this->serv->on('finish', function() {
            
        });
    }

    public function getConfig() {
//        $env = get_cfg_var('env.name') ? get_cfg_var('env.name') : 'product';
//        $jsonConfig = \CloudConfig::get("platform/proxy_shequ", "test");
//        $config = json_decode($jsonConfig, true);


        $this->targetConfig = MYSQL_CONF;
    }

    public function start() {
        $this->serv->start();
    }

    public function OnReceive(\swoole_server $serv, $fd, $from_id, $data) {
        if ($this->clients[$fd]['status'] == self::CONNECT_SEND_AUTH) {
            $dbName = $this->protocol->getDbName($data);
            if (!isset($this->targetConfig[$dbName])) {
                echo "db $dbName can not find\n";
                $binaryData = $this->protocol->packErrorData(10000, "db '$dbName' can not find in mysql proxy config");
                $this->serv->send($fd, $binaryData);
                return;
            }
            $this->protocol->sendConnectOk($serv, $fd);
            $this->clients[$fd]['status'] = self::CONNECT_SEND_ESTA;
            $this->clients[$fd]['dbName'] = $dbName;
            //   $this->clients[$fd]['clientsAuthData'] = $data;
            return;
        }
        if ($this->clients[$fd]['status'] == self::CONNECT_SEND_ESTA) {
            $ret = $this->protocol->getSql($data);
            $cmd = $ret['cmd'];
            $sql = $ret['sql'];
            if ($cmd !== self::COM_QUERY) {
                if ($cmd === self::COM_PREPARE) {
                    $binary = $this->protocol->packErrorData(ERROR_PREPARE, "proxy do not support remote prepare , (PDO example:set PDO::ATTR_EMULATE_PREPARES=true)");
                    $this->serv->send($fd, $binary);
                    return;
                }
                if ($cmd === self::COM_QUIT) {//直接关闭和client链接
                    $serv->close($fd, true);
                    return;
                }
                $binary = $this->protocol->packOkData(0, 0);
                $this->serv->send($fd, $binary);
                return;
            }
            $dbName = $this->clients[$fd]['dbName'];
            $pre = substr($sql, 0, 5);
            if (stristr($pre, "select") || stristr($pre, "show")) {
                if (isset($this->targetConfig[$dbName]['slave'])) {
                    $count = count($this->targetConfig[$dbName]['slave']);
                    $index = random_int(0, $count - 1); //随机均衡
                    $config = $this->targetConfig[$dbName]['slave'][$index];
                } else {//未配置从 直接走主
                    $config = $this->targetConfig[$dbName]['master'];
                }
            } else {
                $config = $this->targetConfig[$dbName]['master'];
            }
            $dataSource = $config['host'] . ":" . $config['port'] . ":" . $dbName;
            if (!isset($this->pool[$dataSource])) {
                $pool = new MySQL($config, $this->table, array($this, 'OnResult'));
                $this->pool[$dataSource] = $pool;
            }
            $this->clients[$fd]['start'] = microtime(true) * 1000;
            $this->clients[$fd]['sql'] = $sql;
            $this->clients[$fd]['datasource'] = $dataSource;
            $this->pool[$dataSource]->query($data, $fd);
        }
    }

    public function OnResult($binaryData, $fd) {
        if (isset($this->clients[$fd])) {//有可能已经关闭了
            $this->serv->send($fd, $binaryData);
            if (RECORD_QUERY) {
                $end = microtime(true) * 1000;
                $logData = array(
                    'start' => $this->clients[$fd]['start'],
                    'size' => strlen($binaryData),
                    'end' => $end,
                    'sql' => $this->clients[$fd]['sql'],
                    'datasource' => $this->clients[$fd]['datasource'],
                    'client_ip' => $this->clients[$fd]['client_ip'],
                );
                $this->serv->task($logData);
            }
        }
    }

    public function OnConnect(\swoole_server $serv, $fd) {
        echo "client connect $fd\n";
        $this->clients[$fd]['status'] = self::CONNECT_START;
        $this->protocol->sendConnectAuth($serv, $fd);
        $this->clients[$fd]['status'] = self::CONNECT_SEND_AUTH;
        $info = $serv->getClientInfo($fd);
        if ($info) {
            $this->clients[$fd]['client_ip'] = $info['remote_ip'];
        } else {
            $this->clients[$fd]['client_ip'] = 0;
        }
        $this->table->incr(MYSQL_CONN_KEY, "client_count");
    }

    public function OnClose(\swoole_server $serv, $fd) {
        echo "client close $fd\n";
        //todo del from client
        $this->table->decr(MYSQL_CONN_KEY, "client_count");
        //remove from task queue,if possible
        if (isset($this->clients[$fd]['datasource'])) {
            $this->pool[$this->clients[$fd]['datasource']]->removeTask($fd);
        }
        unset($this->clients[$fd]);
    }

//    public function OnStart($serv)
//    {
//        
//    }

    public function OnWorkerStart(\swoole_server $serv, $worker_id) {
        if ($worker_id >= $serv->setting['worker_num']) {
            $serv->tick(3000, array($this, "OnTimer"));
//            $serv->tick(5000, array($this, "OnPing"));
            swoole_set_process_name("mysql proxy task");
            $result = swoole_get_local_ip();
            $first_ip = array_pop($result);
            $this->localip = $first_ip;
        } else {
            swoole_set_process_name("mysql proxy worker");
        }
    }

//____________________________________________________task worker__________________________________________________
    //task callback 上报连接数
    public function OnTimer($serv) {
        $count = $this->table->get(MYSQL_CONN_KEY);
        if (empty($this->redis)) {
            $client = new \redis;
            if ($client->pconnect($this->redisHost, $this->redisPort)) {
                $this->redis = $client;
            }
        }
        /*
         * count layout
         *                                                hash
         * 
         *  datasource1      datasource2    datasource3   client_count(客户端链接)
         *       ↓                           ↓                     ↓                    ↓
         *       1                           1                     0                   10
         * 
         */
        $ser = \swoole_serialize::pack($count);
        $this->redis->hSet(MYSQL_CONN_REDIS_KEY, $this->localip, $ser);
        $this->redis->expire(MYSQL_CONN_REDIS_KEY, 60);
    }

    public function OnTask($serv, $task_id, $from_id, $data) {
        if (empty($this->redis)) {
            $client = new \redis;
            if ($client->pconnect($this->redisHost, $this->redisPort)) {
                $this->redis = $client;
            } else {
                return;
            }
        }
        $date = date("Y-m-d");
        $expireFlag = false;
        if (!$this->redis->exists(REDIS_SLOW . $date)) {
            $expireFlag = true;
        }
        $ser = \swoole_serialize::pack($data);
        $this->redis->zadd(REDIS_BIG . $date, $data['size'], $ser);
        $time = $data['end'] - $data['start'];
        $this->redis->zadd(REDIS_SLOW . $date, $time, $ser);
        //$this->redis->lPush('sqllist' . $date, $ser);

        if ($expireFlag) {
            $this->redis->expireAt(REDIS_BIG . $date, strtotime(date("Y-m-d 23:59:59"))); //凌晨过期
            $this->redis->expireAt(REDIS_SLOW . $date, strtotime(date("Y-m-d 23:59:59"))); //凌晨过期
            // $this->redis->expireAt('sqllist' . $date, strtotime(date("Y-m-d 23:59:59")) - time()); //凌晨过期
        }
    }

}
