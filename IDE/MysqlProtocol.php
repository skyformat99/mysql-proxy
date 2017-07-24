<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/5/3
 * Time: 09:49
 */

class MysqlProtocol
{
    /**
     * @param $data
     * @return string
     */
    public function getDbName($data)
    {

    }

    /**
     * @param mixed $data
     * @param int   $fd
     */
    public function sendConnectOk($data, $fd)
    {

    }

    /**
     * @param int       $code
     * @param string    $err_msg
     * @return string
     */
    public function packErrorData($code, $err_msg)
    {

    }

    /**
     * @param mixed     $data
     * @return array
     */
    public function getSql($data)
    {

    }

    /**
     * @param int   $effect_rows
     * @param int   $insert_id
     * @return string
     */
    public function packOkData($effect_rows, $insert_id)
    {

    }

    /**
     * @return string
     */
    public function packPingData()
    {

    }

    /**
     * @param swoole_server $serv
     * @param int           $fd
     */
    public function sendConnectAuth($serv, $fd)
    {

    }

    /**
     * @param string    $data
     * @param string    $database
     * @param string    $user
     * @param string    $password
     * @param string    $charset
     * @return string
     */
    public function responseAuth($data, $database, $user, $password, $charset)
    {

    }

    /**
     * @param string    $data
     * @return int
     */
    public function getConnResult($data)
    {

    }

    /**
     * @param $data
     * @return array
     */
    public function getResp($data)
    {

    }
}