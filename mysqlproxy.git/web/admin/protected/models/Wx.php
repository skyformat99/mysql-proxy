<?php

class Wx extends CActiveRecord
{


	public function tableName()
	{
		return '{{fund}}';
	}
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * 获取热门基金
	 * @return [type] [description]
	 */
	public function getFundRank()
	{
		$typeSql = 'SELECT f.`code`,f.`fund_name`,f.`year_rate_1`,t.`name` FROM `mz_fund` as f LEFT JOIN `mz_fund_type` as t ON f.`fund_type`=t.`id` order by f.`year_rate_1` DESC LIMIT 5';
        $fundTypeInfo = Yii::app()->db->createCommand($typeSql)->queryAll();
        return $fundTypeInfo;
	}


	public function getFundInfo($fundCode)
    {
    	$style = array(1=>"大盘股", 2=>"中盘股", 3=>"小盘股");
    	$typeSql = 'SELECT 
    						f.`code`,
    						f.`fund_name`,
    						f.`month_rise`,
    						f.`month_rate_6`,
    						f.`year_rate_1`,
    						f.`year_num`,
    						f.`ta_name`,
    						f.`year_income`,
    						f.`maxinum`,
    						f.`style`,
    						t.`name` 
    						FROM `mz_fund` as f LEFT JOIN `mz_fund_type` as t ON f.`fund_type`=t.`id` WHERE f.`code`="'.$fundCode.'" LIMIT 1';
        
        $fundTypeInfo = Yii::app()->db->createCommand($typeSql)->queryRow();
        $fundTypeInfo['style'] = $style[$fundTypeInfo['style']];
        $fundTypeInfo['type'] = FundService::getFundTagsList($fundCode);
        return $fundTypeInfo;
    }


    /**
     * 获取基金牛熊市涨跌和沪深300
     * @param  [str] $fundCode  基金编号
     */
    public function getCowBearHs($fundCode)
    {
    	$cowSql = 'SELECT DATE_FORMAT(`start_time`,"%Y%m%d") as start_time,
						   DATE_FORMAT(`end_time`,"%Y%m%d") as end_time,
						   `feature`,
						   `profit`,
						   `ranking`,
						   `count_amount` 
						   FROM `mz_fund_ranking` WHERE `fund_code`="' . $fundCode . '" order by id desc LIMIT 4';
        $fundCowInfo = Yii::app()->db->createCommand($cowSql)->queryAll();
        for ($k = 0; $k < count($fundCowInfo); $k++) {
                $fundCowInfo[$k]['profit'] = $fundCowInfo[$k]['profit'] / 100;
                $hssql = "select `Close` from kline_day where `SecurityID`='100000300' and Date in ('" . $fundCowInfo[$k]['start_time'] . "','" . $fundCowInfo[$k]['end_time'] . "') order by field(Date,'". $fundCowInfo[$k]['start_time'] . "','" . $fundCowInfo[$k]['end_time']."')";
                $hsval = Yii::app()->klinedb->createCommand($hssql)->queryAll();
                $fundCowInfo[$k]['hsval'] = ($hsval[1]['Close']-$hsval[0]['Close']) / $hsval[0]['Close'];
        }
        
        return $fundCowInfo;
    }

}