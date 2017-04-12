            
            <!--个人信息开始-->
            <?php 
                $cookies = Yii::app()->request->getCookies();
                if(isset($cookies['userId']) && $cookies['userId']->value && Yii::app()->controller->id == 'home' && $this->action->id == 'index') {

                    $selfUid = $this->getUid(false);
                    $gayUserModel = new GayUser();
                    $selfData = $gayUserModel->userInfo($selfUid);

                    //关注数
                    $followCnt = Yii::app()->redis->getClient(false, true)->get(RedisService::$keyFollowCnt . $selfUid);
                    $followCnt = empty($followCnt) ? 0 : $followCnt;
                    //粉丝数
                    $fansCnt = Yii::app()->redis->getClient(false, true)->get(RedisService::$keyFansCnt . $selfUid);
                    $fansCnt = empty($fansCnt) ? 0 : $fansCnt;
            ?>
            <div class="personadata">
                <div class="row personadataup margin0">
                    <!--原帖内容开始-->
                    <div class="col-xs-2 padding0">
                        <a class="show fr" href="<?php echo $this->createUrl("user/feedlist")?>">
                            <img class="headportraint" src="<?php echo $selfData['headimgurl']?>" alt=""/>
                        </a>
                    </div>
                    <!--原帖内容结束-->
                    <!--原帖内容右侧-->
                    <div class="col-xs-10">
                        <div class="name">
                            <a href="<?php echo $this->createUrl("user/feedlist")?>" class="show"><?php echo $selfData['nickname']?></a>
                        </div>
                        <div class="articleword">
                            <?php echo $selfData['person_sign']?>
                        </div>
                    </div>
                    <!--原帖内容右侧结束-->
                </div>
                <div class="row personadatadown margin0">
                    <div class="col-xs-6">
                        <div class="personadatadwcnt borderrtde">
                            关注: <?php echo $followCnt;?>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="personadatadwcnt">
                            粉丝: <?php echo $fansCnt;?>
                        </div>
                    </div>
                </div>
            </div>
            <?php }?>
            <!--个人信息结束-->

            <!--基金达人列表页开始-->
            <div class="publicelistfund">
                <div class="publicetitle2 borderbm2">
                    基金达人
                </div>
                <!--基金达人列表页-->
                <ul class="fanslist fanslist2 ">
                <?php
                    foreach($fundMaster as $master)
                    {
                ?>
                    <li class="row margin0 J_attentionP">
                        <div class="col-xs-10 paddinglf0 fanslistleft">
                            <div class="row">
                                <div class="col-xs-2 padding0">
                                    <a class="show fr" href="<?php echo $this->createUrl("user/index",array("userId"=>$master['user_id']))?>">
                                        <img class="headportraint" src="<?php echo $master['headimgurl'];?>" alt="">
                                    </a>
                                </div>
                                <div class="col-xs-10">
                                    <a href="<?php echo $this->createUrl("user/index",array("userId"=>$master['user_id']))?>" class="name show">
                                        <?php echo $master['nickname'];?>
                                    </a>

                                    <div class="dynamic">
                                        <span class="first">动态:<?php echo $master['publish_cnt'];?></span><span>粉丝:<?php echo $master['fans_cnt'];?></span>
                                    </div>
                                    <div class="fanscontent">
                                        <?php echo $master['person_sign'];?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-2 paddinglf0 paddingrt0">
                                <?php if($master['is_follow'] == 0) {?>
                                    <div class="attention">
                                        <div class="attentionbtn J_attentionhx blue" data-atten="0" data-userid="<?php echo $master['user_id'] ;?>">
                                            <span class="common-plus"></span>关注
                                        </div>
                                    </div>
                                <?php }else if($master['is_follow'] == 1){?>
                                    <div class="attention">
                                        <div class="attentionbtn J_attentionhx " data-atten="1" data-userid="<?php echo $master['user_id'] ;?>">
                                            <span class='common-check'></span><span>关注</span>
                                        </div>
                                    </div>
                                <?php }else if($master['is_follow'] == 2){?>
                                    <div class="attention">
                                        <div class="attentionbtn J_attentionhx" data-atten="2" data-userid="<?php echo $master['user_id'] ;?>">
                                            已关注
                                        </div>
                                    </div>
                                 <?php }else if($master['is_follow'] == 3){?>
                                    <div class="attention">
                                        <div class="attentionbtn J_attentionhx" data-atten="3" data-userid="<?php echo $master['user_id'] ;?>">
                                            互相关注
                                        </div>
                                    </div>
                                <?php }?>
                        </div>

                    </li>
                <?php } ?>
                </ul>
            </div>
            <!--基金达人列表页结束-->
            <!-- 推荐基金列表开始-->
            <div class="publicelistfund">
                <div class="publicetitle2 borderbm2 ">
                    推荐基金
                </div>
                <!--推荐基金副标题开始-->
                <div class="subpublicetitle">
                    <span class="fl">基金名称</span>
                    <span class="fr">近3月涨幅</span>
                </div>
                <!--推荐基金副标题开始-->
                <!--推荐基金列表内容开始-->
                <ul class="popularfund">
                <?php
                $classCommone = array("1","2","3","4","5");
                $i=0;
                    foreach($hotFundProg['fundList'] as $hotfundarr)
                    {
                ?>
                    <li class="row margin0">
                        <div class="col-xs-1"><span class="ranknumber"><?php echo $classCommone[$i]; ?></span></div>
                        <div class="col-xs-8"><a href="<?php echo $this->createUrl('fundInfo/fundInfoPc',array('fCode'=>$hotfundarr['code']))?>"><?php echo $hotfundarr['fund_name'];?></a>
                        </div>
                        <div class="col-xs-3 text-right red"><?php echo sprintf("%.2f",$hotfundarr['month_rise'])>0 ? "+".sprintf("%.2f",$hotfundarr['month_rise']) : sprintf("%.2f",$hotfundarr['month_rise'])?>%</div>
                    </li>
                <?php $i++; } ?>
                </ul>
                <!--推荐基金列表结束-->
            </div>