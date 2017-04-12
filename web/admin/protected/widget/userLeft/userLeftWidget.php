<?php    
class userLeftWidget extends CWidget{  
    public function init()  
    {  
        //当视图中执行$this->beginWidget()时候会执行这个方法  
        //可以在这里进行查询数据操作  
    }  

    public function run()  
    { 

        //当视图中执行$this->endWidget()的时候会执行这个方法  
       
        $score=0;//资料完善占比
        $scorepx=0;//资料完善长度
       
        $userId = $this->getController()->userId;
        $userInfo = $this->getController()->userInfo;
        $HJUInfo = $this->getController()->HJUserInfo;
      
        //判断是否开户
        $openUm = API::POST(ACCOUNT_OPENUM,
            array(
                'userId' =>$userId
            ));
        if($openUm['status']==1 && $openUm['code']==100){
            $umAccountName = substr_replace($openUm['data']['um_account_name'],'*******',10,13);
        }else{
            $umAccountName = null;
        }

        //判断是否开户
        if(isset($HJUInfo['data']['huijinUserId']) && !empty($HJUInfo['data']['huijinUserId'])){
            $umAccountName = substr_replace($HJUInfo['data']['huijinUserId'],'*******',10,13);
        }else{
            $umAccountName = null;
        }

        //第三方账户名
        $userInfo['data']['umAccountName']=$umAccountName;
        //资料完善度
        if(!empty($HJUInfo['data']['mobileId'])){
            $score=20;
        }
        if(!empty($HJUInfo['data']['identityCode'])){
            $score+=15;
        }
        if(!empty($userInfo['data']['nick'])){
            $score+=20;
        }
        if(!empty($umAccountName)){
            $score+=15;
        }
        if(!empty($userInfo['data']['email'])){
            $score+=10;
        }
        if(!empty($HJUInfo['data']['cardId'])){
            $score+=20;
        }
        
        $scorepx=($score/100)*99;//资料完成长度
        $userInfo['data']['score']=$score;
        $userInfo['data']['scorepx']=$scorepx;
       
        $userInfo['data']['ignoreSt']=$HJUInfo['data']['bindAgreement'];//是否免密
        //路径（判断左侧选中状态）
        $act = explode('/', Yii::app()->request->getUrl());
        $act = explode('?',$act[2]);
        $this->render('userLeft', array(  
            'userLeftInfo'      =>$userInfo['data'],
            'HJInfo'            =>$HJUInfo['data'],
            'nowAction'         =>$act[0],
            
        ));  
    }  
}  
