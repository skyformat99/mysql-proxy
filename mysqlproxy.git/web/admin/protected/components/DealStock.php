<?php
/**
 * 股票函数库
 * @author    machao <jiaweifeng@taodangpu.com>
 * @version 1.0
 * @copyright mazhan
 * @link http://www.mazhan.com
 * @date 2014-10-15
 **/
class DealStock {
	static function getfestival(){
		 $days=array(20150101,
					20150102,
					20150218,
					20150219,
					20150220,
					20150221,
					20150222,
					20150223,
					20150224,
					20150406,
					20150501,
					20150622,
					20151001,
					20151002,
					20151005,
					20151006,
					20151007
    	);
   	  return $days;
	}
	/**
	 * 判断当天是否股票交易日
	 * @return 0代表正常交易日 1代表非交易日 2代表非交易时段
	 */
	static function dish_isDealTime(){
		$days=self::getfestival();
		$day = date('w');
		$todate=date('Ymd',strtotime(SYSTIME));
		if(in_array($todate,$days)){
			return 1;
		}
		 if (0==$day || 6==$day){
		 	return 1;
		 }
		$date=date('Y-m-d',strtotime(SYSTIME));
		$m_sdate=strtotime($date." 00:00:00");
		$m_edate=strtotime($date." 14:59:59");
		if((time()>=$m_sdate && time()<=$m_edate)){
			$return=0;
		}else{
			$return=1;
		}
		 return $return;
	}
	/**
	 * 判断当天是否股票交易日
	 * @return 0代表正常交易日 1代表非交易日 2代表非交易时段
	 */
	static function stock_isDealTime(){
		$days=self::getfestival();
		$todate=date('Ymd',strtotime(SYSTIME));
		if(in_array($todate,$days)){
			return 1;
		}
		$day = date('w');
		 if (0==$day || 6==$day){
		 	return 1;
		 }
		$date=date('Y-m-d',strtotime(SYSTIME));
		$m_sdate=strtotime($date." 09:30:00");
		$m_edate=strtotime($date." 11:30:00");
		$a_sdate=strtotime($date." 13:00:00");
		$a_edate=strtotime($date." 15:00:00");
		if((time()>=$m_sdate && time()<=$m_edate)||time()>=$a_sdate && time()<=$a_edate){
			$return=0;
		}else{
			$return=1;
		}
		 return $return;
	}
	/**
	 * 判断递延日
	 * @return 0代表正常交易日 1代表非交易日 2代表非交易时段
	 */
	static function stock_isDyTime(){
		$days=self::getfestival();
		$todate=date('Ymd',strtotime(SYSTIME));
		if(in_array($todate,$days)){
			return 1;
		}
		$day = date('w');
		 if (0==$day || 6==$day){
		 	return 1;
		 }
		$date=date('Y-m-d',strtotime(SYSTIME));
		$m_sdate=strtotime($date." 00:00:00");
		$m_edate=strtotime($date." 14:54:59");
		if((time()>=$m_sdate && time()<=$m_edate)){
			$return=0;
		}else{
			$return=1;
		}
		 return $return;
	}
	/**
	 * 判断当天股票最大可卖出时间段
	 * @return 0代表正常交易日 1代表非交易日
	 */
	static function stock_saleMax(){
		$days=self::getfestival();
		$todate=date('Ymd',strtotime(SYSTIME));
		if(in_array($todate,$days)){
			return 1;
		}
		$date=date('Y-m-d',strtotime(SYSTIME));
		$a_edate=strtotime($date." 14:55:00");
		if(time()<=$a_edate){
			$return=0;
		}else{
			$return=1;
		}
		return $return;
	}
	/**
	 * 判断自选股刷新日期
	 * @return 0代表正常交易日 1代表非交易日 2代表非交易时段
	 */
	static function stock_isOptTime(){
		$days=self::getfestival();
		$todate=date('Ymd',strtotime(SYSTIME));
		if(in_array($todate,$days)){
			return 1;
		}
		$day = date('w');
		 if (0==$day || 6==$day){
		 	return 1;
		 }
		$date=date('Y-m-d',strtotime(SYSTIME));
		$m_sdate=strtotime($date." 09:15:00");
		$m_edate=strtotime($date." 11:30:00");
		$a_sdate=strtotime($date." 13:00:00");
		$a_edate=strtotime($date." 15:00:00");
		if((time()>=$m_sdate && time()<=$m_edate)||time()>=$a_sdate && time()<=$a_edate){
			$return=0;
		}else{
			$return=1;
		}
		 return $return;
	}
}
