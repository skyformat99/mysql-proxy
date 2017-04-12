<?php
ini_set("display_errors", "On");
error_reporting(E_ALL);
restore_exception_handler();
restore_error_handler();
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController {

    public $userId = 0;
    
    public $layout='site_main';

    /**
     * getUid 
     * 
     * @param bool $type & 1 是否强制获得uid
     * $type & 2 ajax 接口请求
     * @access public
     * @return void
     */
    public function getUid() {
        $cookies = Yii::app()->request->getCookies(); //cookies
        $this->userId = isset($cookies['userId']) ? DyCrypt::decodeStr($cookies['userId']->value) : 0;
//var_dump($this->userId); die;
        if ($this->userId == 0) {
              $this->redirect("/");
        }
        return $this->userId;
    }

    protected function checkParam($param) {
        $p = array();
        foreach ($param as $key) {
            $p[$key] = Yii::app()->request->getParam($key);

            if ($p[$key] == NULL) {
                exit(Tool::jsonRetArray(0, 7, "缺少参数 {$key}", ""));
            }
        }
        return $p;
    }
}
