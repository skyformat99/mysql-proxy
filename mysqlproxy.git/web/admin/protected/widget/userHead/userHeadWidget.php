<?php    
class userHeadWidget extends CWidget{  
    public function init()  
    {  
        //当视图中执行$this->beginWidget()时候会执行这个方法  
        //可以在这里进行查询数据操作  
    }  

    public function run()  
    {  
        //当视图中执行$this->endWidget()的时候会执行这个方法  
        $userId = $this->getController()->userId;
        $uInfo = $this->getController()->userInfo;
        $nowCon = $this->getController()->nowCon;
        //未登录、返回值为空、或者API异常
        if(empty($userId) || empty($uInfo['data']) || isset($uInfo['data']['json_last_error'])){
            $nick = '';
        }else{
            $nick = $uInfo['data']['nick'];
        }
        //路径（判断左侧选中状态）
        $act = explode('/', str_replace('.html','',Yii::app()->request->getUrl())); 
        
        if(!isset($act[2])&&empty($act[1])){//首页
            $act[2]='';
        }elseif(!isset($act[2])&&!empty($act[1])){
            $act[2]=$act[1];
        }
        $act = explode('?',$act[2]);
        $this->render('head', array(  
            'nick'=>$nick,
            'nowAction'=>$act[0],
            'nowCon'   =>$nowCon  
        ));  
    }  
}  