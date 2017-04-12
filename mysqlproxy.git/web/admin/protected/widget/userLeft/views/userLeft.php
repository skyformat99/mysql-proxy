<div class="login_left mz_fl">        
  <!--<script type="text/javascript">jQuery(".login_left").slide({effect:"left",pnLoop:false});</script>-->
  <!-- zsy修改备注 左侧的头像部分 这是没动的 -->
  <div class="login_1 clearfix">
     <div class="login_1a mz_fl"><img src="<?php echo STATIC_URL;?>/images/mz_login_img04.png" width="47" height="47" /></div>
             <?php $nickLen = (strlen($userLeftInfo['nick']) + mb_strlen($userLeftInfo['nick'],'UTF8'))/2;?>
             <?php if(empty($userLeftInfo['umAccountName'])){?>
             <div class="login_1b mz_fl"  style="display:<?php echo $nickLen<=10?'none':'block';?>">
                 <div class="login_2text"><?php echo $userLeftInfo['nick']?></div>
                 <div class="login_2con">
                       <div class="login_2a mz_fl">
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03b.png" width="22" height="20" style="display:none;"/>
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03a.png" width="22" height="20"  />
                           <div class="login_2a_tit">
                               <div class="login_2a_titbg1"></div>
                               <div class="login_2a_titbg">您尚未进行实名认证<br><a href="/binFund/index">马上认证</a></div>
                           </div>
                       </div>
                       <div class="login_2b mz_fl">
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03c.png" width="22" height="20" style="display:none;"/>
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03d.png" width="22" height="20"  />
                           <div class="login_2a_tit1 login_2a_top login_2a_top2">
                               <div class="login_2a_titbg1"></div>
                               <div class="login_2a_titbg_width">您已手机认证</div>
                           </div>
                       </div>
                 </div> 
             </div>
             
             <div class="login_1b mz_fl" style="display:<?php echo $nickLen<=10?'block':'none';?>;">
                 <div class="login_2text1"><?php echo $userLeftInfo['nick']?></div>
                 <div class="login_2con">
                       <div class="login_2a mz_fl">
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03b.png" width="22" height="20" style="display:none;"/>
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03a.png" width="22" height="20" />
                           <div class="login_2a_tit login_2a_top">
                               <div class="login_2a_titbg1"></div>
                               <div class="login_2a_titbg">您尚未进行实名认证<br><a href="/binFund/index">马上认证</a></div>
                           </div>
                       </div>
                       <div class="login_2b mz_fl">
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03c.png" width="22" height="20" style="display:none;"/>
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03d.png" width="22" height="20"  />
                           <div class="login_2a_tit1 login_2a_top login_2a_top2">
                               <div class="login_2a_titbg1"></div>
                               <div class="login_2a_titbg_width">您已手机认证</div>
                           </div>
                       </div>
                 </div>
                 
             </div>
             <?php }else{?>
             <div class="login_1b mz_fl">
                 <div class="login_2text1"><?php echo $userLeftInfo['nick']?></div>
                 <div class="login_2con">
                       <div class="login_2a mz_fl">
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03b.png" width="22" height="20" />
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03a.png" width="22" height="20" style="display:none;" />
                           <div class="login_2a_tit login_2a_top login_2a_top1">
                               <div class="login_2a_titbg1"></div>
                               <div class="login_2a_titbg_width">您已实名认证</div>
                           </div>
                       </div>
                       <div class="login_2b mz_fl">
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03c.png" width="22" height="20" style="display:none;"/>
                           <img src="<?php echo STATIC_URL;?>/images/mz_login_img03d.png" width="22" height="20"  />
                           <div class="login_2a_tit1 login_2a_top login_2a_top2">
                               <div class="login_2a_titbg1"></div>
                               <div class="login_2a_titbg_width">您已手机认证</div>
                           </div>
                       </div>
                 </div>
                 
             </div>
             <?php }?>
     
 </div>
  <!-- zsy修改备注 左侧的头像部分 这是没动的 结束 -->
  <!-- zsy修改备注 添加的 开始-->
  <div class="balance clearfix border_bm">
      <span >账户余额：<b><?php echo number_format(($HJInfo['usableMoney']+$HJInfo['freezeMoney']),2);?></b></span>
      <input type="button" class="com_button" style="cursor:pointer" value="充值" onclick="window.location.href='/ucenter/suff'"/>
  </div>
  <!-- zsy修改备注 添加的 结束-->
  <!-- zsy修改备注 资金管理等的列表  这个用的之前的布局 注释掉了两个li ul添加class border_bm li的class 更改 其他的没变  开始 -->
  <ul class="bd_ul1" id="bd_ul1">
      <li class="<?php echo ($nowAction=='account')?'bd_ul1_a2':'bd_ul1_a1'?>" onclick="javascript:document.getElementById('bd_nav_01').click();"><a href="/ucenter/account" class="<?php echo ($nowAction=='account')?'active':''?>" id="bd_nav_01">资金管理</a></li>
      <li class="<?php echo ($nowAction=='index')?'bd_ul1_b2':'bd_ul1_b1'?>" onclick="javascript:document.getElementById('bd_nav_02').click();"><a href="/ApplyDish/index" class="<?php echo ($nowAction=='index')?'active':''?>" id="bd_nav_02">自选股</a></li>
      <li class="<?php echo ($nowAction=='detail')?'bd_ul1_c2':'bd_ul1_c1'?>" onclick="javascript:document.getElementById('bd_nav_04').click();"><a href="/ucenter/detail" class="<?php echo ($nowAction=='detail')?'active':''?>" id="bd_nav_04">托管账户明细</a></li>
      <li class="<?php echo ($nowAction=='profitup')?'bd_ul1_d2':'bd_ul1_d1'?>" onclick="javascript:document.getElementById('bd_nav_05').click();"><a href="/ucenter/profitup" class="<?php echo ($nowAction=='profitup')?'active':''?>" id="bd_nav_05">收益账户明细</a></li>
      <li class="<?php echo stristr('userini|modPassword|binEmailShow|binShow|modNick|ignorePass',$nowAction)?'bd_ul1_e2':'bd_ul1_e1'?>" onclick="javascript:document.getElementById('bd_nav_06').click();"><a href="/ucenter/userini" class="<?php echo stristr('userini|modPassword|binEmailShow|binShow|modNick|ignorePass',$nowAction)?'active':''?>" id="bd_nav_06">账户设置</a></li>
  </ul>
  <!-- zsy修改备注 资金管理等的列表  这个用的之前的布局 注释掉了两个li ul添加class border_bm li的class 更改 其他的没变  结束 -->
  <!-- zsy 修改备注 添加的 左侧我要交易模块 没有我的交易的时候 直接去掉就行了-->
  <div class="exchange border_tp colore5" style="display:<?php if(!empty($userLeftInfo['umAccountName']) && $userLeftInfo['ignoreSt']==1){echo 'none';}else{echo 'block';} ?>">
      <h3>我要交易</h3>
      <!-- 三个交易步骤直接用 progress_bar 后面的 progress_bar03这个空子就行了 progress_bar01第一步 progress_bar02第二步 progress_bar03 第三步-->
      <?php if(!empty($userLeftInfo['umAccountName'])) {?>
      <div class="progress_bar progress_bar02">
      <?php }else{?>
      <div class="progress_bar progress_bar01">
      <?php }?>
          <p>
              <em></em>
          </p>
          <span class="span01">1</span>
          <span class="span02">2</span>
          <span class="span03">3</span>
      </div>
      <div class="progress_cnt paddtb10">
          <span>1、注册账户</span><i class="mark_i"></i>
      </div>
      <div class="progress_cnt paddt10">
          <p><span>2、开通第三方托管账户</span><i class="<?php if(!empty($userLeftInfo['umAccountName'])){echo 'mark_i';}else{echo 'sigh_i';} ?>"></i></p>
          <a href="/binFund/index" class="paddlf20 zsy_block coloBlue htlt15" style="display:<?php if(!empty($userLeftInfo['umAccountName'])){echo 'none';}else{echo 'block';} ?>">立即开通</a>
      </div class="progress_cnt paddtb10">
      <div class="progress_cnt paddtb10">3、开通免密支付 <a href="/ucenter/IgnorePass" class="coloBlue htlt15">立即开通</a><i class="sigh_i"></i></div>
  </div>
  <!-- zsy 修改备注添加 左侧我要交易模块结束-->
</div>