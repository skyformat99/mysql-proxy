<?php

ini_set("display_errors", "On");
error_reporting(E_ALL);
restore_exception_handler();
restore_error_handler();
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class AjaxaccountController extends Controller
{

    public $userBiz = null;

    public function __construct()
    {
        $this->userBiz = new GayUser();
    }

    //*****************************头像上传***********************
    public function actionUploadImage()
    {
        try
        {
            $userId = $this->getUid();
            $imageName = md5(base64_encode($userId) . time()) . ".png";
            $path = dirname(__FILE__) . "/../../upload/$imageName";
            $min_name = UploadImage::Upload($path, 100, 100);
            $this->userBiz->upUserImageInfo($userId, $min_name);
//            exit(Tool::jsonRetArray(1, 100, "上传成功", ""));
            $this->redirect('/user/info.html');
        } catch (Exception $message)
        {
            Logger::instance()->log($message->getMessage());
            exit(Tool::jsonRetArray(0, 7, "上传失败", ""));
        }
    }

    //*****************************注册***********************
    public function actionRegister()
    {
        $user_mobile = (int) YII::app()->request->getParam("user_mobile");
        $code = (int) YII::app()->request->getParam("verify");
        $pwd = YII::app()->request->getParam("pwd"); //todo 密码强度检测
        $ret = $this->userBiz->register($user_mobile, $pwd, $code, 0);
        if ($ret === true)
        {
            $user_id = Yii::app()->db->getLastInsertID();

            //设置cookie
            $cookie = new CHttpCookie('userId', DyCrypt::encodeStr($user_id));
            $cookie->domain = MZ_BASE_DOMAIN;
            //$cookie->expire = empty($rememberme) ? -1 : time() + IDTIME;  //有限期
            $cookie->expire = time() + IDTIME;  //有限期
            $cookie->httpOnly = true;
            Yii::app()->request->cookies['userId'] = $cookie;

            $cookie2 = new CHttpCookie('user_mobile', $user_mobile);
            $cookie2->domain = MZ_BASE_DOMAIN;
            $cookie->expire = time() + IDTIME;  //有限期
            
            exit(Tool::jsonRetArray(1, 100, "注册成功", ""));
        } else
        {
            exit(Tool::jsonRetArray(0, $ret['code'], $ret['msg'], ""));
        }
    }

    public function actionGetCodeForRegister()
    {


        $user_mobile = (int) YII::app()->request->getParam("user_mobile");
        if (empty($user_mobile))
        {
            exit(Tool::jsonRetArray(0, 9, "手机号为空", ""));
        }
        $sql = "select 1 from user where `user_mobile`='$user_mobile'";
        $ret = Yii::app()->db->createCommand($sql)->queryScalar();
        if ($ret)
        {
            exit(Tool::jsonRetArray(0, 9, "手机号已被注册", ""));
        }
        $sms = new SmsServicePlus();
        $data = $sms->register($user_mobile);
        if ($data)
        {
            exit(Tool::jsonRetArray(1, 100, "发送成功", $data));
        } else
        {
            exit(Tool::jsonRetArray(0, 7, "发送验证码失败", $data));
        }
    }

    //**************************登录接口*********************************
    public function actionLogin()
    {
        try
        {
            $user_mobile = YII::app()->request->getParam("user_mobile");
            $pwd = YII::app()->request->getParam("pwd");
            $rememberme = YII::app()->request->getParam("rememberme", 1);
//             if (!Toolplus::CheckPhone($user_mobile)) {
//                 exit(Tool::jsonRetArray(0, 9, "手机号错误", ""));
//             }
            $data = $this->userBiz->login($user_mobile, $pwd, "", 0);
            if ($data)
            {
                //设置cookie
                $cookie = new CHttpCookie('userId', DyCrypt::encodeStr($data['user_id']));
                $cookie->domain = MZ_BASE_DOMAIN;
                $cookie->expire = empty($rememberme) ? -1 : time() + IDTIME;  //有限期
                $cookie->httpOnly = true;
                Yii::app()->request->cookies['userId'] = $cookie;

                $cookie2 = new CHttpCookie('nickname', $data['nickname']);
                $cookie2->domain = MZ_BASE_DOMAIN;
                $cookie2->expire = empty($rememberme) ? -1 : time() + IDTIME;
                ;  //有限期
                $cookie2->httpOnly = true;
                Yii::app()->request->cookies['nickname'] = $cookie2;

                $cookie3 = new CHttpCookie('headimgurl', $data['headimgurl']);
                $cookie3->domain = MZ_BASE_DOMAIN;
                $cookie3->expire = empty($rememberme) ? -1 : time() + IDTIME;
                ;  //有限期
                $cookie3->httpOnly = true;
                Yii::app()->request->cookies['headimgurl'] = $cookie3;

                unset($data['userAuthStr']);
                unset($data['id_number']);
                exit(Tool::jsonRetArray(1, 100, "登录成功", $data));
            } else
            {
                exit(Tool::jsonRetArray(0, 7, "手机号或密码错误", ''));
            }
        } catch (Exception $message)
        {
            Logger::instance()->log($message->getMessage());
            exit(Tool::jsonRetArray(0, 10, "登录失败", ''));
        }
    }

    //退出
    public function actionLoginOut()
    {
        $cookies = Yii::app()->request->getCookies();
        if ($cookies['userId'])
        {
            setcookie("userId", time(), time() - 3600, "/", MZ_BASE_DOMAIN);
            setcookie("nickname", time(), time() - 3600, "/", MZ_BASE_DOMAIN);
            setcookie("headimgurl", time(), time() - 3600, "/", MZ_BASE_DOMAIN);
            $this->redirect("/");
        }
        exit(Tool::jsonRetArray(1, 100, "退出成功"));
    }

    public function actionGetCodeForForget()
    {


            $user_mobile = (int) YII::app()->request->getParam("user_mobile");
            if (empty($user_mobile))
            {
                exit(Tool::jsonRetArray(0, 9, "手机号为空", ""));
            }
            $sql = "select 1 from user where `user_mobile`='$user_mobile'";
            $ret = Yii::app()->db->createCommand($sql)->queryScalar();
            if (!$ret)
            {
                exit(Tool::jsonRetArray(0, 9, "账户不存在", ""));
            }
            $sms = new SmsServicePlus();
            $data = $sms->forgetPassword($user_mobile);
            if ($data)
            {
                exit(Tool::jsonRetArray(1, 100, "发送成功", $data));
            } else
            {
                exit(Tool::jsonRetArray(0, 7, "发送验证码失败", $data));
            }
    }

    //**************************找回密码*********************************
    public function actionforgetPwd()
    {
        try
        {
            $user_mobile = (int) YII::app()->request->getParam("user_mobile");
            $code = (int) YII::app()->request->getParam("code");
            $new_pwd = YII::app()->request->getParam("new_pwd");
            if (empty($user_mobile) || empty($new_pwd) || empty($code))
            {
                exit(Tool::jsonRetArray(0, 9, "缺少参数", ""));
            }

            if (!Toolplus::CheckPhone($user_mobile))
            {
                exit(Tool::jsonRetArray(0, 9, "手机号错误", ""));
            }
            $ret = $this->userBiz->forgetPwd($user_mobile, $code, $new_pwd);
            if ($ret)
            {
                exit(Tool::jsonRetArray(1, 100, "修改成功"));
            } else
            {
                exit(Tool::jsonRetArray(1, 7, "验证失败", ""));
            }
        } catch (Exception $message)
        {
            Logger::instance()->log($message->getMessage());
            exit(Tool::jsonRetArray(0, 7, "修改失败"));
        }
    }

    //**************************修改密码*********************************
    public function actionChangePwd()
    {
        try
        {
            $user_mobile = (int) YII::app()->request->getParam("user_mobile");
            $pwd = YII::app()->request->getParam("pwd");
            $new_pwd = YII::app()->request->getParam("new_pwd");
            if (empty($user_mobile) || empty($new_pwd) || empty($pwd))
            {
                exit(Tool::jsonRetArray(0, 9, "缺少参数", ""));
            }

            if (!Toolplus::CheckPhone($user_mobile))
            {
                exit(Tool::jsonRetArray(0, 9, "手机号错误", ""));
            }
            $ret = $this->userBiz->changePwd($pwd, $user_mobile, $new_pwd);
            if (!$ret)
            {
                exit(Tool::jsonRetArray(0, 10, "原密码错误", ''));
            }
            exit(Tool::jsonRetArray(1, 100, "修改成功", $ret));
        } catch (Exception $message)
        {
            Logger::instance()->log($message->getMessage());
            exit(Tool::jsonRetArray(0, 7, "修改失败"));
        }
    }

    //**************************基本信息*********************************
    public function actionBaseInfo()
    {
        try
        {
            $uid = $this->getUid();
            $ret = $this->userBiz->userInfo($uid);
            exit(Tool::jsonRetArray(1, 100, "success", $ret));
        } catch (Exception $message)
        {
            Logger::instance()->log($message->getMessage());
            exit(Tool::jsonRetArray(0, 7, "failed"));
        }
    }

    //**************************修改基本信息*********************************
    public function actionChangeBaseInfo()
    {
        try
        {
            $uid = $this->getUid();
            $nickname = YII::app()->request->getParam("nickname");
            $person_sign = YII::app()->request->getParam("person_sign");
            $sex = YII::app()->request->getParam("sex");
            if ($this->userBiz->changeNick($uid, $nickname) === false && $this->userBiz->checkNickIsSelf($uid, $nickname))
            {
                exit(Tool::jsonRetArray(0, 7, "昵称重复"));
            };
            $this->userBiz->changeSign($uid, $person_sign);
            $this->userBiz->changeSex($uid, $sex);
            //更改cookie  nickname
            $cookie2 = new CHttpCookie('nickname', $nickname);
            $cookie2->domain = MZ_BASE_DOMAIN;
            $cookie2->expire = time() + IDTIME;
            ;  //有限期
            $cookie2->httpOnly = true;
            Yii::app()->request->cookies['nickname'] = $cookie2;
            exit(Tool::jsonRetArray(1, 100, "success"));
        } catch (Exception $message)
        {
            Logger::instance()->log($message->getMessage());
            exit(Tool::jsonRetArray(0, 7, "failed"));
        }
    }

    /*
     * 获取图片验证码
     */

    public function actionGetVerifyImage()
    {
        session_start();
        $_vc = new ValidateCode();  //实例化一个对象
        $_vc->doimg();
        $_SESSION['authnum_session'] = $_vc->getCode(); //验证码保存到SESSION中
    }

}
