<div class="header2_bg <?php echo ($nowAction==''||$nowAction=='security'||$nowAction=='new'||$nowCon=='login'||strtolower($nowCon)=='repassword'||$nowCon=='incode')?'header2_bgnone':'';?>">
     <div class="header2_1bg">
        <?php if(empty($nick)){?>
            <div class="header2_1">
           <div class="focusUs fl">
             <span>关注我们</span>
           </div>
           <div class="focusUsWay fl">
             <span class="wb"><a href="http://weibo.com/u/5589417181" target="_blank"><img src="<?php echo STATIC_URL;?>/images/icon-wbhover.png" border="0" ></a></span>
             <span class="wx"><img src="<?php echo STATIC_URL;?>/images/icon-wxhover.png" border="0" ></span>
          </div>
            	<a href="/register/index.html" class="hong">免费注册</a><span class="line">|</span><a href="/login/index.html" class="hui">立即登录</a><span class="tel">服务热线<b class="colorhong">400-154-3336</b></span></div>
        <?php }else{?>
            <div class="header2_1">
            	<div class="focusUs fl">
             <span>关注我们</span>
           </div>
           <div class="focusUsWay fl">
             <span class="wb"><a href="http://weibo.com/u/5589417181" target="_blank"><img src="<?php echo STATIC_URL;?>/images/icon-wbhover.png" border="0"></a></span>
             <span class="wx"><img src="<?php echo STATIC_URL;?>/images/icon-wxhover.png" border="0" ></span>
          </div>
            	<span class="hui1">您好！</span><a href="/ApplyDish/index" class="hong"><?php echo $nick;?> </a><a href="/login/loginOut" class="hui1">【安全退出】</a><span class="tel">服务热线<b>400 154 3336</b></span></div>
        <?php }?>
     </div>

     <div class="header2">
         <div class="head2a">
             <div class="head2a_1 mz_fl"><a href='/'><img src="<?php echo STATIC_URL;?>/images/mz_logo.png" /></a></div>
             <div class="head2a_2 mz_fl"><img src="<?php echo STATIC_URL;?>/images/mz_login_line1.jpg" width="1" height="18" /></div>
             <div class="head2a_3 mz_fl"><img src="<?php echo STATIC_URL;?>/images/mz_login_text1.jpg"/></div>
         </div>

         <?php
            $noneArr = array('register','binfund','suff');
            if(!in_array(strtolower($nowCon),$noneArr)){
         ?>
         <div class="head2b" >
            <ul>
                <li class="head2b_nav"><a href="/" class="<?php echo $nowAction==''?'active':'';?>">首页</a></li>
                <li class="head2b_nav"><a href="/ApplyDish/index/act/manage" class="<?php echo strtolower($nowCon)=='applydish'?'active':'';?>">实盘交易</a></li>
                <li class="head2b_nav"><a href="/copy/security.html" class="<?php echo $nowAction=='security'?'active':'';?>">安全保障</a></li>
                <li class="head2b_nav"><a href="/copy/new.html" class="<?php echo $nowAction=='new'?'active':'';?>">新手指引</a></li>
                <li class="head2b_nav"><a href="/copy/help.html" class="<?php echo $nowAction=='help'?'active':'';?>">帮助中心</a></li>
            </ul>
         </div>
         <?php }?>
     </div>
</div>
