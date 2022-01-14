<?php
$version= '1.66';
$error_log= 0;//=0 or =1 for egold_error.log
if($error_log==1){
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	error_reporting(E_ALL);
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 0);
	ini_set('log_errors','on');
	ini_set('error_log', __DIR__ .'/../egold_error.log');
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
date_default_timezone_set('UTC');
$json_arr['time']= strval(time());
$date_h=(int)date("H",$json_arr['time']);
$date_m=(int)date("i",$json_arr['time']);
$date_s=(int)date("s",$json_arr['time']);
$date_synch= strtotime(date("Y-m-d H:i:00",$json_arr['time']));
function delay_now(){usleep(mt_rand(0.0001,0.01)*1000000);}
delay_now();
include __DIR__ .'/egold_settings.php';
if(isset($history_day) && (int)$history_day>7)$history_day= (int)$history_day;else $history_day=7;
$history_day_sec= $history_day*86400;
function convert_ipv6($ip){
  if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
    if(strpos($ip, '::') === FALSE){
      $parts = explode(':', $ip);
      $new_parts = array();
      $ignore = FALSE;
      $done = FALSE;
      for($i=0;$i<count($parts);$i++){
        if(intval(hexdec($parts[$i])) === 0 && $ignore == FALSE && $done == FALSE){
          $ignore = TRUE;
          $new_parts[] = '';
          if($i==0)$new_parts = '';
        } else if(intval(hexdec($parts[$i])) === 0 && $ignore == TRUE && $done == FALSE)continue;
        else if (intval(hexdec($parts[$i])) !== 0 && $ignore == TRUE){
          $done = TRUE;
          $ignore = FALSE;
          $new_parts[] = $parts[$i];
        } else $new_parts[] = $parts[$i];
      }
      $ip = implode(':', $new_parts);
    }
    if (substr($ip, -2) != ':0')$ip = preg_replace("/:0{1,3}/",":", $ip);
    if(isset($new_parts) && count($new_parts)<8 && array_pop($new_parts) == '')$ip.= ':0';
  }
  return $ip;
}
$dir_temp= __DIR__ .'/egold_temp';
if(!isset($noda_ip) || !$noda_ip){echo '{"error":"noda_ip"}';exit;}
else if(isset($argv[1]) || !isset($_SERVER['SERVER_NAME']) || ($_SERVER['SERVER_NAME']=='127.0.0.1' || $_SERVER['SERVER_NAME']=='localhost'))$host_ip=$noda_ip;
else{
	if(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']))$host_ip=$_SERVER['HTTP_CLIENT_IP'];
	else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))$host_ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	else if(isset($_SERVER['REMOTE_ADDR']))$host_ip=$_SERVER['REMOTE_ADDR'];
	if(!isset($host_ip) || !$host_ip){echo '{"error":"user_ip"}';exit;}
	if(strpos($host_ip,',') !== false){
		$host_temp= explode(',',$host_ip);
		$host_ip= $host_temp[0];
		unset($host_temp);
	}
	if(isset($host_ip) && $host_ip)$host_ip= strtolower(preg_replace("/[^0-9a-zA-Z\.]/",'.',convert_ipv6($host_ip)));
	if($host_ip!=$noda_ip && (!isset($ip_trust) || !is_array($ip_trust) || !in_array($host_ip,$ip_trust))){
		if(!isset($argv[1]) || $argv[1]!="synch"){
			if(!isset($_REQUEST['type']) || $_REQUEST['type']!="send"){
				$ddos_check_file_temp= $GLOBALS['dir_temp']."/ddos_";
				foreach(glob($ddos_check_file_temp."*") as $file){
					$ddos_check= (int)str_replace($ddos_check_file_temp, "",$file);
					$ddos_check_test= (time()-@filemtime($file)+100)/$ddos_check;
					$ddos_check++;
					$ddos_check_file= $ddos_check_file_temp.$ddos_check;
					if(@rename($file, $ddos_check_file)!== true)unset($ddos_check_file);
					if($ddos_check_test<0.001){echo '{"error":"ddos"}';exit;}
					break;
				}
				if(!isset($ddos_check_file)){
					$ddos_check_file= $ddos_check_file_temp."1";
					if(!file_exists($ddos_check_file))file_put_contents($ddos_check_file, "");
				}
			}
			$host_ip_check_file_temp= $GLOBALS['dir_temp']."/ip_".$host_ip."_";
			foreach(glob($host_ip_check_file_temp."*") as $file){
				$host_ip_check= (int)str_replace($host_ip_check_file_temp, "",$file);
				$host_ip_check_test= (time()-@filemtime($file)+10)/$host_ip_check;
				$host_ip_check++;
				$host_ip_check_file= $host_ip_check_file_temp.$host_ip_check;
				if(@rename($file, $host_ip_check_file)!== true)unset($host_ip_check_file);
				if($host_ip_check_test<0.5){echo '{"error":"timeout"}';exit;}
				break;
			}
			if(!isset($host_ip_check_file)){
				$host_ip_check_file= $host_ip_check_file_temp."1";
				if(!file_exists($host_ip_check_file))file_put_contents($host_ip_check_file, "");
			}
		}
	}
}
if((float)phpversion()<7.1){echo '{"message":"PHP version minimum 7.1, but your PHP: '.phpversion().'"}';exit;}
if(!extension_loaded('bcmath')){echo '{"message":"Require to install BCMATH"}';exit;}
if(!extension_loaded('gmp')){echo '{"message":"Require to install GMP"}';exit;}
if(!extension_loaded('curl')){echo '{"message":"Require to install CURL"}';exit;}
$dir_temp_index= $dir_temp.'/index.html';
if(!file_exists($dir_temp_index) || !(fileperms($dir_temp)>=16832)){
	if(!file_exists($dir_temp))mkdir($dir_temp, 0755);
	if(file_exists($dir_temp) && !file_exists($dir_temp_index))file_put_contents($dir_temp_index, "");
	if(!file_exists($dir_temp_index) || !(fileperms($dir_temp)>=16832)){echo '{"message":"Required to allow writing rights for a dir folder: '.$dir_temp.'"}';exit;}
}
if(isset($argv[1]) && $argv[1]=="synch"){$_REQUEST=[];$_REQUEST['type']="synch";}
if(isset($_REQUEST['type'])){
	if($_REQUEST['type']=="send"){
		if(isset($_REQUEST['recipient']) && $_REQUEST['recipient']==0)$request['recipient']=0;
		if(isset($_REQUEST['recipient']) && $_REQUEST['recipient']!=1  && isset($_REQUEST['height']) && $_REQUEST['height']<=1){echo '{"height":"false","send":"false"}';exit;}
		else if(isset($_REQUEST['date']) && ($_REQUEST['date']!=preg_replace("/[^0-9]/",'',$_REQUEST['date']) || !($_REQUEST['date']>0) || $_REQUEST['date']<$json_arr['time']-60)){echo '{"date":"false","send":"false"}';exit;}
		else if(isset($_REQUEST['wallet']) && $_REQUEST['wallet']>0 && isset($_REQUEST['height']) && $_REQUEST['height']>=0 && isset($_REQUEST['recipient']) && $_REQUEST['recipient']>=0 && isset($_REQUEST['money']) && $_REQUEST['money'] && isset($_REQUEST['pin']) && $_REQUEST['pin']>=0 && isset($_REQUEST['signpub']) && $_REQUEST['signpub'] && isset($_REQUEST['sign']) && $_REQUEST['sign']){
			$filename_temp_send= $dir_temp.'/'.$_REQUEST['wallet'].'_'.$_REQUEST['height'].'_'.hash('sha256', $_REQUEST['wallet'].$_REQUEST['height'].$_REQUEST['money'].$_REQUEST['pin'].(isset($_REQUEST['signpubreg'])?$_REQUEST['signpubreg']:'').(isset($_REQUEST['signreg'])?$_REQUEST['signreg']:'').(isset($_REQUEST['signpubnew'])?$_REQUEST['signpubnew']:'').(isset($_REQUEST['signnew'])?$_REQUEST['signnew']:'').$_REQUEST['signpub'].$_REQUEST['sign']);
			if(file_exists($filename_temp_send)){echo '{"send":"false"}';exit;}
			else file_put_contents($filename_temp_send, '');
		} else {
			if(isset($host_ip_check_file)){
				$host_ip_check_file_block= $host_ip_check_file_temp.($host_ip_check+60);
				@rename($host_ip_check_file, $host_ip_check_file_block);
			}
			echo '{"send":"false"}';exit;
		}
	} else
	if($_REQUEST['type']=="balanceall" || $_REQUEST['type']=="nodainfo"){
		$balanceall=0;
		foreach(glob($GLOBALS['dir_temp']."/balanceall_*") as $file){$balanceall= str_replace("balanceall_","", basename($file));}
		if($_REQUEST['type']=="balanceall"){
			echo '{"balanceall":"'.$balanceall.'"}';
			exit;
		}
	}
	if($_REQUEST['type']=="walletscount" || $_REQUEST['type']=="nodainfo"){
		$walletscount=0;
		foreach(glob($GLOBALS['dir_temp']."/walletscount_*") as $file){$walletscount= str_replace("walletscount_","", basename($file));}
		if($_REQUEST['type']=="walletscount"){
			echo '{"walletscount":"'.$walletscount.'"}';
			exit;
		}
	}
	if($_REQUEST['type']=="nodas"){
		$file_nodas= $dir_temp."/nodas";
		if(isset($_REQUEST['order']) && $_REQUEST['order']=='balance')$file_nodas.= "_order_balance_desc";
		else if(isset($_REQUEST['order']) && $_REQUEST['order']=='walletsuse')$file_nodas.= "_order_walletsuse_desc";
		if(file_exists($file_nodas)){
			$json_nodas= file_get_contents($file_nodas);
			if($json_nodas){echo $json_nodas;exit;}
		}
	}
	if($_REQUEST['type']=="nodainfo"){
		$file_nodainfo= $dir_temp."/nodainfo";
		if(file_exists($file_nodainfo)){
			$json_nodainfo= file_get_contents($file_nodainfo);
			if($json_nodainfo){echo $json_nodainfo;exit;}
		}
	}
	$periodStart= '2021-11-01';
	$periodEnd= '2035-03-01';
	$timeStartNewPercent= strtotime($periodStart);
	$timeEndNewPercent= strtotime($periodEnd);
	$percent_main= round(10/(100*30*86400), 12, PHP_ROUND_HALF_DOWN)+1;
	$percent_last= round(2/(100*30*86400), 12, PHP_ROUND_HALF_DOWN)+1;
	$percent_old= round(4/(100*30*86400), 12, PHP_ROUND_HALF_DOWN)+1;
	$percent_remain= 0.99;
	function pow_period($date2,$date1){
		$begin= new DateTime($date1);
		$end= new DateTime($date2);
		$end= $end->modify('+1 month');
		$interval= DateInterval::createFromDateString('1 month');
		$period= new DatePeriod($begin, $interval, $end);
		$counter= 0;
		foreach($period as $dt)$counter++;
		return $counter;
	}
	function pow_percent($percent, $percent_last, $percent_old, $timeto, $time, $multiplier){
		global $timeStartNewPercent, $timeEndNewPercent, $periodStart, $periodEnd, $percent_remain;
		if($multiplier!=1){
			$percent= round(($percent-1)*$multiplier, 12, PHP_ROUND_HALF_DOWN)+1;
			$percent_last= round(($percent_last-1)*$multiplier, 12, PHP_ROUND_HALF_DOWN)+1;
			$percent_old= round(($percent_old-1)*$multiplier, 12, PHP_ROUND_HALF_DOWN)+1;
		}
		if($timeto==$time)$result= 1;
		else if($timeto<=$timeStartNewPercent)$result= POW($percent_old,$timeto-$time);
		else if($time>=$timeEndNewPercent)$result= POW($percent_last,$timeto-$time);
		else {
			$result= 1;
			if($time<=$timeStartNewPercent){
				$result*= POW($percent_old,$timeStartNewPercent-$time);
				$time= $timeStartNewPercent;
				$timePeriodMin= 0;
			} else $timePeriodMin= pow_period(date('Y-m-01',$time),$periodStart)-1;
			if($timeto>=$timeEndNewPercent){
				$result*= POW($percent_old,$timeto-$timeEndNewPercent);
				$timePeriodMax= pow_period($periodEnd,$periodStart)-1;
			} else $timePeriodMax= pow_period(date('Y-m-01',$timeto),$periodStart)-1;
			for($i=$timePeriodMin+1, $timePeriodMin_min=$time, $j= POW($percent_remain,$timePeriodMin);$i<=$timePeriodMax+1;$i++){
				if($i!=$timePeriodMin)$j*= floatval($percent_remain);
				$timePeriodMin_max= strtotime('+'.$i.' MONTH', strtotime($periodStart));
				if($timePeriodMin_max>$timeto)$timePeriodMin_max= $timeto;
				else if($i>$timePeriodMax && $timePeriodMin_max> $timeEndNewPercent)$timePeriodMin_max= $timeEndNewPercent;
				$percent_temp= ($percent-1)*$j+1;
				if($percent_temp<$percent_last)$percent_temp= $percent_last;
				$result*=  POW($percent_temp,$timePeriodMin_max-$timePeriodMin_min);
				if($timePeriodMin_max>= $timeEndNewPercent)break;
				$timePeriodMin_min= $timePeriodMin_max;
			}
		}
		return round($result-1, 12, PHP_ROUND_HALF_DOWN);
	}
	if($_REQUEST['type']=="percent"){
		if(isset($_REQUEST['date']) && $_REQUEST['date']==preg_replace("/[^0-9]/",'',$_REQUEST['date']))$time= $_REQUEST['date'];
		else $time= $json_arr['time'];
		if(isset($_REQUEST['day']) && $_REQUEST['day']==preg_replace("/[^0-9]/",'',$_REQUEST['day']) && $_REQUEST['day']<=3650)$_REQUEST['day']= $_REQUEST['day'];
		else $_REQUEST['day']=30;
		$timeto= $time+$_REQUEST['day']*86400;
		$percent_view['time']= strval($json_arr['time']);
		$percent_view['period_day']= strval($_REQUEST['day']);
		$percent_view['percent']= strval((int)(pow_percent($GLOBALS['percent_main'], $GLOBALS['percent_last'], $GLOBALS['percent_old'], $timeto, $time, 1)*10000)/100);
		$percent_view['ref1']= strval((int)(($percent_view['percent']/4)*100)/100);
		$percent_view['ref2']= strval((int)(($percent_view['percent']/8)*100)/100);
		$percent_view['ref3']= strval((int)(($percent_view['percent']/16)*100)/100);
		$percent_view['percent_noda']= strval((int)(pow_percent($GLOBALS['percent_main'], $GLOBALS['percent_last'], $GLOBALS['percent_old'], $timeto, $time, 1.25)*10000)/100);
		$percent_view['ref1_noda']= strval((int)(($percent_view['percent_noda']/4)*100)/100);
		$percent_view['ref2_noda']= strval((int)(($percent_view['percent_noda']/8)*100)/100);
		$percent_view['ref3_noda']= strval((int)(($percent_view['percent_noda']/16)*100)/100);
		echo json_encode($percent_view);
		exit;
	}
	if($_REQUEST['type']=="send" || $_REQUEST['type']=="synch"){
		$filename_temp_synch= $dir_temp.'/synch_'.(int)date("i",$json_arr['time']);
		if($_REQUEST['type']!="send" && file_exists($filename_temp_synch)){echo '{"synch":"now"}';exit;}
		$error_falcon= '';
		include __DIR__ .'/egold_crypto/falcon.php';
		function bchexdec($hex){
			$dec = 0; $len = strlen($hex);
			for($i = 1; $i <= $len; $i++)$dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
			while(strlen($dec)<19)$dec.= '1';
			return $dec;
		}
		function sha_dec($str){return substr(bchexdec(gen_sha3($str,19)),0,19);}
		function signcheck($str,$signpub,$sign){
			$signcheck_temp= '';
			if($str && $signpub && $sign){
				try{$signcheck_temp= Falcon\verify($signpub, $str, $sign);}
				catch(Exception $ex){}
			}
			return (!$GLOBALS['error_falcon'] && $signcheck_temp? 1: 0);
		}
		if($_REQUEST['type']=="send" && isset($_REQUEST['signpubnew_check']) && $_REQUEST['signpubnew_check']){
			if(signcheck($_REQUEST['wallet'].$_REQUEST['height'],$_REQUEST['signpubnew_check'],$_REQUEST['signnew'])!=1){
				echo '{"signpubnew_check":"false"}';
				exit;
			} else $json_arr['signpubnew_check']= 'true';
		}
	} else include __DIR__ .'/egold_crypto/SHA3.php';
}
if(isset($email_domain) && $email_domain && !function_exists('mail')){$email_domain= '';}
$limit_synch= 150;
if(!isset($noda_ip) || !$noda_ip){echo '{"error":"noda_ip in egold_settings.php"}';exit;}
$noda_ip=convert_ipv6(preg_replace("/[^0-9\.]/",'',$noda_ip));
if(!isset($noda_wallet) || !$noda_wallet){echo '{"error":"noda_wallet in egold_settings.php"}';exit;}
$noda_wallet=preg_replace("/[^0-9]/",'',$noda_wallet);
if(!isset($host_db) || !$host_db){echo '{"error":"host_db in egold_settings.php"}';exit;}
if(!isset($database_db) || !$database_db){echo '{"error":"database_db in egold_settings.php"}';exit;}
if(!isset($user_db) || !$user_db){echo '{"error":"user_db in egold_settings.php"}';exit;}
if(!isset($password_db) || !$password_db){echo '{"error":"password_db in egold_settings.php"}';exit;}
if(!isset($prefix_db))$prefix_db= substr(preg_replace("/[^0-9a-zA-Z]/",'',$prefix_db),0,10);
if(!isset($prefix_db) || !$prefix_db)$prefix_db='eGOLD';
if(isset($noda_trust))foreach($noda_trust as $key=> $val)if(!$val || (!filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)))unset($noda_trust[$key]);
if(!isset($noda_trust) || count($noda_trust)<3){echo '{"error":"noda_trust minimum 3 nodas in egold_settings.php"}';exit;}
if(($key=array_search($noda_ip,$noda_trust)) !== FALSE){array_splice($noda_trust, $key, 1);}
function mysqli_connect_open(){if(!isset($GLOBALS['mysqli_connect']) || !$GLOBALS['mysqli_connect'])$GLOBALS['mysqli_connect']= mysqli_connect($GLOBALS['host_db'],$GLOBALS['user_db'],$GLOBALS['password_db'],$GLOBALS['database_db']) or die("error_connect_bd");}
function mysqli_connect_close(){if(isset($GLOBALS['mysqli_connect']) && $GLOBALS['mysqli_connect']){mysqli_close($GLOBALS['mysqli_connect']);$GLOBALS['mysqli_connect']='';}}
mysqli_connect_open();
function mysqli_query_bd($query){
	mysqli_connect_open();
	$result= mysqli_query($GLOBALS['mysqli_connect'],$query) or die("error_bd: ".$query);
	return $result;
}
function exit_now(){mysqli_connect_close();exit;}
function query_bd($query){
  global $mysqli_connect,$sqltbl;
  mysqli_connect_open();
  $result= mysqli_query_bd($query);
  if($result!== FALSE && gettype($result)!= "boolean") $GLOBALS['sqltbl']= mysqli_fetch_assoc($result);
  else $GLOBALS['sqltbl']='';
  if($GLOBALS['sqltbl'])return $GLOBALS['sqltbl'];
}
query_bd("SHOW TABLES FROM `".$database_db."` LIKE '".$GLOBALS['prefix_db']."_wallets';");
if(!isset($sqltbl['Tables_in_'.$database_db.' ('.$GLOBALS['prefix_db'].'_wallets)'])){
  $query= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = '+00:00';
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_contacts`;
CREATE TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_contacts` (
  `wallet` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `number` tinyint(3) UNSIGNED NOT NULL,
  `recipient` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history`;
CREATE TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` (
  `wallet` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `recipient` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `money` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `pin` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `height` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `nodawallet` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `nodause` varchar(40) NOT NULL DEFAULT '',
  `nodaown` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `details` varchar(250) NOT NULL DEFAULT '',
  `date` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `signpubreg` varchar(514) NOT NULL DEFAULT '',
  `signreg` varchar(1440) NOT NULL DEFAULT '',
  `signpubnew` varchar(19) NOT NULL DEFAULT '',
  `signnew` varchar(1440) NOT NULL DEFAULT '',
  `signpub` varchar(514) NOT NULL DEFAULT '',
  `sign` varchar(1440) NOT NULL DEFAULT '',
  `checkhistory` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
	`hash` VARCHAR(19) NOT NULL DEFAULT '',
  `checkemail` tinyint(1) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals`;
CREATE TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals` (
  `wallet` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `ref1` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `ref2` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `ref3` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `money1` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `money2` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `money3` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `height` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `date` bigint(20) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings`;
CREATE TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` (
  `name` varchar(100) NOT NULL DEFAULT '',
  `value` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` (`name`, `value`) VALUES
('synch_now', '0'),
('synch_wallet', '1'),
('synch_history', '".$GLOBALS['date_synch']."'),
('check_wallet', '0'),
('version', '".$version."');
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users`;
CREATE TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` (
  `wallet` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `password` varchar(128) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `up` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `down` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `nodatrue` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `date` bigint(20) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets`;
CREATE TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` (
  `wallet` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `ref1` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `ref2` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `ref3` bigint(18) UNSIGNED NOT NULL DEFAULT '0',
  `noda` varchar(40) NOT NULL DEFAULT '',
  `nodause` varchar(40) NOT NULL DEFAULT '',
  `balance` bigint(20) NOT NULL DEFAULT '0',
  `date` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `percent_ref` bigint(20) NOT NULL DEFAULT '0',
  `height` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `signpub` bigint(19) UNSIGNED NOT NULL DEFAULT '0',
  `checkbalance` varchar(20) NOT NULL DEFAULT '',
  `checkbalanceall` varchar(20) NOT NULL DEFAULT '',
  `checkwallet` varchar(20) NOT NULL DEFAULT '',
  `view` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `checknoda` varchar(20) NOT NULL DEFAULT '',
  `nodaping` varchar(20) NOT NULL DEFAULT '',
  `nodacheckwallets` varchar(20) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_contacts`
  ADD PRIMARY KEY (`wallet`,`number`) USING BTREE;
ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history`
  ADD PRIMARY KEY (`wallet`,`height`,`checkhistory`,`details`,`date`,`hash`) USING BTREE,
  ADD KEY `recipient` (`recipient`) USING BTREE,
  ADD KEY `pin` (`pin`) USING BTREE,
  ADD KEY `details` (`details`) USING BTREE,
	ADD KEY `date` (`date`) USING BTREE,
  ADD KEY `checkhistory` (`checkhistory`) USING BTREE;
ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals`
  ADD PRIMARY KEY (`wallet`,`date`) USING BTREE,
  ADD KEY `ref1` (`ref1`) USING BTREE,
  ADD KEY `ref2` (`ref2`) USING BTREE,
  ADD KEY `ref3` (`ref3`) USING BTREE;
ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings`
  ADD PRIMARY KEY (`name`) USING BTREE;
ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users`
  ADD PRIMARY KEY (`wallet`) USING BTREE,
  ADD KEY `email` (`email`) USING BTREE,
  ADD KEY `nodatrue` (`nodatrue`) USING BTREE;
ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets`
  ADD PRIMARY KEY (`wallet`) USING BTREE,
  ADD KEY `noda` (`noda`) USING BTREE,
  ADD KEY `nodause` (`nodause`) USING BTREE,
	ADD KEY `date` (`date`) USING BTREE,
  ADD KEY `ref1` (`ref1`) USING BTREE,
  ADD KEY `ref2` (`ref2`) USING BTREE,
  ADD KEY `ref3` (`ref3`) USING BTREE,
  ADD KEY `view` (`view`) USING BTREE;
COMMIT;";
  $result= mysqli_multi_query($mysqli_connect,$query) or die("error_install_bd");
  $mysqli_affected_rows=0;
  while(true){
    if(mysqli_more_results($mysqli_connect)){
      mysqli_next_result($mysqli_connect);
      $mysqli_affected_rows++;
      } else break;
  }
  if($mysqli_affected_rows==23){
		if(isset($noda_trust) && count($noda_trust)>=1 && !file_exists($GLOBALS['dir_temp'].'/backup_start.sql.gz')){
			exec('wget -bqc -N -O \''.$GLOBALS['dir_temp'].'/backup_start.sql.gz\' \'http://'.$noda_trust[mt_rand(0,count($noda_trust)-1)].'/egold_temp/backup.log\' || rm -f \''.$GLOBALS['dir_temp'].'/backup_start.sql.gz\'');
		}
		echo '{"install_bd":"true","message":"Setting a task cron to start php script \'/egold.php synch\' (it is recommended) or \'http://'.(filter_var($noda_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)?"[".$noda_ip."]":$noda_ip).'/egold.php?type=synch\' every minute and wait for synchronization"}';
  } else {
    echo '{"install_bd":"false"}';
    $query= "DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_contacts`;
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history`;
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals`;
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings`;
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users`;
DROP TABLE IF EXISTS `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets`;
COMMIT;";
    $result= mysqli_multi_query($mysqli_connect,$query) or die("error_del_bd");
  }
  exit_now();
}
if(isset($_REQUEST['type']) && $_REQUEST['type']=="synch"){
	query_bd("SELECT `value` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` WHERE `name`='version';");
	$version_sql= (int)str_replace('1.','',$sqltbl['value']);
	if($version_sql>=42 && $version_sql<(int)str_replace('1.','',$version)){
		if($version_sql<53)query_bd("ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` ADD KEY `date` (`date`) USING BTREE;");
		if($version_sql<54){
			query_bd("ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` ADD KEY `date` (`date`) USING BTREE;");
			query_bd("ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` DROP `nodaerror`;");
		}
		if($version_sql<56)query_bd("ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` DROP `date_ref`;");
		if($version_sql<59){
			query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` (`name`, `value`) VALUES ('synch_history', '".$GLOBALS['date_synch']."');");
			query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` WHERE `name` = 'synch_wallet_history';");
			query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` WHERE `name` = 'synch_wallet_last_history';");
		}
		if($version_sql<63){
			query_bd("ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` ADD `hash` VARCHAR(19) NOT NULL DEFAULT '' AFTER `checkhistory`;");
			query_bd("ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` DROP PRIMARY KEY, ADD PRIMARY KEY (`wallet`,`height`,`checkhistory`,`details`,`date`,`hash`) USING BTREE;");
		}
		if($version_sql<65){
			query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `signpubreg`='', `signreg`='', `signpubnew`='', `signnew`='', `signpub`='', `sign`='', `checkhistory`=1, `hash`='' WHERE `checkhistory`!=0;");
			query_bd("ALTER TABLE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` DROP `sign`;");
		}
		query_bd("REPLACE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` SET `value`='".$version."', `name`='version';");
	}
}
$type['type']="a-z";
$type['wallet']="0-9";
$type['recipient']=$type['wallet'];
$type['history']="0-9";
$type['ref1']=$type['wallet'];
$type['ref2']=$type['wallet'];
$type['ref3']=$type['wallet'];
$type['money']="0-9";
$type['pin']="0-9";
$type['height']="0-9";
$type['signpubnew']="0-9";
$type['signpub']="0-9a-z";
$type['signpubreg']=$type['signpub'];
$type['sign']="0-9a-z:";
$type['signnew']=$type['sign'];
$type['signreg']=$type['sign'];
$type['date']="0-9";
$type['dateto']=$type['date'];
$type['dateview']="1";
$type['noda']="0-9\.";
$type['nodawallet']=$type['wallet'];
$type['nodause']=$type['noda'];
$type['nodaown']="0-1";
$type['details']="0-9a-zA-Z\-\@\.\,\:\;\!\#\$\*\+\=\?\&\_\{\|\}\~\(\)\/\' \^\✚\✖\◆";
$type['order']="a-z";
$type['start']="0-9";
$type['limit']="0-9";
$type['all']="0-9";
$type['password']="0-9a-z";
$type['up']="0-9";
$type['down']="0-9";
$type['ref']="0-9";
$type['ref1']="0-9";
$type['ref2']="0-9";
$type['ref3']="0-9";
$type['email']="0-9";
$type['wallets_with_noda_first']="1";
$type['synch_wallet']="0-9";
$type['checkhistory']="0-9";
foreach($_REQUEST as $key=> $val) if(strlen($key)<100 && $val && strlen($val)<1440 && in_array($key,array_keys($type))) $request[$key]= preg_replace("/[^".$type[$key]."]/",'',$val);
if(isset($_REQUEST['p2p']))$request['p2p']= '';
else if(isset($_REQUEST['sms']))$request['sms']= '';
if(!isset($request))$stop=1;
else $stop=0;
function gold_wallet_view($wallet){return 'G-'.substr($wallet,0,4).'-'.substr($wallet,4,5).'-'.substr($wallet,9,4).'-'.substr($wallet,13,5);}
if(!isset($request['type']))$request['type']='';
if(isset($request['details'])){
	if(isset($request['details']) && ($request['details']!=$_REQUEST['details'] || strlen($request['details'])>250 || (isset($request['recipient']) && $request['recipient']!=1 && $request['type']=='send') || isset($request['signpubnew']) || isset($request['signnew']) || isset($request['signpubreg']) || isset($request['signreg']) || $_REQUEST['details']!= $request['details'])){
		echo '{"details":"false"}';
		exit_now();
	} else {
		$request['details']= preg_replace('/[ ]{2,}/', ' ', trim($request['details']));
		$patterns[0] = '/\✚/';
		$patterns[1] = '/\✖/';
		$patterns[2] = '/\^/';
		$patterns[3] = '/\◆/';
		$replacements[0] = '+';
		$replacements[1] = '*';
		$replacements[2] = '$';
		$replacements[3] = '&';
		$request['details']= preg_replace($patterns,$replacements,$request['details']);
		if(!$request['details'])unset($request['details']);
	}
}
if($request['type']=="email" && isset($request['wallet']) && strlen($request['wallet'])==18 && isset($request['email']) && isset($request['password'])){
	function intToChar($str){
		$intStr= str_split($str, 4);
		$outText= '';
		for($j= 0;$j<count($intStr);$j++){$outText.= chr($intStr[$j]);}
		return $outText;
	}
	function xor_this($text,$key){
		$outText= '';
		for($i=0;$i<strlen($text);){for($j=0;$j<strlen($key)&&$i<strlen($text);$j++,$i++){$outText.= $text{$i}^$key{$j};}}
		return $outText;
	}
	query_bd("SELECT `password` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` WHERE `wallet`= '".$request['wallet']."' and `nodatrue`=1 LIMIT 1;");
	if(isset($sqltbl['password']) && gen_sha3($sqltbl['password'],256)==$request['password']){
		$request['email']= xor_this(intToChar($request['email']),$sqltbl['password']);
		if(filter_var($request['email'], FILTER_VALIDATE_EMAIL) === false || mysqli_real_escape_string($mysqli_connect,$request['email'])!=$request['email'])$stop=1;
	} else $stop=1;
}
if(isset($request['noda']) && !filter_var($request['noda'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !filter_var($request['noda'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
  echo '{"noda_ip_check":"false"}';
  exit_now();
}
if(isset($request['nodause']) && !filter_var($request['nodause'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !filter_var($request['nodause'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
  echo '{"nodause_ip_check":"false"}';
  exit_now();
}
$json_arr['noda']= $noda_ip;
function timer($time){
	global $json_arr,$date_synch;
	$timer= microtime(true)-$date_synch;
	if($timer<$time){
		$timer=$time-$timer;
		if($timer<=0)$timer=0.1;
		if($timer>=1)mysqli_connect_close();
		usleep($timer*1000000);
		mysqli_connect_open();
	}
}
function wallet($wallet,$time,$checkhistory,$wallet_type,$type){
	global $sqltbl;
	if($wallet_type==1 || strlen($wallet)==18){
		if($wallet_type!=1)query_bd("SELECT `wallet`, `ref1`, `ref2`, `ref3`, `noda`, `nodause`, `balance`, `date`, `percent_ref`, `height`, `signpub`, `view`, `nodaping` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`='".$wallet."' and `view`>0  LIMIT 1;");
		else {
			$sqltbl= $wallet;
			$wallet= $wallet['wallet'];
		}
		if(isset($sqltbl['wallet'])) {
			$wallet_return= $sqltbl;
			$wallet_return['balancecheck']= 0;
			if($checkhistory==0){
				query_bd("SELECT SUM(`money`+2) as balancecheck, MIN(`date`) as date FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`='".$wallet."' and `checkhistory`= 0 LIMIT 1;");
				if(isset($sqltbl['balancecheck'])){
					$wallet_return['balancecheck']= (int)$sqltbl['balancecheck'];
					$wallet_return['balance']= $wallet_return['balance']-$sqltbl['balancecheck'];
					$time= $sqltbl['date'];
					query_bd("SELECT `date` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`='".$wallet."' and `checkhistory`=1 ORDER by `date` DESC LIMIT 1;");
					if(isset($sqltbl['date']) && $wallet_return['date']< $sqltbl['date'])$wallet_return['date']= $sqltbl['date'];
				}
			}
			$balance_temp= $wallet_return['balance']+$wallet_return['balancecheck'];
			$wallet_return['percent_4']= $balance_temp*pow_percent($GLOBALS['percent_main'], $GLOBALS['percent_last'], $GLOBALS['percent_old'], $time, $wallet_return['date'], 1);
			if($type!=1)$wallet_return['percent_4']= (int)$wallet_return['percent_4'];
			$wallet_return['percent_5']= $balance_temp*pow_percent($GLOBALS['percent_main'], $GLOBALS['percent_last'], $GLOBALS['percent_old'], $time, $wallet_return['date'], 1.25);
			if($type!=1)$wallet_return['percent_5']= (int)$wallet_return['percent_5'];
			return $wallet_return;
		} else return 0;
	} else return -1;
}
function noda_owner(){
	global $sqltbl,$json_arr;
	query_bd("SELECT `wallet` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`='".$GLOBALS['noda_ip']."' ORDER BY `date` DESC LIMIT 1;");
	if(isset($sqltbl['wallet']))$json_arr['owner']= gold_wallet_view($sqltbl['wallet']);
	else {
		$json_arr['owner']= gold_wallet_view($GLOBALS['noda_wallet']);
		$json_arr['status']= "not_activated";
	}
}
if($stop==1){
	noda_owner();
	foreach(glob($GLOBALS['dir_temp']."/balanceall_*") as $file){$json_arr['balanceall']= str_replace("balanceall_","", basename($file));}
	foreach(glob($GLOBALS['dir_temp']."/walletscount_*") as $file){$json_arr['walletscount']= str_replace("walletscount_","", basename($file));}
}
if($request['type']=="synch" || (isset($request['nodause']) && isset($request['date']) && isset($request['nodawallet'])))$json_arr['send_noda']= 1;
else $json_arr['send_noda']= 0;
if($stop!=1 && ($request['type']=="send" || $request['type']=="history") && (!isset($request['pin']) || !$request['pin'])){
	$request['pin']=0;
}
if($stop!=1 && $request['type']=="height"){
	delay_now();
  if(!isset($request['wallet']) || strlen($request['wallet'])!=18){echo '{"wallet":"false"}';exit_now();}
  if(isset($request['nodause']))$nodause= $request['nodause'];
  else $nodause= $noda_ip;
  $wallet= wallet($request['wallet'],$json_arr['time'],0,0,0);
  if(isset($wallet['height']) && isset($wallet['date']) && isset($wallet['view']) && ($wallet['view']==1 || $wallet['view']==3)){
    $json_arr['balance']= $wallet['balance']+$wallet['percent_4'];
    $json_arr['height']= $wallet['height'];
    $json_arr['date']= $wallet['date'];
    query_bd("SELECT `height`,`date` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`= '".$request['wallet']."' and `checkhistory`= 0 and `height`>'".$wallet['height']."' ORDER BY `height` DESC,`date` DESC LIMIT 1;");
    if(isset($sqltbl['height'])){
      $json_arr['height']= $sqltbl['height'];
      $json_arr['date']= $sqltbl['date'];
    }
    if(isset($request['height']) && (int)$request['height']>=0 && (int)$request['height']==$request['height'] && $json_arr['height']!=$request['height']-1){echo '{"height":"false"}';exit_now();}
    if($request['type']=="height")$stop=1;
    else if($json_arr['time']-$json_arr['date']<=4){echo '{"send":"timeout"}';exit_now();}
  } else {echo '{"wallet":"unavailable"}';exit_now();}
  unset($nodause);
}
if($stop!=1){
  function connect_noda_multi($urls,$path,$post,$timer,$type_post){
    global $mysqli_connect,$sqltbl,$noda_ip,$json_arr;
		if($timer>=1)mysqli_connect_close();
		usleep(mt_rand(0.5,0.55)*1000000);
    if(!is_array($urls)){
      $url= $urls;
      $urls= array();
      $urls[]= $url;
    }
    if(isset($urls) && count($urls)>=1){
      $multi= curl_multi_init();
      $channels= array();
      $json_get_arr= array();
      foreach ($urls as $url){
		if($type_post==1){
			$post_send['type']= "checkwallets";
			$post_send['wallets']=json_encode(array_keys($post[$url]));
		} else $post_send=$post;
		$ch= curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://".(filter_var($url, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)?"[".$url."]":$url).'/egold.php'.$path);
		curl_setopt($ch, CURLOPT_HEADER, false);
		if($post_send){
		  curl_setopt($ch, CURLOPT_POST, true);
		  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_send);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timer*1000);
		curl_setopt($ch, CURLOPT_IPRESOLVE, (filter_var($noda_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)?CURL_IPRESOLVE_V6:CURL_IPRESOLVE_V4));
		curl_multi_add_handle($multi, $ch);
		$channels[$url]= $ch;
      }
      $active= null;
      do $mrc= curl_multi_exec($multi, $active);
      while ($mrc== CURLM_CALL_MULTI_PERFORM);
      while ($active && $mrc== CURLM_OK){
        if (curl_multi_select($multi)== -1)continue;
        do $mrc= curl_multi_exec($multi, $active);
        while ($mrc== CURLM_CALL_MULTI_PERFORM);
      }
      foreach ($channels as $channel=> $val){
        $json_get_arr[$channel]= json_decode(trim(curl_multi_getcontent($val)),true);
        curl_multi_remove_handle($multi, $val);
      }
      curl_multi_close($multi);
    }
	mysqli_connect_open();
	if(isset($json_get_arr) && $json_get_arr)return $json_get_arr;
  }
  function random($array,$count){
    if(is_array($array) && count($array)>$count){
      $keys = array_keys($array);
      shuffle($keys);
      foreach($keys as $key)$new[$key] = $array[$key];
      $array = array_slice($new,0,$count);
    }
    return $array;
  }
  function deals($wallet,$date,$pin,$details,$checkhistory1,$checkhistory2,$day_limit){
		global $json_arr,$sqltbl,$mysqli_connect;
		query_bd("SELECT `wallet`,`height`,`date`,`money`,`nodawallet`,`nodause`,`pin`,`details` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE ".($date?"`date`='".$date."'":'')." ".($wallet?" and `wallet`='".$wallet."'":'')." ".($details?"`details` LIKE '".$details."%'":"and `details`!=''")." ".($pin?"and `pin`='".$pin."'":'')." and (`checkhistory`='".$checkhistory1."' ".($checkhistory2>0?" or `checkhistory`='".$checkhistory2."'":'').") and `recipient`=1 ".($day_limit>0?"and `date`>= ".($GLOBALS['date_synch']-$day_limit*86400):'')." ORDER BY `date` LIMIT 1;");
		return $sqltbl;
  }
  function deals_wallet_update($wallet,$height,$money,$nodause,$nodawallet,$date,$pin,$details){
		global $json_arr,$sqltbl,$mysqli_connect;
		query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `balance`=`balance`+'".$money."',`view`=IF(`view`=1,3,`view`) WHERE `wallet`='".$wallet."';");
		query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `wallet`=1, `recipient`= '".$wallet."', `money`= '".$money."', `height`= 0, `pin`= '".$pin."', `details`= CONCAT('".$wallet."','w".$height."'), `date`= '".($date+1)."', `nodause`= '".$nodause."', `nodawallet`= '".$nodawallet."', `checkhistory`=1;");
  }
  function deals_close($deal,$deal_type){
		global $json_arr,$sqltbl,$mysqli_connect;
		if(!isset($deal['details']))$deal['details']= $deal['date'];
		deals_wallet_update($deal['wallet'],$deal['height'],$deal['money'],$deal['nodause'],$deal['nodawallet'],($deal_type>0?$deal['date']:strtotime(date("Y-m-d H:i:00",$deal['date']+3*86400))),$deal['wallet'],$deal['details']);
		query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`='".$deal['wallet']."' and `date`='".$deal['details']."' and `recipient`=1 and `checkhistory`=6 and `details`!='' ORDER BY `date` LIMIT 1;");
		if(!isset($sqltbl['height']))query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=6 WHERE `wallet`='".$deal['wallet']."' and `date`='".$deal['details']."' and `recipient`=1 and `checkhistory`=1 and `details`!='';");
		else query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`='".$deal['wallet']."' and `date`='".$deal['details']."' and `recipient`=1 and `checkhistory`=1 and `details`!='';");
		query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `details` LIKE '%|".$deal['details']."%' and `checkhistory`=6 and `date`>'".$deal['details']."' and `recipient`=1;");
		if(!isset($sqltbl['height']))query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=6 WHERE `details` LIKE '%|".$deal['details']."%' and `checkhistory`>0 and `checkhistory`!=2 and `date`>'".$deal['details']."' and `recipient`=1;");
		else query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `details` LIKE '%|".$deal['details']."%' and `checkhistory`>0 and `checkhistory`!=2 and `date`>'".$deal['details']."' and `recipient`=1;");
  }
  function deals_cancel($deal,$deal_type){
		global $json_arr,$sqltbl,$mysqli_connect;
		if($deal_type!=1){
			$deal['details']=str_replace('1|','',$deal['details']);
			deals($deal['pin'],$deal['details'],'','',3,0,4);
			if(isset($sqltbl['height'])){
				$deal['buyer_wallet']= $deal['wallet'];
				$deal['wallet']= $sqltbl['wallet'];
				$deal['height']= $sqltbl['height'];
				$deal['nodawallet']= $sqltbl['nodawallet'];
				$deal['nodause']= $sqltbl['nodause'];
				$deal['date']= strtotime(date("Y-m-d H:i:00",$deal['date']))+1*86400;
			}
		} else deals_wallet_update($deal['buyer_wallet'],$deal['buyer_height'],$deal['money'],$deal['buyer_nodause'],$deal['buyer_nodawallet'],$deal['date'],$deal['wallet'],$deal['details']);
		if(isset($deal['buyer_wallet'])){
			deals_wallet_update($deal['wallet'],$deal['height'],($deal['money']==1?120:12*$deal['money']),$deal['nodause'],$deal['nodawallet'],$deal['date'],$deal['wallet'],$deal['details']);
			query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`='".$deal['wallet']."' and `date`='".$deal['details']."' and `recipient`=1 and `checkhistory`=7 and `details`!='';");
			if(!isset($sqltbl['height']))query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=7 WHERE `wallet`='".$deal['wallet']."' and `date`='".$deal['details']."' and `recipient`=1 and `checkhistory`=3 and `details`!='';");
			else query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`='".$deal['wallet']."' and `date`='".$deal['details']."' and `recipient`=1 and `checkhistory`=3 and `details`!='';");
			query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `details` LIKE '%|".$deal['details']."%' and `checkhistory`=7 and `date`>'".$deal['details']."' and `recipient`=1;");
			if(!isset($sqltbl['height']))query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=7 WHERE `details` LIKE '%|".$deal['details']."%' and `checkhistory`>0 and `checkhistory`!=2 and `date`>'".$deal['details']."' and `recipient`=1;");
			else query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `details` LIKE '%|".$deal['details']."%' and `checkhistory`>0 and `checkhistory`!=2 and `date`>'".$deal['details']."' and `recipient`=1;");
		}
  }
  function wallet_check(){
    global $json_arr,$sqltbl,$mysqli_connect,$noda_ip;
    $result= mysqli_query_bd("SELECT * FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `checkhistory`= 0 and `date`<".($GLOBALS['date_synch']-60)." ORDER BY `date`,`wallet`,`height` LIMIT 10000;");
    while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
			$wallet_update= 0;
			query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`= '".$sqltbl_arr['wallet']."' and `height`= '".$sqltbl_arr['height']."' and `checkhistory`>0 and `checkhistory`!=2;");
			if(isset($sqltbl['height']))$history_delete= 1;
			else if($sqltbl_arr['recipient']==1 && $sqltbl_arr['pin']>0 && ($sqltbl_arr['signpubreg'] || $sqltbl_arr['signreg'] || $sqltbl_arr['signpubnew'] || $sqltbl_arr['signnew']))$history_delete= 1;
			else if(($sqltbl_arr['signpubreg'] || $sqltbl_arr['signreg']) && $sqltbl_arr['money']!=3)$history_delete= 1;
			else if(strlen($sqltbl_arr['money'])>=13)$history_delete= 1;
			else {
				$wallet= wallet($sqltbl_arr['wallet'],$sqltbl_arr['date'],1,0,0);
				if(!isset($wallet['wallet']) || $wallet['wallet']!=$sqltbl_arr['wallet'] || $wallet['height']!=$sqltbl_arr['height']-1){
					usleep(100);
					$wallet= wallet($sqltbl_arr['wallet'],$sqltbl_arr['date'],1,0,0);
				}
				if($sqltbl_arr['recipient']==1 && $sqltbl_arr['details']=='' && $sqltbl_arr['pin']>1 && strlen($sqltbl_arr['pin'])!=18){
					if($sqltbl_arr['money']==100 && $sqltbl_arr['nodaown']==1 && $wallet['noda']!=$sqltbl_arr['nodause'] && $sqltbl_arr['pin']==preg_replace("/[^0-9]/",'',$sqltbl_arr['nodause'])){
						if($wallet['balance']+$wallet['percent_4']-$sqltbl_arr['money']-2>=0){
							query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`='".$sqltbl_arr['nodause']."' LIMIT 1;");
							if(!isset($sqltbl['height'])){
								$wallet['noda']= $sqltbl_arr['nodause'];
								$noda_set= 1;
							} else $history_delete= 1;
						} else $history_delete= 1;
					} else if($sqltbl_arr['money']==1 && $wallet['noda'] && $sqltbl_arr['pin']==preg_replace("/[^0-9]/",'',$wallet['noda'])){
						$wallet['noda']='';
						$noda_set= 2;
					} else $history_delete= 1;
				}
			}
			if(!isset($history_delete)){
				query_bd("SELECT COUNT(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`= '".$sqltbl_arr['wallet']."' and (`date`>= ".$sqltbl_arr['date']."-3 and `date`<= ".$sqltbl_arr['date']."+3) LIMIT 1;");
				if(isset($sqltbl['count']) && (int)$sqltbl['count']>1){
					query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=2 WHERE `checkhistory`= 0 and `wallet`= '".$sqltbl_arr['wallet']."' and (`date`>= ".$sqltbl_arr['date']."-3 and `date`<= ".$sqltbl_arr['date']."+3);");
					$history_delete= 1;
				}
			}
			if(!isset($history_delete) && $sqltbl_arr['checkhistory']==0 && isset($wallet['wallet']) && $wallet['view']>0 && $wallet['height']==$sqltbl_arr['height']-1){
				$wallet_update=-1;
				if(($wallet['signpub']== sha_dec($sqltbl_arr['signpubnew']) || $wallet['signpub']== sha_dec($sqltbl_arr['signpub'])) && signcheck($wallet['wallet'].($sqltbl_arr['signpubreg'] && $sqltbl_arr['signreg'] && $sqltbl_arr['money']==3?'0':$sqltbl_arr['recipient']).$sqltbl_arr['money'].$sqltbl_arr['pin'].$sqltbl_arr['height'].$sqltbl_arr['nodause'].$sqltbl_arr['details'].($sqltbl_arr['signpubreg'] && $sqltbl_arr['signreg']?$sqltbl_arr['signpubreg'].$sqltbl_arr['signreg']:'').($sqltbl_arr['signpubnew'] && $sqltbl_arr['signnew']?$sqltbl_arr['signpubnew'].$sqltbl_arr['signnew']:''),$sqltbl_arr['signpub'],$sqltbl_arr['sign'])==1){
					$wallet_percent=(!(isset($noda_set) && $noda_set==1) && $wallet['noda']?$wallet['percent_5']:$wallet['percent_4']);
					$wallet_balance_percent= $wallet['percent_ref']+$wallet_percent;
					$wallet_balance= $wallet['balance']+$wallet_balance_percent-($sqltbl_arr['money']+2);
					if(!isset($history_delete) && $sqltbl_arr['checkhistory']==0 && $wallet_balance>=0){
						if($sqltbl_arr['recipient']==1 && $sqltbl_arr['details']!=''){
							if(strlen($sqltbl_arr['pin'])==18){
								if($sqltbl_arr['details']==preg_replace("/[^0-9\|]/",'',$sqltbl_arr['details']) && strlen($sqltbl_arr['details'])<= 40){
									$details_arr= explode('|',$sqltbl_arr['details']);
									if(is_array($details_arr) && (count($details_arr)==2 || (count($details_arr)==3 && strlen($details_arr[2])==strlen((int)$details_arr[2]) && strlen($details_arr[2])==18 && $sqltbl_arr['wallet']!=$details_arr[2])) && strlen($details_arr[0])==strlen((int)$details_arr[0]) && $details_arr[0]>0 && strlen($details_arr[1])==strlen((int)$details_arr[1]) && strlen($details_arr[1])>0){
										$type_action= (int)$details_arr[0];
										$sqltbl_arr['details']= (int)$details_arr[1];
										if((count($details_arr)==2 && $type_action!=3 && $type_action!=6) || (count($details_arr)==3 && ($type_action==3 || $type_action==4 || ($type_action==6 && $sqltbl_arr['pin']==$details_arr[2])))){
											if($type_action==1){
												deals($sqltbl_arr['pin'],$sqltbl_arr['details'],'','',1,0,3);
												if(isset($sqltbl['height']) && $sqltbl['wallet']!=$sqltbl_arr['wallet']){
													$seller_height= $sqltbl['height'];
													$seller_nodawallet= $sqltbl['nodawallet'];
													$seller_nodause= $sqltbl['nodause'];
													$money_seller_max= $sqltbl['money'];
													if($sqltbl_arr['money']>10){
														if($sqltbl['pin']> 100){
															$money_seller_min= (int)(1.2*$sqltbl['pin']);
															if($money_seller_min > $money_seller_max)$money_seller_min= $money_seller_max;
														} else $money_seller_min= 120;
													} else if($sqltbl['pin']<= 100 && $sqltbl_arr['money']==1){
														$money_seller_min= 120;
														$money_buyer= 120;
													} else $history_delete= 1;
													if(!isset($history_delete) || $history_delete!= 1){
														if(!isset($money_buyer))$money_buyer= 12*$sqltbl_arr['money'];
														$money_return= $money_seller_max-ceil($money_buyer);
														$money_buyer= (int)$money_buyer;
														if(!($money_buyer >= 120 && $money_seller_max+9 >= $money_buyer && $money_seller_min <= $money_buyer))$history_delete= 1;
													}
												} else $history_delete= 1;
											} else if($type_action==2 && $sqltbl_arr['money']==1 && $sqltbl_arr['wallet']==$sqltbl_arr['pin']){
												deals($sqltbl_arr['wallet'],$sqltbl_arr['details'],'','',3,5,7);
												if(isset($sqltbl['height'])){
													$seller_wallet= $sqltbl['wallet'];
													$seller_date= $sqltbl['date'];
													$seller_nodawallet= $sqltbl['nodawallet'];
													$seller_nodause= $sqltbl['nodause'];
													$seller_height= $sqltbl['height'];
													deals('','',$seller_wallet,'1|'.$seller_date,1,5,3);
													if(isset($sqltbl['height'])){
														$buyer_wallet= $sqltbl['wallet'];
														$buyer_date= $sqltbl['date'];
														$buyer_details= $sqltbl['details'];
														$buyer_nodawallet= $sqltbl['nodawallet'];
														$buyer_nodause= $sqltbl['nodause'];
														$buyer_height= $sqltbl['height'];
														$buyer_money= $sqltbl['money'];
														deals('','',$seller_wallet,'4|'.$seller_date,5,0,3);
														if(isset($sqltbl['height']) && ($buyer_money==1?90:9*$buyer_money)==$sqltbl['money']){
															$arbitr_wallet= $sqltbl['wallet'];
															$arbitr_nodawallet= $sqltbl['nodawallet'];
															$arbitr_nodause= $sqltbl['nodause'];
															$arbitr_height= $sqltbl['height'];
															$seller_money= (int)($buyer_money==1?15:1.5*$buyer_money);
															$arbitr_money= (int)($buyer_money==1?90:9.45*$buyer_money);
															$buyer_money= (int)($buyer_money==1?100:10.5*$buyer_money);
														} else {
															$seller_money= (int)($buyer_money==1?20:2*$buyer_money);
															$buyer_money= (int)($buyer_money==1?101:11*$buyer_money);
														}
													} else $history_delete= 1;
												} else $history_delete= 1;
											} else if((($type_action==3 && isset($details_arr[2]) && $details_arr[2]!=$sqltbl_arr['pin']) || $type_action==6) && $sqltbl_arr['money']==1){
												deals($sqltbl_arr['pin'],$sqltbl_arr['details'],'','',3,0,4);
												if(isset($sqltbl['height'])){
													$seller_wallet= $sqltbl['wallet'];
													$seller_date= $sqltbl['date'];
													deals('','',$seller_wallet,'1|'.$seller_date,1,0,1);
													if(!isset($sqltbl['height']) || $sqltbl['wallet']!=$sqltbl_arr['wallet'])$history_delete= 1;
												} else $history_delete= 1;
											} else if($type_action==4 && $sqltbl_arr['money']>=90){
												deals($sqltbl_arr['pin'],$sqltbl_arr['details'],'','',3,0,4);
												if(isset($sqltbl['height'])){
													$seller_wallet= $sqltbl['wallet'];
													$seller_date= $sqltbl['date'];
													deals('','',$seller_wallet,'1|'.$seller_date,1,0,1);
													if(isset($sqltbl['height'])){
														$buyer_wallet= $sqltbl['wallet'];
														$buyer_money= $sqltbl['money'];
														if(($buyer_money==1?90:9*$buyer_money)==$sqltbl_arr['money']){
															if($buyer_wallet==$sqltbl_arr['wallet'] && isset($details_arr[2]))$history_delete= 1;
															else if($buyer_wallet!=$sqltbl_arr['wallet']){
																if(isset($details_arr[2]) && $sqltbl['wallet']==$details_arr[2])deals('','',$seller_wallet,'3|'.$seller_date.'|'.$sqltbl_arr['wallet'],1,0,1);
																else $history_delete= 1;
															}
															if(isset($sqltbl['height']))$arbitr_checkhistory= 5;
															else $history_delete= 1;
														} else $history_delete= 1;
													} else $history_delete= 1;
												} else $history_delete= 1;
											} else if($type_action==5 && $sqltbl_arr['money']==1 && $sqltbl_arr['wallet']==$sqltbl_arr['pin']){
												deals($sqltbl_arr['pin'],$sqltbl_arr['details'],'','',1,0,4);
												if(isset($sqltbl['height'])){
													$deals_close_true['wallet']= $sqltbl['wallet'];
													$deals_close_true['height']= $sqltbl['height'];
													$deals_close_true['nodawallet']= $sqltbl_arr['nodawallet'];
													$deals_close_true['nodause']= $sqltbl_arr['nodause'];
													$deals_close_true['date']= $sqltbl_arr['date'];
													$deals_close_true['money']= $sqltbl['money'];
													$deals_close_true['details']= $sqltbl_arr['details'];
												} else $history_delete= 1;
											} else if($type_action==7 && $sqltbl_arr['money']==1 && $sqltbl_arr['wallet']==$sqltbl_arr['pin']){
												deals($sqltbl_arr['pin'],$sqltbl_arr['details'],'','',3,0,7);
												if(isset($sqltbl['height'])){
													$deals_cancel_true['wallet']= $sqltbl['wallet'];
													$deals_cancel_true['height']= $sqltbl['height'];
													$deals_cancel_true['nodawallet']= $sqltbl['nodawallet'];
													$deals_cancel_true['nodause']= $sqltbl['nodause'];
													$deals_cancel_true['details']= $sqltbl['date'];
													$deals_cancel_true['date']= $sqltbl_arr['date'];
													deals('','',$deals_cancel_true['wallet'],'1|'.$deals_cancel_true['details'],1,0,1);
													if(isset($sqltbl['height'])){
														$deals_cancel_true['buyer_wallet']= $sqltbl['wallet'];
														$deals_cancel_true['buyer_height']= $sqltbl['height'];
														$deals_cancel_true['buyer_nodawallet']= $sqltbl['nodawallet'];
														$deals_cancel_true['buyer_nodause']= $sqltbl['nodause'];
														$deals_cancel_true['money']= $sqltbl['money'];
														deals('','',$deals_cancel_true['wallet'],'6|'.$deals_cancel_true['details'].'|'.$deals_cancel_true['wallet'],1,0,1);
														if(!isset($sqltbl['height']))$history_delete= 1;
													} else $history_delete= 1;
												} else $history_delete= 1;
											} else $history_delete= 1;
										} else $history_delete= 1;
									} else $history_delete= 1;
								} else $history_delete= 1;
							} else if(strlen($sqltbl_arr['pin'])<13 && !($sqltbl_arr['money']>=100 && ($sqltbl_arr['money']/1.2)%10==0 && ($sqltbl_arr['pin']>=100 || $sqltbl_arr['pin']==0) && $sqltbl_arr['pin']%10==0 && $sqltbl_arr['money']>=1.2*$sqltbl_arr['pin']))$history_delete= 1;
							else if(($sqltbl_arr['pin']>1000000000000 && $sqltbl_arr['money']!=1) || ($sqltbl_arr['pin']==1000000000000 && $sqltbl_arr['money']!=100))$history_delete=1;
							else if(!($sqltbl_arr['pin']>0))$history_delete=1;
						}
						if(!isset($history_delete)){
							$recipient= wallet($sqltbl_arr['recipient'],$sqltbl_arr['date'],1,0,0);
							if(isset($recipient['wallet'])){
								$recipient_percent=($recipient['noda']?$recipient['percent_5']:$recipient['percent_4']);
								$recipient_balance_percent= $recipient['percent_ref']+$recipient_percent;
								$recipient_balance= $recipient['balance']+$recipient_balance_percent+$sqltbl_arr['money'];
								query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `balance`='".$recipient_balance."', `ref1`='".$recipient['ref1']."', `ref2`='".$recipient['ref2']."', `ref3`='".$recipient['ref3']."', `percent_ref`= 0,`date`=IF(`date`<'".$sqltbl_arr['date']."','".$sqltbl_arr['date']."',`date`),`view`=IF(`view`=1,3,`view`) WHERE `wallet`='".$recipient['wallet']."';");
								if(mysqli_affected_rows($mysqli_connect)>=1 && $recipient_balance_percent>0){
									query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `wallet`=1, `recipient`= '".$recipient['wallet']."', `money`= '".$recipient_balance_percent."', `height`= 0, `details`= CONCAT('".$wallet['wallet']."','r".$sqltbl_arr['height']."'), `pin`= 0, `date`= '".($sqltbl_arr['date']+1)."', `nodause`= '".$sqltbl_arr['nodause']."', `nodawallet`= '".$sqltbl_arr['nodawallet']."', `checkhistory`=1;");
								}
							} else if($sqltbl_arr['signpubreg'] && $sqltbl_arr['signreg']){
								if(strlen($sqltbl_arr['pin'])==18){
									query_bd("SELECT `wallet`,`ref1`,`ref2` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`='".$sqltbl_arr['pin']."' and `view`>0  LIMIT 1;");
									if(isset($sqltbl['wallet'])){
										$regwalletref1= $sqltbl_arr['pin'];
										$regwalletref2= $sqltbl['ref1'];
										$regwalletref3= $sqltbl['ref2'];
									}
								} else if($sqltbl_arr['pin']==1){
									$regwalletref1= '';
									$regwalletref2= '';
									$regwalletref3= '';
								}
								if(!isset($regwalletref1)){
									$regwalletref1= $wallet['wallet'];
									$regwalletref2= $wallet['ref1'];
									$regwalletref3= $wallet['ref2'];
								}
								query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `wallet`= '".$sqltbl_arr['recipient']."',`ref1`= '".$regwalletref1."',`ref2`= '".$regwalletref2."',`ref3`= '".$regwalletref3."',`balance`= 3,`height`= 0,`date` = '".$sqltbl_arr['date']."',`percent_ref`= 0,`signpub`= '".sha_dec($sqltbl_arr['signpubreg'])."',`view`=3;");
								unset($regwalletref1);
								unset($regwalletref2);
								unset($regwalletref3);
							}
							query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `nodaping`=IF(`noda`='".$wallet['noda']."',`nodaping`,''),`noda`='".$wallet['noda']."',`nodause`='".$sqltbl_arr['nodause']."',`balance`='".$wallet_balance."',`percent_ref`= 0,`height`='".$sqltbl_arr['height']."',`ref1`='".$wallet['ref1']."',`ref2`='".$wallet['ref2']."',`ref3`='".$wallet['ref3']."',`date`=IF(`date`<'".$sqltbl_arr['date']."','".$sqltbl_arr['date']."',`date`),`view`=IF(`view`=1,3,`view`), `signpub`='".($sqltbl_arr['signpubnew']?$sqltbl_arr['signpubnew']:sha_dec($sqltbl_arr['signpub']))."' WHERE `wallet`='".$wallet['wallet']."';");
							if(mysqli_affected_rows($mysqli_connect)>=1){
								$wallet_update= 1;
								if($wallet_balance_percent>0){
									query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `wallet`=1, `recipient`= '".$wallet['wallet']."', `money`= '".$wallet_balance_percent."', `height`= 0, `details`= CONCAT('".$wallet['wallet']."','w".$sqltbl_arr['height']."'), `pin`= 0, `date`= '".($sqltbl_arr['date']+1)."', `nodause`= '".$sqltbl_arr['nodause']."', `nodawallet`= '".$sqltbl_arr['nodawallet']."', `checkhistory`=1;");
								}
								if($wallet_percent/4>=1 && isset($wallet['ref1']) && $wallet['ref1']>1){
									query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `percent_ref`=`percent_ref`+'".(int)($wallet_percent/4)."', `view`=IF(`view`=1,3,`view`) WHERE `wallet`='".$wallet['ref1']."';");
								}
								if($wallet_percent/8>=1 && isset($wallet['ref2']) && $wallet['ref2']>1){
									query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `percent_ref`=`percent_ref`+'".(int)($wallet_percent/8)."', `view`=IF(`view`=1,3,`view`) WHERE `wallet`='".$wallet['ref2']."';");
								}
								if($wallet_percent/16>=1 && isset($wallet['ref3']) && $wallet['ref3']>1){
									query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `percent_ref`=`percent_ref`+'".(int)($wallet_percent/16)."', `view`=IF(`view`=1,3,`view`) WHERE `wallet`='".$wallet['ref3']."';");
								}
								if(isset($recipient['wallet']) && isset($recipient_percent)){
									if($recipient_percent/4>=1 && isset($recipient['ref1']) && $recipient['ref1']>1){
										query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `percent_ref`=`percent_ref`+'".(int)($recipient_percent/4)."', `view`=IF(`view`=1,3,`view`) WHERE `wallet`='".$recipient['ref1']."';");
									}
									if($recipient_percent/8>=1 && isset($recipient['ref2']) && $recipient['ref2']>1){
										query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `percent_ref`=`percent_ref`+'".(int)($recipient_percent/8)."', `view`=IF(`view`=1,3,`view`) WHERE `wallet`='".$recipient['ref2']."';");
									}
									if($recipient_percent/16>=1 && isset($recipient['ref3']) && $recipient['ref3']>1){
										query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `percent_ref`=`percent_ref`+'".(int)($recipient_percent/16)."', `view`=IF(`view`=1,3,`view`) WHERE `wallet`='".$recipient['ref3']."';");
									}
								}
								if(!isset($noda_set))query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `percent_ref`=`percent_ref`+1 ".(mt_rand(1,100)==1?', `view`=IF(`view`=1,3,`view`)':'')." WHERE `noda`='".$sqltbl_arr['nodause']."';");
								else if($noda_set==1 && $wallet['noda'])query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `noda`='', `nodaping`='', `view`=IF(`view`=1,3,`view`) WHERE `noda`='".$wallet['noda']."' and `wallet`!='".$wallet['wallet']."';");
								query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `signpubreg`='', `signreg`='', `signpubnew`='', `signnew`='', `signpub`='', `sign`='', `checkhistory`=1, `hash`='' WHERE `wallet`= '".$sqltbl_arr['wallet']."' and `height`= '".$sqltbl_arr['height']."' and `checkhistory`=0;");
								if(mysqli_affected_rows($mysqli_connect)>=1){
									if($sqltbl_arr['nodause']==$noda_ip){
										query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` SET `nodatrue`=1, `date`='".$sqltbl_arr['date']."' WHERE `wallet`= '".$sqltbl_arr['wallet']."';");
									} else query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` SET `nodatrue`= 0, `date`='".$sqltbl_arr['date']."' WHERE `wallet`= '".$sqltbl_arr['wallet']."' and `nodatrue`!='0';");
									if(isset($type_action)){
										if($type_action==1 && isset($money_return)){
											if($money_return>=1)deals_wallet_update($sqltbl_arr['pin'],$seller_height,$money_return,$seller_nodause,$seller_nodawallet,$sqltbl_arr['date'],$sqltbl_arr['pin'],$sqltbl_arr['details']);
											query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=3 WHERE `checkhistory`=1 and `wallet`='".$sqltbl_arr['pin']."' and `date`='".$sqltbl_arr['details']."' and `recipient`=1 and `details`!='';");
										} else if($type_action==2 && isset($buyer_money) && isset($seller_money)){
											deals_wallet_update($seller_wallet,$seller_height,$seller_money,$seller_nodause,$seller_nodawallet,$sqltbl_arr['date'],$seller_wallet,$seller_date);
											deals_wallet_update($buyer_wallet,$buyer_height,$buyer_money,$buyer_nodause,$buyer_nodawallet,$sqltbl_arr['date'],$seller_wallet,$seller_date);
											if(isset($arbitr_money))deals_wallet_update($arbitr_wallet,$arbitr_height,$arbitr_money,$arbitr_nodause,$arbitr_nodawallet,$sqltbl_arr['date'],$seller_wallet,$seller_date);
											query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=4 WHERE (`checkhistory`=3 or `checkhistory`=5) and `wallet`='".$sqltbl_arr['wallet']."' and `date`='".$seller_date."' and `recipient`=1 and `details`!='';");
											query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=4 WHERE `checkhistory`>0 and `checkhistory`!=2 and `date`>'".$seller_date."' and `details` LIKE '%|".$seller_date."%' and `recipient`=1;");
										} else if($type_action==4 && isset($arbitr_checkhistory) && isset($seller_wallet)){
											query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=5 WHERE `checkhistory`=3 and `wallet`='".$seller_wallet."' and `date`='".$seller_date."' and `recipient`=1 and `details`!='';");
											query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=5 WHERE `checkhistory`>0 and `checkhistory`!=2 and `date`>'".$seller_date."' and `details` LIKE '%|".$seller_date."%' and `recipient`=1;");
										} else if($type_action==5 && isset($deals_close_true))deals_close($deals_close_true,1);
										else if($type_action==7 && isset($deals_cancel_true))deals_cancel($deals_cancel_true,1);
									}
								}
								unset($noda_set);
							}
							$sqltbl_arr['checkhistory']= 1;
						}
					}
				}
			}
			if($wallet_update==1){
				if(isset($wallet['wallet']) && isset($wallet['ref1']) && isset($wallet['ref2']) && isset($wallet['ref3']) && isset($wallet_percent) && $wallet_percent/4>=1 && $wallet['ref1']>1){
					query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals` WHERE `wallet`= '".$sqltbl_arr['wallet']."' and `height`> '".$sqltbl_arr['height']."';");
					query_bd("REPLACE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals` SET `wallet`= '".$sqltbl_arr['wallet']."',`ref1`= '".$wallet['ref1']."',`ref2`= '".$wallet['ref2']."',`ref3`= '".$wallet['ref3']."',`money1`= '".($wallet['ref1']>1?(int)($wallet_percent/4):'0')."',`money2`= '".($wallet['ref2']>1?(int)($wallet_percent/8):'0')."',`money3`= '".($wallet['ref3']>1?(int)($wallet_percent/16):'0')."',`height` = '".$sqltbl_arr['height']."',`date` = '".$sqltbl_arr['date']."';");
				}
				if(isset($recipient['wallet']) && isset($recipient['ref1']) && isset($recipient['ref2']) && isset($recipient['ref3']) && isset($recipient_percent) && $recipient_percent/4>=1 && $recipient['ref1']>1){
					query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals` WHERE `wallet`= '".$sqltbl_arr['recipient']."' and `height`> '".$recipient['height']."';");
					query_bd("REPLACE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals` SET `wallet`= '".$sqltbl_arr['recipient']."',`ref1`= '".$recipient['ref1']."',`ref2`= '".$recipient['ref2']."',`ref3`= '".$recipient['ref3']."',`money1`= '".($recipient['ref1']>1?(int)($recipient_percent/4):'0')."',`money2`= '".($recipient['ref2']>1?(int)($recipient_percent/8):'0')."',`money3`= '".($recipient['ref3']>1?(int)($recipient_percent/16):'0')."',`height` = '".$recipient['height']."',`date` = '".$sqltbl_arr['date']."';");
				}
			}
			query_bd("UPDATE IGNORE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkhistory`=2 WHERE `checkhistory`= 0 and `wallet`= '".$sqltbl_arr['wallet']."' and `height`= '".$sqltbl_arr['height']."' and `date`<".($GLOBALS['date_synch']-60).";");
			query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `checkhistory`= 0 and `wallet`= '".$sqltbl_arr['wallet']."' and `height`= '".$sqltbl_arr['height']."' and `date`< ".($GLOBALS['date_synch']-60).";");
			unset($history_delete);
			unset($type_action);
			unset($details_arr);
			unset($wallet);
			unset($recipient);
			unset($money_seller_max);
			unset($money_seller_min);
			unset($money_buyer);
			unset($money_return);
			unset($buyer_wallet);
			unset($buyer_height);
			unset($buyer_date);
			unset($buyer_nodause);
			unset($buyer_nodawallet);
			unset($buyer_money);
			unset($buyer_details);
			unset($seller_wallet);
			unset($seller_height);
			unset($seller_date);
			unset($seller_nodause);
			unset($seller_nodawallet);
			unset($seller_money);
			unset($arbitr_wallet);
			unset($arbitr_height);
			unset($arbitr_date);
			unset($arbitr_nodause);
			unset($arbitr_nodawallet);
			unset($arbitr_money);
			unset($arbitr_checkhistory);
			unset($deals_close_true);
			unset($deals_cancel_true);
    }
  }
  function send($request,$wallet){
    global $json_arr,$sqltbl,$mysqli_connect,$noda_wallet,$noda_ip,$host_ip;
		delay_now();
    $stop=0;
    unset($json_arr['walletnew']);
    unset($json_arr['recipient']);
    unset($json_arr['height']);
    unset($json_arr['transaction']);
    unset($json_arr['wallet']);
    unset($json_arr['balance']);
    unset($json_arr['send']);
		if(isset($request['date']) && ($request['date']<time()- 60 || $request['date']>time()))$stop=1;
		else if(isset($request['money']) && strlen($request['money'])<13 && (!isset($request['signpubreg']) || $request['money']==3) && isset($request['sign']) && isset($request['signpub']) && strlen($request['wallet'])==18){
			if(isset($request['signpubreg']) && $request['signpubreg']){
				query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `signpub`= '".sha_dec($request['signpubreg'])."' LIMIT 1;");
				if(!isset($sqltbl['height'])){
					query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `checkhistory`= 0 and `signpubreg`= '".$request['signpubreg']."' LIMIT 1;");
					if(isset($sqltbl['height'])){
						$json_arr['wallet_reg']= 'false';
						$stop=1;
					}
				} else {
					$json_arr['wallet_reg']= 'false';
					$stop=1;
				}
			}
			if($stop!=1){
				unset($walletnew);
				if($json_arr['send_noda']==1){
					$nodawallet= $request['nodawallet'];
					$nodause= $request['nodause'];
					$nodaown= (isset($request['nodaown']) && $request['nodaown']==1?1:0);
					$datecheck= (int)$request['date'];
				} else {
					$nodawallet= $noda_wallet;
					$nodause= $noda_ip;
					$datecheck= $json_arr['time'];
				}
			}
			if($stop!=1 && (!isset($wallet['balance']) || !isset($wallet['height']) || !isset($wallet['date']))){
				query_bd("SELECT * FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`= '".$request['wallet']."' and `signpub`= '".sha_dec($request['signpub'])."' and `view`>0 LIMIT 1;");
				if(isset($sqltbl['wallet']))$wallet= $sqltbl;
				else $stop=1;
			}
			if($stop!=1){
				query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`= '".$request['wallet']."' and `height`= '".$request['height']."' and `sign`= '".$request['sign']."' LIMIT 1;");
				if(isset($sqltbl['height'])){
					$json_arr['transaction']= 'double';
					$stop=1;
				} else if(!isset($request['height']) || $wallet['height']> $request['height']){
					$json_arr['height']= 'false';
					$stop=1;
				} else if($wallet['height']==$request['height']){
					$json_arr['height']= 'double';
					$stop=1;
				} else if($wallet['date']>$datecheck){
					$json_arr['date']= 'error';
					$stop=1;
				}
			}
			if($stop!=1 && $request['recipient']==1 && $request['money']==1 && strlen($request['pin'])==18 && isset($request['details']) && strpos($request['details'], '1|')===0 && $wallet['height']>0){
				$json_arr['walletnewbuy']= 'false';
				$stop=1;
			}
			if(!(isset($json_arr['date']) && $json_arr['date']== 'false')){
				if($stop!=1 && isset($datecheck) && isset($nodause) && isset($nodawallet) && signcheck($request['wallet'].(isset($request['signpubreg']) && isset($request['signreg'])?'0':$request['recipient']).$request['money'].$request['pin'].$request['height'].$nodause.(isset($request['details'])?$request['details']:'').(isset($request['signpubreg']) && isset($request['signreg'])?$request['signpubreg'].$request['signreg']:'').(isset($request['signpubnew']) && isset($request['signnew'])?$request['signpubnew'].$request['signnew']:''),$request['signpub'],$request['sign'])==1){
					if($json_arr['send_noda']!=1 && $wallet['view']!=1 && $wallet['view']!=3){
						$json_arr['wallet']= 'unavailable';
						$stop=1;
					}
					if(!isset($nodaown))$nodaown= ($noda_wallet==$wallet['wallet']?1:0);
					if($stop!=1 && $request['recipient']==1 && !isset($request['details']) && $request['pin']>1 && strlen($request['pin'])!=18){
						if($request['money']==100 || $request['money']==1){
							query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `checkhistory`= 0 and `pin`='".$request['pin']."' and (`money`=1 or `money`=100) and `recipient`=1 LIMIT 1;");
							if(isset($sqltbl['height']))$stop=1;
						}
						if($stop!=1){
							if(!isset($wallet['percent_4']))$wallet= wallet($wallet,$datecheck,0,1,0);
							if($request['money']==100 && $nodaown==1 && $wallet['noda']!=$request['nodause'] && $request['pin']==preg_replace("/[^0-9]/",'',$request['nodause'])){
								if($wallet['balance']+$wallet['percent_4']-$request['money']-2>=0){
									query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`='".$request['nodause']."' LIMIT 1;");
									if(!isset($sqltbl['height'])){
										$wallet['noda']= $request['nodause'];
										$noda_set= 1;
									} else $stop= 1;
								} else {
									$json_arr['balance']= 'false';
									$stop= 1;
								}
							} else if($request['money']==1 && $wallet['noda'] && $request['pin']==preg_replace("/[^0-9]/",'',$wallet['noda'])){
								$wallet['noda']='';
								$noda_set= 2;
							} else {
								$json_arr['pin']= 'false';
								$stop= 1;
							}
						}
					} else if(strlen($request['pin'])==13){
						if(!isset($wallet['percent_4']))$wallet= wallet($wallet,$datecheck,0,1,0);
						if($wallet['balance']+$wallet['percent_4']<1000){
							$json_arr['balance_required']= "1000";
							$stop= 1;
						}
					}
					if(!(isset($noda_set) && $noda_set==1)){
						query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`='".$nodause."' LIMIT 1;");
						if(!isset($sqltbl['height']))$stop= 1;
					}
					if($stop!=1){
						if(isset($request['details']) && !$request['details']){
							$json_arr['details']= 'false';
						} else if($stop!=1){
							if($stop!=1 && isset($request['details']) && $request['details'] && isset($request['recipient']) && $request['recipient']==1){
								if(strlen($request['pin'])==18){
									query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `details`='".mysqli_real_escape_string($mysqli_connect,$request['details'])."' and `pin`='".$request['pin']."' and `recipient`=1 and (`checkhistory`=1 or `checkhistory`>2) LIMIT 1;");
									if(!isset($sqltbl['height'])){
										$details_arr= explode('|',$request['details']);
										if(is_array($details_arr) && (count($details_arr)==2 || (count($details_arr)==3 && strlen($details_arr[2])==strlen((int)$details_arr[2]) && strlen($details_arr[2])==18 && $request['wallet']!=$details_arr[2])) && strlen($details_arr[0])==strlen((int)$details_arr[0]) && $details_arr[0]>0 && strlen($details_arr[1])==strlen((int)$details_arr[1]) && strlen($details_arr[1])>0){
											$type_action= (int)$details_arr[0];
											$details_check= (int)$details_arr[1];
											if((count($details_arr)==2 && $type_action!=3 && $type_action!=6) || (count($details_arr)==3 && ($type_action==3 || $type_action==4 || ($type_action==6 && $request['pin']==$details_arr[2])))){
												if($stop!=1 && isset($details_arr[2])){
													$wallet_check= (int)$details_arr[2];
													query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`='".$wallet_check."' LIMIT 1;");
													if(!isset($sqltbl['height']))$stop=1;
												}
												if($stop!=1){
													if($type_action==1 || $type_action==5){
														if($type_action==5 && ($request['wallet']!=$request['pin'] || $request['money']!=1))$stop= 1;
														else {
															deals($request['pin'],$details_check,'','',1,0,3);
															if(isset($sqltbl['height'])){
																if($type_action==1){
																	$money_seller_max= $sqltbl['money'];
																	if($request['money']>10){
																		if($sqltbl['pin']> 100){
																			$money_seller_min= (int)(1.2*$sqltbl['pin']);
																			if($money_seller_min > $money_seller_max)$money_seller_min= $money_seller_max;
																		} else $money_seller_min= 120;
																	} else if($sqltbl['pin']<= 100 && $request['money']==1){
																		$money_seller_min= 120;
																		$money_buyer= 120;
																	} else $stop= 1;
																	if($stop!= 1){
																		if(!isset($money_buyer))$money_buyer= 12*$request['money'];
																		$money_return= $money_seller_max-ceil($money_buyer);
																		$money_buyer= (int)$money_buyer;
																		if(!($money_buyer >= 120 && $money_seller_max+9 >= $money_buyer && $money_seller_min <= $money_buyer))$stop= 1;
																	}
																}
															} else $stop=1;
														}
													} else if((($type_action==2 || $type_action==7) && $request['wallet']==$request['pin']) || ($type_action==3 && isset($wallet_check) && $wallet_check!=$request['pin']) || $type_action==4 || $type_action==6){
														deals($request['pin'],$details_check,'','',3,($type_action==2?5:0),($type_action==2 || $type_action==7?7:4));
														if(isset($sqltbl['height'])){
															if($request['money']==1 || ($type_action==4 && $request['money']>=90)){
																deals('','',$request['pin'],'1|'.$details_check,1,($type_action==2?5:0),($type_action==2?3:1));
																if(isset($sqltbl['height'])){
																	if(($type_action==3 || $type_action==6) && $sqltbl['wallet']!=$request['wallet'])$stop= 1;
																	else if($type_action==4){
																		if(($sqltbl['money']==1?90:9*$sqltbl['money'])==$request['money']){
																			if($sqltbl['wallet']==$request['wallet'] && isset($wallet_check))$history_delete= 1;
																			else {
																				if($sqltbl['wallet']!=$request['wallet']){
																					deals('','',$request['pin'],'3|'.$details_check.'|'.$request['wallet'],1,0,1);
																					if(!isset($sqltbl['height']) || !isset($wallet_check) || $sqltbl['wallet']!=$wallet_check)$stop= 1;
																				}
																			}
																		} else $stop= 1;
																	} else if($type_action==7){
																		deals('','',$request['pin'],'6|'.$details_check.'|'.$request['pin'],1,0,1);
																		if(!isset($sqltbl['height']))$stop=1;
																	}
																} else $stop=1;
															} else $stop=1;
														} else $stop=1;
													} else $stop=1;
												}
											} else $stop=1;
										} else $stop=1;
									} else {
										$json_arr['transaction']= 'double';
										$stop=1;
									}
								} else if(strlen($request['pin'])<13 && !($request['money']>=100 && ($request['money']/1.2)%10==0 && ($request['pin']>=100 || $request['pin']==0) && $request['pin']%10==0 && $request['money']>=1.2*$request['pin']))$stop=1;
								else if(($request['pin']>1000000000000 && $request['money']!=1) || ($request['pin']==1000000000000 && $request['money']!=100))$stop=1;
								else if(!($request['pin']>0))$stop=1;
							}
							if($stop!=1){
								query_bd("SELECT `wallet`,`balance` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`= '".$nodause."' ORDER BY `date` DESC LIMIT 1;");
								if(isset($sqltbl['wallet'])){
									$nodawallet=$sqltbl['wallet'];
									if($nodawallet==$wallet['wallet'])$nodaown=1;
									else $nodaown=0;
								}
								if($request['recipient']==1)$recipient['wallet']=1;
								else if(isset($request['signpubreg']) && isset($request['signreg']) && $request['money']==3 && signcheck($wallet['wallet'].'0'.'3'.$request['pin'].$request['height'].$nodause.$request['signpubreg'].$request['signreg'].(isset($request['signpubnew']) && isset($request['signnew'])?$request['signpubnew'].$request['signnew']:''),$request['signpub'],$request['sign'])==1 && signcheck('30'.sha_dec($request['signpubreg']),$request['signpubreg'],$request['signreg'])==1){
									if($request['recipient']==0){
										function genwallet(){
											global $sqltbl,$mysqli_connect;
											$wallet_temp= (string)mt_rand(100000000000000001,999999999999999999);
											if($wallet_temp && strlen((int)$wallet_temp)==18)query_bd("SELECT `wallet` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`= '".$wallet_temp."' LIMIT 1;");
											else genwallet();
											if(isset($sqltbl['wallet']))genwallet();
											else {
												query_bd("SELECT `wallet` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`= '".$wallet_temp."' or `recipient`= '".$wallet_temp."' LIMIT 1;");
												if(isset($sqltbl['wallet']))genwallet();
												else return $wallet_temp;
											}
										}
										$walletnew= genwallet();
										for($r=0;!$walletnew && $r<=10;$r++)$walletnew= genwallet();
										if($walletnew)$recipient['wallet']= $walletnew;
										else $json_arr['walletnew']= "false";
									} else if(strlen($request['recipient'])==18){
									query_bd("SELECT `wallet` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`= '".$request['recipient']."' LIMIT 1;");
									if(isset($sqltbl['wallet']))$json_arr['walletnew']= "false";
									else {
										query_bd("SELECT `wallet` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`= '".$request['recipient']."' or `recipient`= '".$request['recipient']."' LIMIT 1;");
										if(isset($sqltbl['wallet']))$json_arr['walletnew']= "false";
										else {
										$walletnew= $request['recipient'];
										$recipient['wallet']= $walletnew;
										}
									}
									} else $json_arr['walletnew']= "false";
								} else if($request['recipient']>1 && strlen($request['recipient'])==18){
									query_bd("SELECT * FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`= '".$request['recipient']."' and `view`>0 LIMIT 1;");
									if(isset($sqltbl['wallet']))$recipient= $sqltbl;
								}
								if(!isset($recipient['wallet']))$json_arr['recipient']= 'false';
								else if($recipient['wallet']>0){
									if($wallet['height']==$request['height']-1){
									if($wallet['signpub']==sha_dec($request['signpub']))$checkhistory= 0;
									else $checkhistory= -1;
									} else if($wallet['height']<$request['height']-1 && $wallet['signpub']==sha_dec($request['signpub'])){
									query_bd("SELECT `nodause`, `height`, `signpubnew`, `signnew`, `signpub`, `checkhistory` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`= '".$request['wallet']."' and `height`= '".($request['height']-1)."' and `signpubnew`='' and `signpub`='".$request['signpub']."' and `checkhistory`!= 2 ORDER BY `date` ASC LIMIT 1;");
									if(isset($sqltbl['height']))$checkhistory= 0;
									else $checkhistory= -2;
									} else $checkhistory= -3;
									if($checkhistory==0){
										query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `wallet`= '".$request['wallet']."', `height`= '".$request['height']."', `date`= '".$datecheck."', `pin`= '".$request['pin']."', `nodause`= '".$nodause."', `recipient`= '".$recipient['wallet']."', `money`= '".$request['money']."', `nodawallet`= '".$nodawallet."', `nodaown`= '".$nodaown."', `details`= '".(isset($request['details'])?mysqli_real_escape_string($mysqli_connect,$request['details']):'')."', `signpubreg`= '".(isset($request['signpubreg']) && isset($request['signreg'])?$request['signpubreg']:'')."', `signreg`= '".(isset($request['signpubreg']) && isset($request['signreg'])?$request['signreg']:'')."', `signpubnew`= '".(isset($request['signpubnew']) && isset($request['signnew'])?$request['signpubnew']:'')."', `signnew`= '".(isset($request['signpubnew']) && isset($request['signnew'])?$request['signnew']:'')."', `signpub`= '".$request['signpub']."', `sign`= '".$request['sign']."', `hash`= '".sha_dec($request['wallet'].$recipient['wallet'].$request['money'].$request['pin'].$request['height'].$nodawallet.$nodause.$nodaown.(isset($request['details'])?$request['details']:'').$datecheck.(isset($request['signpubreg']) && isset($request['signreg'])?$request['signpubreg'].$request['signreg']:'').(isset($request['signpubnew']) && isset($request['signnew'])?$request['signpubnew'].$request['signnew']:'').$request['signpub'].$request['sign'])."', `checkhistory`= '".$checkhistory."';");
										if(mysqli_affected_rows($mysqli_connect)>=1){
											delay_now();
											if(isset($GLOBALS['host_ip_check_file']) && file_exists($GLOBALS['host_ip_check_file']))@unlink($GLOBALS['host_ip_check_file']);
											if($checkhistory!=0)$stop= 1;
											else {
												if($json_arr['send_noda']!=1){
													$json_arr['date']= $datecheck;
													$json_arr['wallet']= $request['wallet'];
													$json_arr['recipient']= $request['recipient'];
													$json_arr['height']= $request['height'];
													$json_arr['pin']= $request['pin'];
													if(isset($request['details']))$json_arr['details']= $request['details'];
													$json_arr['money']= $request['money']+2;
													if(isset($wallet['balance']) && isset($wallet['percent_4']))$json_arr['balance']= $wallet['balance']+$wallet['percent_4']-$request['money']-2;
												}
												query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet` = '".$request['wallet']."' and `height`>='".$request['height']."' and `checkhistory`!=0;");
												$json_arr['send']= 'true';
											}
											delay_now();
											if($recipient['wallet']>1)$json_arr['recipient']= gold_wallet_view($recipient['wallet']);
											if(isset($walletnew))$json_arr['walletnew']= gold_wallet_view($walletnew);
											$nodas_synch= [];
											$result= mysqli_query_bd("SELECT `noda` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`!='' and `noda`!='".$host_ip."' and `noda`<'".$noda_ip."' and `balance`>=100 and `view`>0 ORDER BY `noda` DESC LIMIT 10;");
											while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))$nodas_synch[$sqltbl_arr['noda']]= 1;
											$result= mysqli_query_bd("SELECT `noda` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`!='' and `noda`!='".$host_ip."' and `noda`>'".$noda_ip."' and `balance`>=100 and `view`>0 ORDER BY `noda` ASC LIMIT 10;");
											while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))$nodas_synch[$sqltbl_arr['noda']]= 1;
											query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`!='' LIMIT 1;");
											if((isset($nodas_synch) && count($nodas_synch)>1) || (isset($sqltbl['count']) && $sqltbl['count']>0)){
												if(isset($sqltbl['count']) && $sqltbl['count']-count($nodas_synch)>=10){
													$noda_count= $sqltbl['count'];
													for($i=0;$i<3 && $sqltbl['count']-count($nodas_synch)>=5;$i++){
														$noda_rand= mt_rand(0,$noda_count-4);
														$result= mysqli_query_bd("SELECT `noda` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`!='' and `noda` NOT IN ('".implode("','",array_keys($nodas_synch))."','".$host_ip."','".$noda_ip."') and `balance`>=10000 and `view`>0 and (`checknoda`='' or `checknoda`<= ".$GLOBALS['date_synch'].") LIMIT ".$noda_rand.", 3;");
														while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))$nodas_synch[$sqltbl_arr['noda']]= 1;
													}
												}
												if(isset($nodas_synch) && count($nodas_synch)>=1){
													unset($request['password']);
													$request['nodawallet']=$nodawallet;
													$request['nodause']=$nodause;
													$request['nodaown']=$nodaown;
													$request['date']=$datecheck;
													if(isset($recipient['wallet']))$request['recipient']= $recipient['wallet'];
													$request_post= '';
													$patterns[0] = '/\+/';
													$patterns[1] = '/\*/';
													$patterns[2] = '/\$/';
													$patterns[3] = '/\&/';
													$replacements[0] = '✚';
													$replacements[1] = '✖';
													$replacements[2] = '^';
													$replacements[3] = '◆';
													foreach($request as $key => $value)$request_post.= ($request_post?'&':'').$key.'='.($key=='details'?preg_replace($patterns,$replacements,$value):$value);
													if($request_post)foreach($nodas_synch as $key => $value)exec('wget -O/dev/null -bqc --timeout=45 --post-data "'.$request_post.'" "http://'.$key.'/egold.php"');
												}
											}
										} else $json_arr['transaction']= 'double';
									} else $json_arr['wallet']= 'false';
								} else {
									if(isset($GLOBALS['host_ip_check_file']) && isset($GLOBALS['host_ip_check_file_temp']) && isset($GLOBALS['host_ip_check'])){
										$GLOBALS['host_ip_check_file_block']= $GLOBALS['host_ip_check_file_temp'].($GLOBALS['host_ip_check']+60);
										@rename($GLOBALS['host_ip_check_file'], $GLOBALS['host_ip_check_file_block']);
									}
									$json_arr['sign']= 'false';
								}
								delay_now();
							}
						}
					}
				} else if(!isset($json_arr['date']) || $json_arr['date']!= 'false')$json_arr['wallet']= 'false';
			}
		}
		if($stop==1)$json_arr['send']= 'false';
	}
	function transaction_check($host_ip,$post,$wallet,$request,$limit_in,$type_transaction_check){
		global $json_arr,$noda_balance,$sqltbl,$mysqli_connect;
		$wallet_temp= $wallet;
		$json= connect_noda_multi($host_ip,'',$post,10,0);
		if(is_array($json)){
			foreach($json as $key1 => $value1){
				if(is_array($value1)){
					$limit_in_stop= 0;
					$value1= array_reverse($value1);
					foreach($value1 as $key2 => $value2){
						if($limit_in_stop<$limit_in)$limit_in_stop++;else break;
						if(
							isset($value2['wallet']) && $value2['wallet']== preg_replace("/[^0-9]/",'',$value2['wallet']) && ($value2['wallet']==1 || strlen($value2['wallet'])==18) &&
							isset($value2['height']) && $value2['height']== preg_replace("/[^0-9]/",'',$value2['height']) && strlen($value2['height'])<=18 &&
							isset($value2['date']) && $value2['date']== preg_replace("/[^0-9]/",'',$value2['date']) && strlen($value2['date'])<=20 && $value2['date']> $GLOBALS['date_synch']-$GLOBALS['history_day_sec'] && $value2['date']<= time()){
							if(
								($type_transaction_check==3 || ((($type_transaction_check==1 && count($value2)==17) || ($type_transaction_check==2 && count($value2)==11)) &&
									isset($value2['recipient']) && $value2['recipient']== preg_replace("/[^0-9]/",'',$value2['recipient']) && ($value2['recipient']==1 || strlen($value2['recipient'])==18) &&
									isset($value2['money']) && $value2['money']== preg_replace("/[^0-9]/",'',$value2['money']) && strlen($value2['money'])<=18 &&
									isset($value2['pin']) && $value2['pin']== preg_replace("/[^0-9]/",'',$value2['pin']) && strlen($value2['pin'])<=18 &&
									isset($value2['nodawallet']) && $value2['nodawallet']== preg_replace("/[^0-9]/",'',$value2['nodawallet']) && (strlen($value2['nodawallet'])==18 || ($value2['nodawallet']==0 && $value2['date']<= 1628662141)) &&
									isset($value2['nodause']) && $value2['nodause']== preg_replace("/[^0-9\.]/",'',$value2['nodause']) && strlen($value2['nodause'])<=40 &&
									isset($value2['nodaown']) && $value2['nodaown']== preg_replace("/[^0-9]/",'',$value2['nodaown']) && strlen($value2['nodaown'])==1 &&
									($type_transaction_check==2 || (
										isset($value2['signpubreg']) && $value2['signpubreg']== preg_replace("/[^0-9a-z]/",'',$value2['signpubreg']) && strlen($value2['signpubreg'])<=514 &&
										isset($value2['signreg']) && $value2['signreg']== preg_replace("/[^0-9a-z:]/",'',$value2['signreg']) && strlen($value2['signreg'])<=1440 &&
										isset($value2['signpubnew']) && $value2['signpubnew']== preg_replace("/[^0-9]/",'',$value2['signpubnew']) && strlen($value2['signpubnew'])<=19 &&
										isset($value2['signnew']) && $value2['signnew']== preg_replace("/[^0-9a-z:]/",'',$value2['signnew']) && strlen($value2['signnew'])<=1440 &&
										isset($value2['signpub']) && $value2['signpub']== preg_replace("/[^0-9a-z]/",'',$value2['signpub']) && strlen($value2['signpub'])==514 &&
										isset($value2['sign']) && $value2['sign']== preg_replace("/[^0-9a-z:]/",'',$value2['sign']) && strlen($value2['sign'])<=1440
									)) &&
									isset($value2['details']) && $value2['details']== preg_replace("/[^0-9a-zA-Z\-\@\.\,\:\;\!\#\$\*\+\=\?\&\_\{\|\}\~\(\)\/\' ]/",'',$value2['details']) && strlen($value2['details'])<=250 && ($value2['wallet']==1 || $value2['details']=='' || $value2['date']>= $GLOBALS['date_synch']-7*86400-7320) &&
									isset($value2['checkhistory']) && $value2['checkhistory']== preg_replace("/[^0-9]/",'',$value2['checkhistory']) && strlen($value2['checkhistory'])==1 && $value2['checkhistory']!=2 &&
									!($value2['wallet']!=1 && $value2['recipient']!=1 && $value2['height']<=1 && $value2['date']> 1631774151)
								)) &&
								($type_transaction_check!=3 || (
									count($value2)==9 &&
									isset($value2['ref1']) && $value2['ref1']== preg_replace("/[^0-9]/",'',$value2['ref1']) && ($value2['ref1']==0 || strlen($value2['ref1'])==18) &&
									isset($value2['ref2']) && $value2['ref2']== preg_replace("/[^0-9]/",'',$value2['ref2']) && ($value2['ref2']==0 || strlen($value2['ref2'])==18) &&
									isset($value2['ref3']) && $value2['ref3']== preg_replace("/[^0-9]/",'',$value2['ref3']) && ($value2['ref3']==0 || strlen($value2['ref3'])==18) &&
									isset($value2['money1']) && $value2['money1']== preg_replace("/[^0-9]/",'',$value2['money1']) && strlen($value2['money1'])<=18 &&
									isset($value2['money2']) && $value2['money2']== preg_replace("/[^0-9]/",'',$value2['money2']) && strlen($value2['money2'])<=18 &&
									isset($value2['money3']) && $value2['money3']== preg_replace("/[^0-9]/",'',$value2['money3']) && strlen($value2['money3'])<=18
								))
							){
								if($type_transaction_check!=1 && $value2['wallet']!=1){
									$transaction_check1[$value2['wallet'].'_'.$value2['height']][$key1]=$value2;
								} else {
									if(!isset($transaction) || !in_array($value2,$transaction))$transaction[]=$value2;
									if($type_transaction_check!=1)$transaction_check2[]=$value2;
								}
							}
						}
					}
				}
			}
		}
		if($type_transaction_check!=1 && (isset($transaction) || isset($transaction_check1))){
			$noda_count= count($host_ip);
			if(isset($transaction) && isset($transaction_check2)){
				foreach($transaction as $key => $value){
					for($i=$noda_count;$i>1;$i--){
						$key_search= array_search($value,$transaction_check2);
						if($key_search!== false){
							unset($transaction_check2[$key_search]);
						} else {
							unset($transaction[array_search($value,$transaction)]);
							break;
						}
					}
				}
			}
			if(isset($transaction_check1)){
				foreach($transaction_check1 as $key1 => $value1){
					if(count($transaction_check1[$key1])>=$noda_count-1){
						foreach($value1 as $key2 => $value2){
							$count_repeat_value2= 1;
							foreach($value1 as $key3 => $value3)if($key2!=$key3 && $value2==$value3)$count_repeat_value2++;
							if($count_repeat_value2>=$noda_count-1 && (!isset($transaction) || !in_array($value2,$transaction)))$transaction[]=$value2;
						}
					}
				}
			}
		}
		if(isset($transaction) && count($transaction)>0){
			if($type_transaction_check==1){
				foreach($transaction as $key => $value){
					if(($wallet=='' && $request=='') || (isset($value['wallet']) && $wallet['wallet']==$value['wallet'])){
						$send_arr['wallet']=$value['wallet'];
						$send_arr['recipient']=$value['recipient'];
						$send_arr['money']=$value['money'];
						$send_arr['pin']=$value['pin'];
						$send_arr['height']=$value['height'];
						$send_arr['nodawallet']=$value['nodawallet'];
						$send_arr['nodause']=$value['nodause'];
						$send_arr['nodaown']=$value['nodaown'];
						if(isset($value['details']) && $value['details'])$send_arr['details']=$value['details'];
						if(isset($value['signpubreg']) && $value['signpubreg'])$send_arr['signpubreg']=$value['signpubreg'];
						if(isset($value['signreg']) && $value['signreg'])$send_arr['signreg']=$value['signreg'];
						if(isset($value['signpubnew']) && $value['signpubnew'])$send_arr['signpubnew']=$value['signpubnew'];
						if(isset($value['signnew']) && $value['signnew'])$send_arr['signnew']=$value['signnew'];
						$send_arr['signpub']=$value['signpub'];
						$send_arr['sign']=$value['sign'];
						$send_arr['date']=$value['date'];
						send($send_arr,'');
						if(isset($json_arr['send']) && isset($wallet['height'])){
						$wallet['height']++;
						$wallet['date']=$value['date'];
						if($send_arr['nodaown']==1)$wallet['noda']=$value['nodause'];
						else $wallet['noda']='';
						$wallet['nodause']=$value['nodause'];
						$wallet['signpubnew']=$value['signpubnew'];
						$wallet['signnew']=$value['signnew'];
						$wallet['signpub']=$value['signpub'];
						$wallet['sign']=$value['sign'];
						}
						unset($send_arr);
						delay_now();
					}
				}
			} else if($type_transaction_check==2){
				foreach($transaction as $key => $value){
					if($value['wallet']!=1 && isset($value['details']) && $value['details'] && strlen($value['pin'])==18){
						$history_delete= 0;
						query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `details`='".mysqli_real_escape_string($mysqli_connect,$value['details'])."' and `pin`='".$value['pin']."' and `recipient`=1 and (`checkhistory`=1 or `checkhistory`>2) LIMIT 1;");
						if(isset($sqltbl['height']))$history_delete= 1;
						else {
							$details_arr= explode('|',$value['details']);
							if(is_array($details_arr) && (count($details_arr)==2 || (count($details_arr)==3 && strlen($details_arr[2])==strlen((int)$details_arr[2]) && strlen($details_arr[2])==18 && $value['wallet']!=$details_arr[2])) && strlen($details_arr[0])==strlen((int)$details_arr[0]) && $details_arr[0]>0 && strlen($details_arr[1])==strlen((int)$details_arr[1]) && strlen($details_arr[1])>0){
								$type_action= (int)$details_arr[0];
								$type_action_date= (int)$details_arr[1];
								if((count($details_arr)==2 && $type_action!=3 && $type_action!=6) || (count($details_arr)==3 && ($type_action==3 || $type_action==4 || ($type_action==6 && $value['pin']==$details_arr[2])))){
									query_bd("SELECT `wallet`,`height`,`date`,`money`,`nodawallet`,`nodause`,`pin`,`details`,`checkhistory` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `date`='".$type_action_date."' and `wallet`='".$value['pin']."' and LENGTH(`pin`)< 13 and `checkhistory`>1 and `recipient`=1 ORDER BY `date` LIMIT 1;");
									if(isset($sqltbl['height'])){
										if($type_action==4 && ($sqltbl['checkhistory']<4 || $value['money']>0.9*$sqltbl['money']/1.2 || $value['money']<0.9*$sqltbl['pin']))$history_delete= 1;
										else if($type_action==1 && $sqltbl['wallet']!=$value['wallet']){
											$money_seller_max= $sqltbl['money'];
											if($value['money']>10){
												if($sqltbl['pin']> 100){
													$money_seller_min= (int)(1.2*$sqltbl['pin']);
													if($money_seller_min > $money_seller_max)$money_seller_min= $money_seller_max;
												} else $money_seller_min= 120;
											} else if($sqltbl['pin']<= 100 && $value['money']==1){
												$money_seller_min= 120;
												$money_buyer= 120;
											} else $history_delete= 1;
											if($history_delete!= 1){
												if(!isset($money_buyer))$money_buyer= 12*$value['money'];
												$money_return= $money_seller_max-ceil($money_buyer);
												$money_buyer= (int)$money_buyer;
												if(!($money_buyer >= 120 && $money_seller_max+9 >= $money_buyer && $money_seller_min <= $money_buyer))$history_delete= 1;
											}
										}
									} else $history_delete= 1;
								} else $history_delete= 1;
							} else $history_delete= 1;
						}
						if($history_delete== 1)unset($transaction[$key]);
					}
				}
				if(isset($transaction) && count($transaction)>0){
					foreach($transaction as $key => $value){
						if($value['wallet']!=1)query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`='".$value['wallet']."' and `height`='".$value['height']."' and `checkhistory`=2;");
						query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `wallet`='".$value['wallet']."', `recipient`='".$value['recipient']."', `money`='".$value['money']."', `pin`='".$value['pin']."', `height`='".$value['height']."', `nodawallet`='".$value['nodawallet']."', `nodause`='".$value['nodause']."', `nodaown`='".$value['nodaown']."', `details`='".mysqli_real_escape_string($mysqli_connect,$value['details'])."', `date`='".$value['date']."', `checkhistory`='".($value['wallet']!=1?$value['checkhistory']:1)."';");
					}
					query_bd("SELECT `date` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `date`<=".$post['dateto']." ORDER BY `date` DESC LIMIT ".(int)($limit_in/2).",1;");
					if(isset($sqltbl['date']) && $sqltbl['date']>0 && $sqltbl['date']>= $GLOBALS['date_synch']-7*86400){
						query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` SET `value`= '".($sqltbl['date']-1)."' WHERE `name`= 'synch_history';");
					} else query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` SET `value`= ".$GLOBALS['date_synch']." WHERE `name`= 'synch_history';");
				}
			} else if($type_transaction_check==3){
				foreach($transaction as $key => $value){
					query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals` SET `wallet`='".$value['wallet']."', `ref1`='".$value['ref1']."', `ref2`='".$value['ref2']."', `ref3`='".$value['ref3']."', `money1`='".$value['money1']."', `money2`='".$value['money2']."', `money3`='".$value['money3']."', `height`='".$value['height']."', `date`='".$value['date']."';");
				}
			}
			$json_arr['transaction_check']=count($transaction);
		} else $json_arr['transaction_check']=0;
		if($type_transaction_check==1){
			if(isset($json_arr['send']) && isset($wallet['height'])){
				return $wallet;
			} else {
				return $wallet_temp;
			}
		}
  }
  function history_synch(){
    global $json_arr,$sqltbl,$mysqli_connect,$noda_ip,$noda_balance,$limit_synch;
		delay_now();
    if(isset($noda_balance) && count($noda_balance)>=1){
			$post_history['type']= 'history';
			$post_history['order']= 'DESC';
			$post_history['all']= 1;
			$noda_history= random($noda_balance,3);
			transaction_check(array_keys($noda_history),$post_history,'','',$limit_synch,1);
			unset($json_arr['walletnew']);
			unset($json_arr['recipient']);
			unset($json_arr['height']);
			unset($json_arr['transaction']);
			unset($json_arr['wallet']);
			unset($json_arr['balance']);
			unset($json_arr['send']);
    }
  }
  function history_synch_old(){
		global $json_arr,$sqltbl,$mysqli_connect,$noda_ip,$noda_balance,$noda_balance_history_synch,$limit_synch;
		delay_now();
		query_bd("SELECT `value` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` WHERE `name`='synch_history' LIMIT 1;");
		if(isset($sqltbl['value'])){
			if($sqltbl['value']<$GLOBALS['date_synch']-7*86400 || $sqltbl['value']>$GLOBALS['date_synch'])$synch_history= $GLOBALS['date_synch'];
			else $synch_history= $sqltbl['value'];
		} else $synch_history= $GLOBALS['date_synch'];
		if(isset($noda_balance_history_synch) && count($noda_balance_history_synch)>=4){
			$post_history['type']= 'history';
			$post_history['date']= $GLOBALS['date_synch']-7*86400;
			$post_history['dateto']= $synch_history;
			$post_history['order']= 'DESC';
			$post_history['all']= 5;
			$noda_history= random($noda_balance_history_synch,5);
			transaction_check($noda_history,$post_history,'','',$limit_synch,2);
			$post_history['type']= 'referrals';
			$post_history['date']= $GLOBALS['date_synch']-7*86400;
			$post_history['dateto']= $synch_history;
			$post_history['order']= 'DESC';
			$post_history['limit']= $limit_synch;
			$post_history['all']= 1;
			$noda_history= random($noda_balance_history_synch,5);
			transaction_check($noda_history,$post_history,'','',$limit_synch,3);
			query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` SET `value`= ".$GLOBALS['date_synch']." WHERE `name`= 'synch_history' and `value`= ".$synch_history.";");
		}
  }
  function delete_wallet_history($wallet,$height,$nodause,$ref1,$ref2,$ref3){
		global $sqltbl;
		query_bd("SELECT `date` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`= '".$wallet."' and `height`>= '".$height."' ORDER BY `date` ASC LIMIT 1;");
		if(isset($sqltbl['date'])){
			query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `date`>".$sqltbl['date']." and `wallet`= 1 and `details` like '".$wallet."%';");
		}
		query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`= '".$wallet."' and `height`>= '".$height."';");
		query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals` WHERE `wallet`= '".$wallet."' and `height`>= '".$height."';");
		if($nodause)query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `view`=3 WHERE `noda`= '".$nodause."' and `view`=1;");
		if($ref1>0)query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `view`=3 WHERE `wallet`= '".$ref1."' and `view`=1;");
		if($ref2>0)query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `view`=3 WHERE `wallet`= '".$ref2."' and `view`=1;");
		if($ref3>0)query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `view`=3 WHERE `wallet`= '".$ref3."' and `view`=1;");
  }
  function wallet_synch(){
    global $stop,$json_arr,$sqltbl,$mysqli_connect,$noda_ip,$checkbalancenodatime,$noda_balance,$noda_balance_noda_ip,$limit_synch,$noda_trust,$nodas_balance_sum_bd,$checkwallets,$wallet_check_del,$synch_wallet;
		delay_now();
		$post_synchwallets= [];
		if((!isset($noda_balance) || count($noda_balance)<3) && isset($noda_trust) && count($noda_trust)>=1){
      $post_synchwallets['wallets_with_noda_first']= 1;
      foreach($noda_trust as $key => $value)if(!isset($noda_balance[$value]))$noda_balance[$value]= 1;
    }
    if($stop!=1 && isset($noda_balance) && count($noda_balance)>=3){
			$nodas_balance_sum=0;
			$limit=(int)($limit_synch*2/6);
			$limit_tmp=0;
			$count_synch=0;
			$wallets_bd= [];
			if(isset($wallet_check_del) && count($wallet_check_del)>0)$wallets_bd= $wallet_check_del;
			$result= mysqli_query_bd("SELECT `wallet`, `ref1`, `ref2`, `ref3`, `noda`, `nodause`, `balance`, `date`, `percent_ref`, `height`, `signpub`, `view`, `checkbalanceall` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `view`!=1 and `view`!=3 and `date`<= ".$GLOBALS['date_synch']." and (`checkwallet`='' or `checkwallet`<'".($checkbalancenodatime>0?$checkbalancenodatime:1)."') ".(count($wallets_bd)>0?"and `wallet` NOT IN ('".implode("','",array_keys($wallets_bd))."')":'')." ORDER BY `noda`!='' DESC, `view`= 0,`view`=2, `checkwallet` LIMIT ".$limit.";");
			while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
				if($sqltbl_arr['signpub'])$wallets_bd[$sqltbl_arr['wallet']]= $sqltbl_arr;
				else $wallets_bd[$sqltbl_arr['wallet']]= 1;
				$limit_tmp++;
				if($sqltbl_arr['checkbalanceall']=='' && $sqltbl_arr['view']==2){
					if($sqltbl_arr['ref1']>0 && !isset($wallets_bd[$sqltbl_arr['ref1']])){
						query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `view`=3 WHERE `wallet`= '".$sqltbl_arr['ref1']."' and `view`=1;");
						if(!(mysqli_affected_rows($mysqli_connect)>=1)){
							query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`= '".$sqltbl_arr['ref1']."' LIMIT 1;");
							if(!isset($sqltbl['height'])){
								$wallets_bd[$sqltbl_arr['ref1']]= 1;
								$limit_tmp++;
							}
						}
					}
					if($sqltbl_arr['ref2']>0 && !isset($wallets_bd[$sqltbl_arr['ref2']])){
						query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `view`=3 WHERE `wallet`= '".$sqltbl_arr['ref2']."' and `view`=1;");
						if(!(mysqli_affected_rows($mysqli_connect)>=1)){
							query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`= '".$sqltbl_arr['ref2']."' LIMIT 1;");
							if(!isset($sqltbl['height'])){
								$wallets_bd[$sqltbl_arr['ref2']]= 1;
								$limit_tmp++;
							}
						}
					}
					if($sqltbl_arr['ref3']>0 && !isset($wallets_bd[$sqltbl_arr['ref3']])){
						query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `view`=3 WHERE `wallet`= '".$sqltbl_arr['ref3']."' and `view`=1;");
						if(!(mysqli_affected_rows($mysqli_connect)>=1)){
							query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`= '".$sqltbl_arr['ref3']."' LIMIT 1;");
							if(!isset($sqltbl['height'])){
								$wallets_bd[$sqltbl_arr['ref3']]= 1;
								$limit_tmp++;
							}
						}
					}
				}
				unset($wallets_bd[$sqltbl_arr['wallet']]['checkbalanceall']);
			}
			query_bd("SELECT `value` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` WHERE `name`='synch_wallet_last_history' LIMIT 1;");
			if(isset($sqltbl['value']) && strlen($sqltbl['value'])==18 && !isset($wallets_bd[$sqltbl['value']])){
				$wallet_last= $sqltbl['value'];
				query_bd("SELECT `wallet`, `ref1`, `ref2`, `ref3`, `noda`, `nodause`, `balance`, `date`, `percent_ref`, `height`, `signpub`, `view` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`= '".$wallet_last."' LIMIT 1;");
				if(isset($sqltbl['height'])){
					$wallets_bd[$wallet_last]= $sqltbl;
					query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `view`=3 WHERE `wallet`= '".$wallet_last."' and `view`=1;");
				} else $wallets_bd[$wallet_last]= 1;
			}
			$limit=(int)($limit_synch*5/6)-$limit_tmp;
      if($limit>0){
        $result= mysqli_query_bd("SELECT `wallet`, `ref1`, `ref2`, `ref3`, `noda`, `nodause`, `balance`, `date`, `percent_ref`, `height`, `signpub`, `view` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `view`=3 and `date`<".($GLOBALS['date_synch']-5*60)." and (`checkwallet`='' or `checkwallet`<'".($checkbalancenodatime>0?$checkbalancenodatime:'')."') ".(count($wallets_bd)>0?"and `wallet` NOT IN ('".implode("','",array_keys($wallets_bd))."')":'')." ORDER BY `checkwallet`,`date` LIMIT ".$limit.";");
        while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))$wallets_bd[$sqltbl_arr['wallet']]= $sqltbl_arr;
      }
      $post_synchwallets['type']= 'synchwallets';
			$post_synchwallets['synch_wallet']= $synch_wallet;
      if(isset($wallets_bd)){
        $post_synchwallets['wallets']= json_encode(array_keys($wallets_bd));
				if(count($wallets_bd)>=$limit_synch/6)$nodaping= [];
      }
      $noda_json_arr= connect_noda_multi(array_keys($noda_balance),'',$post_synchwallets,10,0);
      if(is_array($noda_json_arr) && count($noda_json_arr)>=3){
        foreach($noda_json_arr as $key1 => $value1){
					if(isset($nodaping) && $key1!=$noda_ip)$nodaping[$key1]= 1;
          if(isset($noda_balance[$key1]) && isset($value1['noda']) && $value1['noda']==$key1){
            $nodas_balance_sum+=$noda_balance[$key1];
            if(isset($noda_json_arr[$key1]['synchwallets']))array_splice($noda_json_arr[$key1]['synchwallets'], $limit_synch);
					} else unset($noda_json_arr[$key1]);
					if(isset($value1['synchwallets'])){
						$wallets_no_bd_check= [];
						foreach($value1['synchwallets'] as $key2 => $value2){
							if(isset($value2['wallet']) && preg_replace("/[^0-9]/i",'',$value2['wallet'])==$value2['wallet'] && strlen($value2['wallet'])==18 && !isset($wallets_bd[$value2['wallet']]) && count($wallets_no_bd_check)<=2*$limit_synch)$wallets_no_bd_check[$value2['wallet']]= 1;
						}
					}
				}
				if(isset($wallets_no_bd_check) && count($wallets_no_bd_check)>0){
					$result= mysqli_query_bd("SELECT `wallet` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet` IN ('".implode("','",array_keys($wallets_no_bd_check))."') and `signpub`!='';");
					while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))unset($wallets_no_bd_check[$sqltbl_arr['wallet']]);
					foreach($wallets_bd as $key => $value)if(!isset($value['wallet']))$wallets_no_bd_check[$key]= 1;
				}
				foreach($noda_json_arr as $key1 => $value1){
					if(isset($value1['noda']) && preg_replace("/[^0-9\.]/i",'',$value1['noda'])==$value1['noda'] && $value1['noda']==$key1){
						if(isset($value1['time']) && (int)$value1['time']>0){
							$value1['time']= (int)$value1['time'];
							if($json_arr['time']>$value1['time']+5 || $json_arr['time']<$value1['time']-60)unset($noda_json_arr[$key1]);
							else {
								if(isset($value1['synchwallets'])){
									foreach($value1['synchwallets'] as $key2 => $value2){
										if(is_array($value2) && count($value2)==12 && isset($value2['wallet']) && $value2['wallet'] && isset($value2['balance']) && isset($value2['percent_ref']) && isset($value2['ref1']) && isset($value2['ref2']) && isset($value2['ref3']) && isset($value2['noda']) && isset($value2['nodause']) && isset($value2['date']) && isset($value2['height']) && isset($value2['signpub']) && isset($value2['view']) && ($value2['view']==1 || $value2['view']==3)){
											if(isset($wallets_bd[$value2['wallet']]['wallet']) && !isset($wallet_check_del[$value2['wallet']])){
												if(!isset($wallets_bd[$value2['wallet']]['checkbalance']))$wallets_bd[$value2['wallet']]['checkbalance']= 0;
												if(!isset($wallets_bd[$value2['wallet']]['checkbalanceall']))$wallets_bd[$value2['wallet']]['checkbalanceall']= 0;
												if(
													$wallets_bd[$value2['wallet']]['balance']==$value2['balance'] &&
													$wallets_bd[$value2['wallet']]['percent_ref']==$value2['percent_ref'] &&
													$wallets_bd[$value2['wallet']]['ref1']==$value2['ref1'] &&
													$wallets_bd[$value2['wallet']]['ref2']==$value2['ref2'] &&
													$wallets_bd[$value2['wallet']]['ref3']==$value2['ref3'] &&
													$wallets_bd[$value2['wallet']]['noda']==$value2['noda'] &&
													$wallets_bd[$value2['wallet']]['nodause']==$value2['nodause'] &&
													$wallets_bd[$value2['wallet']]['date']==$value2['date'] &&
													$wallets_bd[$value2['wallet']]['height']==$value2['height'] &&
													$wallets_bd[$value2['wallet']]['signpub']==$value2['signpub']
													){
														if(isset($nodaping[$key1]))unset($nodaping[$key1]);
														$wallets_bd[$value2['wallet']]['checkbalance']+= $noda_balance[$key1];
													} else if($value2['view']==1){
														if(!isset($checkwallets_false[$value2['wallet']]))$checkwallets_false[$value2['wallet']]= $key1;
														else $checkwallets_false[$value2['wallet']]= 1;
													}
													$wallets_bd[$value2['wallet']]['checkbalanceall']+= $noda_balance[$key1];
											} else if(!isset($wallet_add[$value2['wallet']]) && isset($wallets_no_bd_check[$value2['wallet']])){
												if(preg_replace("/[^0-9]/i",'',$value2['wallet'])==$value2['wallet'] && strlen($value2['wallet'])==18
													&& preg_replace("/[^0-9]/i",'',$value2['ref1'])==$value2['ref1'] && strlen($value2['ref1'])<=18
													&& preg_replace("/[^0-9]/i",'',$value2['ref2'])==$value2['ref2'] && strlen($value2['ref2'])<=18
													&& preg_replace("/[^0-9]/i",'',$value2['ref3'])==$value2['ref3'] && strlen($value2['ref3'])<=18
													&& preg_replace("/[^0-9\.]/i",'',$value2['noda'])==$value2['noda'] && strlen($value2['noda'])<=40
													&& preg_replace("/[^0-9\.]/i",'',$value2['nodause'])==$value2['nodause'] && strlen($value2['nodause'])<=40
													&& preg_replace("/[^0-9]/i",'',$value2['balance'])==$value2['balance'] && $value2['balance']>=0
													&& preg_replace("/[^0-9]/i",'',$value2['date'])==$value2['date'] && $value2['date']<=$json_arr['time']+2
													&& preg_replace("/[^0-9]/i",'',$value2['percent_ref'])==$value2['percent_ref'] && $value2['percent_ref']>=0
													&& preg_replace("/[^0-9]/i",'',$value2['height'])==$value2['height']
													&& preg_replace("/[^0-9]/i",'',$value2['signpub'])==$value2['signpub'] && strlen($value2['signpub'])==19
													){
													if(!isset($wallet_add[$value2['wallet']])){
														$balance_check[$value2['wallet']]= 0;
														foreach($noda_balance as $key3 => $value3){
															if(isset($noda_json_arr[$key3]['synchwallets'])){
																$checkTrue= 0;
																$value_temp= [];
																foreach($noda_json_arr[$key3]['synchwallets'] as $key4 => $value4){
																	$value_temp= $value4;
																	if(is_array($value_temp) && count($value_temp)==12 && isset($value_temp['wallet']) && $value_temp['wallet']==$value2['wallet'] && isset($value_temp['view']) && ($value_temp['view']==1 || $value_temp['view']==3)){
																		if(
																		isset($value_temp['balance']) && $value_temp['balance']==$value2['balance'] &&
																		isset($value_temp['percent_ref']) && $value_temp['percent_ref']==$value2['percent_ref'] &&
																		isset($value_temp['ref1']) && $value_temp['ref1']==$value2['ref1'] &&
																		isset($value_temp['ref2']) && $value_temp['ref2']==$value2['ref2'] &&
																		isset($value_temp['ref3']) && $value_temp['ref3']==$value2['ref3'] &&
																		isset($value_temp['noda']) && $value_temp['noda']==$value2['noda'] &&
																		isset($value_temp['nodause']) && $value_temp['nodause']==$value2['nodause'] &&
																		isset($value_temp['date']) && $value_temp['date']==$value2['date'] &&
																		isset($value_temp['height']) && $value_temp['height']==$value2['height'] &&
																		isset($value_temp['signpub']) && $value_temp['signpub']==$value2['signpub']
																		){
																			$checkTrue= 1;
																			break;
																		} else if($checkTrue!= 1)$checkTrue= 2;
																	}
																}
																if($checkTrue== 1){
																	if(isset($nodaping[$key3]))unset($nodaping[$key3]);
																	$balance_check[$value2['wallet']]+= $noda_balance[$key3];
																	$checkwallets_true[$key3][$value_temp['wallet']]= 1;
																	unset($checkwallets[$key3][$value_temp['wallet']]);
																} else if($checkTrue== 2 && isset($value_temp['wallet']) && !isset($checkwallets[$key3][$value_temp['wallet']]) && !isset($checkwallets_true[$key3][$value_temp['wallet']]) && (!isset($checkwallets[$key3]) || (is_array($checkwallets[$key3]) && count($checkwallets[$key3])<$limit_synch/30)) && $key3!=$noda_ip){
																	$checkwallets[$key3][$value_temp['wallet']]= 1;
																}
															}
														}
														if($balance_check[$value2['wallet']]> 0.5*$nodas_balance_sum){
															query_bd("REPLACE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` (`wallet`, `ref1`, `ref2`, `ref3`, `noda`, `nodause`, `balance`, `date`, `percent_ref`, `height`, `signpub`, `view`) VALUES ('".$value2['wallet']."','".$value2['ref1']."','".$value2['ref2']."','".$value2['ref3']."','".$value2['noda']."','".$value2['nodause']."','".$value2['balance']."','".$value2['date']."','".$value2['percent_ref']."','".$value2['height']."','".$value2['signpub']."','0')");
															if(mysqli_affected_rows($mysqli_connect)>=1){
																$wallet_add[$value2['wallet']]= 1;
																delete_wallet_history($value2['wallet'],$value2['height'],$value2['nodause'],$value2['ref1'],$value2['ref2'],$value2['ref3']);
															}
															if(isset($nodaping[$key1]))unset($nodaping[$key1]);
														}
													}
												} else {
													$checkwallets[$key1][$value2['wallet']]= 1;
													unset($noda_json_arr[$key1]);
													break;
												}
											}
										}
									}
								}
							}
						}
					}
				}
				unset($checkwallets_true);
				if(isset($noda_json_arr) && is_array($noda_json_arr) && count(array_keys($noda_json_arr))>0)query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `checknoda`='".$GLOBALS['date_synch']."' WHERE `noda` IN ('".implode("','",array_keys($noda_json_arr))."');");
				if(!($nodas_balance_sum_bd>0)){
					if(!isset($post_synchwallets['wallets_with_noda_first'])){
						query_bd("SELECT IFNULL(SUM(`balance`),0) as balance FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `view`>0 and `noda`!='' and (`checknoda`='' or `checknoda`<= ".$GLOBALS['date_synch'].") and `balance`>=100 LIMIT 1;");
						if(isset($sqltbl['balance']) && $sqltbl['balance']>0)$nodas_balance_sum_bd= $sqltbl['balance'];
						else $nodas_balance_sum_bd=$nodas_balance_sum;
					} else {
						$nodas_balance_sum_bd=$nodas_balance_sum;
					}
				}
				if(!($nodas_balance_sum_bd>0) && isset($noda_trust) && is_array($noda_trust))$nodas_balance_sum_bd= count($noda_trust);
				if(isset($wallets_bd) && count($wallets_bd)>0){
					foreach($wallets_bd as $key => $value){
						if(isset($value['wallet']) && (!isset($value['checkbalanceall']) || $value['checkbalanceall']==0) && count($noda_balance)>=5 && $nodas_balance_sum>0 && $nodas_balance_sum> 5*$noda_balance_noda_ip){
							$value['checkbalance']= 0;
							$value['checkbalanceall']= $nodas_balance_sum;
						}
						if(isset($value['view']) && $value['view']!=1 && ($value['view']!=3 || (isset($value['checkbalance']) && $value['checkbalance']>=0 && isset($value['checkbalanceall']) && $value['checkbalanceall']>0)))query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `checkbalance`=IF(`checkbalance`!='',`checkbalance`,0)+'".(isset($value['checkbalance'])?$value['checkbalance']:0)."', `checkbalanceall`=IF(`checkbalanceall`!='',`checkbalanceall`,0)+'".($value['view']==3?$value['checkbalanceall']:$nodas_balance_sum)."', `checkwallet`='".$checkbalancenodatime."' WHERE `wallet`= '".$key."' and `view`!=1;");
					}
				}
				$result= mysqli_query_bd("SELECT `wallet` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`>".$synch_wallet." ORDER BY `wallet` ASC LIMIT ".(int)($limit_synch/2).";");
				while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
					if($sqltbl_arr['wallet']>$synch_wallet){
						$synch_wallet=$sqltbl_arr['wallet'];
						$synch_wallet_true= 1;
					}
				}
				if(!isset($synch_wallet_true))$synch_wallet= 1;
				query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` SET `value`= ".$synch_wallet." WHERE `name`= 'synch_wallet';");
				if(isset($nodaping) && is_array($nodaping) && count($nodaping)>=1 && count($noda_balance)>=5 && count($nodaping)<count($noda_balance)/2){
					query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `nodaping`=IF(`nodaping`!='',`nodaping`,0)+IF(`nodaping`='' or `nodaping`<1440,2,0),`checknoda`=".$GLOBALS['date_synch']."+IF(`nodaping`!='',`nodaping`,1)*60 WHERE `noda` IN ('".implode("','",array_keys($nodaping))."') and `noda`!= '".$noda_ip."';");
				}
				foreach($noda_balance as $key => $value)if(isset($nodaping[$key]))unset($noda_balance[$key]);
				if(isset($checkwallets_false)){
					foreach($checkwallets_false as $key => $value){
						if($value>1 && $key>1 && !isset($checkwallets[$value][$key]))$checkwallets[$value][$key]= 1;
					}
				}
			}
		}
		if($nodas_balance_sum_bd>0 && $noda_balance_noda_ip>0 && (0.9*$nodas_balance_sum_bd-$noda_balance_noda_ip)>0){
			query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `checkbalance`='', `checkbalanceall`='', `checkwallet`='', `view`=1, `checknoda`='' WHERE `view`>1 and `checkbalanceall`!='' and `checkbalanceall`>= ".(0.9*$nodas_balance_sum_bd-$noda_balance_noda_ip)." and `checkbalance`!='' and `checkbalance`+".$noda_balance_noda_ip."> 0.5*`checkbalanceall`;");
			query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `checkbalance`='', `checkbalanceall`='', `checkwallet`='', `view`=1, `checknoda`='' WHERE `view`=0 and `signpub`!='' and `checkbalanceall`!='' and `checkbalanceall`>= ".(0.9*$nodas_balance_sum_bd-$noda_balance_noda_ip)." and `checkbalance`!='' and `checkbalance`> 0.5*`checkbalanceall`;");
			query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `checkbalance`='', `checkbalanceall`='', `checkwallet`='', `view`=IF(`view`=3,2,0), `checknoda`='' WHERE `view`>1 and `checkbalanceall`!='' and `checkbalanceall`>= ".(0.9*$nodas_balance_sum_bd-$noda_balance_noda_ip)." and (`checkbalance`='' or `checkbalance`+".$noda_balance_noda_ip."<= 0.5*`checkbalanceall`);");
		}
	}
}
if($stop!=1 && $request['type']=="nodas"){
	delay_now();
  if(isset($request['order']) && $request['order']=='balance')$order= "n1.`balance` DESC";
  else $order= "n2.`datelastuse` DESC";
  $result= mysqli_query_bd("SELECT n1.`noda` as noda, n1.`wallet` as wallet, n1.`balance` as balance, n2.datelastuse as datelastuse FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` as n1 LEFT JOIN (SELECT `nodause`, MAX(`date`) as datelastuse FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` GROUP BY `nodause`) as n2 ON n2.`nodause`=n1.`noda` WHERE n1.`view`>0 and n1.`noda`!='' and (`checknoda`='' or `checknoda`<= ".$GLOBALS['date_synch'].") ORDER BY ".$order." LIMIT 100;");
  while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))if($sqltbl_arr['noda']){
		if(!$sqltbl_arr['datelastuse'] || !($sqltbl_arr['datelastuse']>0))$sqltbl_arr['datelastuse']= "1";
		$json_arr['nodas'][]= $sqltbl_arr;
  }
  if(isset($json_arr['nodas']) && is_array($json_arr['nodas'])){
		query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`!='' and (`checknoda`='' or `checknoda`<= ".$GLOBALS['date_synch'].") and `view`>0 LIMIT 1;");
		if(isset($sqltbl['count'])){
			$json_arr['nodas'][]= (int)$sqltbl['count'];
			if(isset($GLOBALS['file_nodas']) && $GLOBALS['file_nodas'] && (!file_exists($GLOBALS['file_nodas']) || is_writable($GLOBALS['file_nodas'])))file_put_contents($GLOBALS['file_nodas'], json_encode($json_arr['nodas']));
		}
  }
  $stop=1;
} else
if($stop!=1 && $request['type']=="nodainfo"){
	delay_now();
  $archive= 'eGOLD_v'.$version.'.zip';
  if(file_exists($archive)) $md5= ',"MD5":"'.strtoupper(hash_file('md5', $archive)).'"';
  query_bd("SELECT n1.`wallet` as owner, n1.`balance` as balance, n2.datelastuse as datelastuse FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` as n1 LEFT JOIN (SELECT `nodause`, MAX(`date`) as datelastuse FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` GROUP BY `nodause`) as n2 ON n2.`nodause`=n1.`noda` WHERE n1.`noda`='".$GLOBALS['noda_ip']."' LIMIT 1;");
  $nodainfo= '{"time":"'.$json_arr['time'].'","noda":"'.$GLOBALS['noda_ip'].'","version":"'.$version.'"'.(isset($md5)?',"download":"'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']?'https':'http').'://'.$_SERVER['SERVER_NAME'].'/'.$archive.'"'.$md5:'').(isset($email_domain) && $email_domain?',"email_domain":"'.$email_domain.'"':'').(isset($noda_site) && $noda_site?',"noda_site":"'.$noda_site.'"':'').',"owner":"'.(isset($sqltbl['owner']) && $sqltbl['owner']?gold_wallet_view($sqltbl['owner']).'"':gold_wallet_view($noda_wallet).'","status":"not_activated"').(isset($sqltbl['balance'])?',"balance":"'.$sqltbl['balance'].'","datelastuse":"'.$sqltbl['datelastuse'].'"':'').',"balanceall":"'.(isset($balanceall) && $balanceall>0?$balanceall:0).'","walletscount":"'.(isset($walletscount) && $walletscount>0?$walletscount:0).'"}';
  echo $nodainfo;
  if(isset($GLOBALS['file_nodainfo']) && $GLOBALS['file_nodainfo'] && (!file_exists($GLOBALS['file_nodainfo']) || is_writable($GLOBALS['file_nodainfo'])))file_put_contents($GLOBALS['file_nodainfo'], $nodainfo);
  exit_now();
  $stop=1;
} else
if($stop!=1 && $request['type']=="wallet"){
	delay_now();
  noda_owner();
  $json_arr['percent']= (string)(pow_percent($GLOBALS['percent_main'], $GLOBALS['percent_last'], $GLOBALS['percent_old'], $json_arr['time'], $json_arr['time']-1, 1)+1);
  $json_arr['percent_noda']= (string)(($json_arr['percent']-1)*1.25+1);
  if(isset($request['wallet']))$wallet= wallet($request['wallet'],$json_arr['time'],0,0,1);
  if(isset($wallet['wallet'])){
		$json_arr['wallet']= gold_wallet_view($wallet['wallet']);
		if(isset($wallet['ref1']) && $wallet['ref1']>0)$json_arr['ref1']= gold_wallet_view($wallet['ref1']);else $json_arr['ref1']= '0';
		if(isset($wallet['ref2']) && $wallet['ref2']>0)$json_arr['ref2']= gold_wallet_view($wallet['ref2']);else $json_arr['ref2']= '0';
		if(isset($wallet['ref3']) && $wallet['ref3']>0)$json_arr['ref3']= gold_wallet_view($wallet['ref3']);else $json_arr['ref3']= '0';
		$json_arr['nodawallet']=$wallet['noda'];
		$json_arr['nodawalletuse']=$wallet['nodause'];
		$json_arr['balance']= (string)$wallet['balance'];
		if($wallet['balancecheck']!=0){
			$json_arr['balancetransactioncheck']= (string)$wallet['balancecheck'];
			$json_arr['percent_4']= "0";
			$json_arr['percent_5']= "0";
	  } else {
			$json_arr['percent_4']= (string)$wallet['percent_4'];
			$json_arr['percent_5']= (string)$wallet['percent_5'];
	  }
	  $json_arr['percent_ref']= (string)$wallet['percent_ref'];
		$json_arr['height']= (string)$wallet['height'];
		$json_arr['date']= (string)$wallet['date'];
		if(isset($request['password'])){
			query_bd("SELECT `email`,`up`,`down`,`date`,`password` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` WHERE `wallet`= '".$request['wallet']."' and `nodatrue`=1 LIMIT 1;");
			if(isset($sqltbl['password']) && gen_sha3($sqltbl['password'],256)==$request['password']){
				if(isset($email_domain) && $email_domain){
					if($sqltbl['email']){
							$json_arr['usersemail']= 'true';
							$json_arr['usersemailup']= $sqltbl['up'];
							$json_arr['usersemaildown']= $sqltbl['down'];
							$json_arr['usersemaildateupdate']= $sqltbl['date'];
					} else $json_arr['usersemail']= 'false';
				} else $json_arr['usersemail']= 'email_domain_false';
			}
		}
		if($wallet['noda'])$json_arr['nodaping']= $wallet['nodaping'];
		$json_arr['signpub']= $wallet['signpub'];
  } else $json_arr['wallet']= 'false';
  $stop=1;
} else
if($stop!=1 && $request['type']=="history"){
	delay_now();
	if(isset($request['details']) && $request['details'])$request['details']= mysqli_real_escape_string($mysqli_connect,$request['details']);
	if((!isset($request['history']) && !isset($request['wallet']) && !isset($request['recipient']) ) || (isset($request['history']) && strlen($request['history'])==18) || (isset($request['wallet']) && strlen($request['wallet'])==18) || (isset($request['recipient']) && strlen($request['recipient'])==18)){
		if(isset($request['p2p'])){
			if(isset($request['details'])){
				if($request['details']){
					$details_arr= explode(' ',$request['details']);
					$request['details']= '';
					$words_count=0;
					foreach($details_arr as $key => $value)if($value){$request['details'].= ($words_count<5?'%':'').$value;$words_count++;}
				}
			}
			if(isset($request['recipient']) && (isset($request['height']) || (!(isset($request['money']) && $request['money']>0) && !(isset($request['pin']) && $request['pin']>0) && !(isset($request['details']) && $request['details']) && !(isset($request['date']) && $request['date']) && !(isset($request['dateto']) && $request['dateto']) && !(isset($request['wallet']) && $request['wallet'])))){
				$query= "SELECT `wallet`,`pin`,`details`,`checkhistory` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `details`!='' and (`checkhistory`=4 or (`date`>= ".($GLOBALS['date_synch']-3*86400)." and (`date`>= ".($GLOBALS['date_synch']-86400)." or `checkhistory`=5))) and `recipient`=1";
				$query.= " and `pin`>=100000000000000000 and ";
				if(isset($request['all']) && $request['all']!=1)unset($request['all']);
				$query.= "(`wallet`='".$request['recipient']."' or `details` LIKE '6|%|".$request['recipient']."' or (`details` LIKE '3|%|".$request['recipient']."' and `checkhistory`<4) or `details` LIKE '4|%|".$request['recipient']."') and `checkhistory`!=2 and `checkhistory`<9 ORDER BY `date` DESC, `wallet`, `height` LIMIT 1000;";
				$result= mysqli_query_bd($query);
				while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
					$details_arr= explode('|',$sqltbl_arr['details']);
					if(count($details_arr)==2 || count($details_arr)==3){
						$type_action= (int)$details_arr[0];
						$details_check= (int)$details_arr[1];
						if(isset($details_arr[2]) && $details_arr[2])$wallet_check= (int)$details_arr[2];
						if($sqltbl_arr['checkhistory']==1){
							if($type_action==1 && $sqltbl_arr['wallet']==$request['recipient'])$type_action=6;
							else if($type_action==3){
								if($sqltbl_arr['wallet']==$request['recipient'])$type_action=16;
								else if(isset($wallet_check) && $wallet_check==$request['recipient'])$type_action=8;
							} else if($type_action==6){
								if($sqltbl_arr['wallet']==$request['recipient'])$type_action=12;
								else if(isset($wallet_check) && $wallet_check==$request['recipient'])$type_action=13;
							}
						}
						else if($sqltbl_arr['checkhistory']==4){
							if($type_action==2 || $sqltbl_arr['pin']==$request['recipient'])$type_action=3;
							else if($sqltbl_arr['wallet']==$request['recipient']){
								if($type_action==4 && isset($wallet_check))$type_action=5;
								else $type_action=4;
							}
						}
						else if($sqltbl_arr['checkhistory']==5){
							if($type_action==4 && isset($wallet_check) && $wallet_check!=$request['recipient'])$type_action=11;
							else if($type_action==1 || $type_action==3 || $type_action==4)$type_action=10;
							else if($type_action==6 && isset($wallet_check) && $wallet_check==$request['recipient'])$type_action=9;
						}
						else $type_action=0;
						if(($type_action>0) && (!isset($history_deals_wallet_action[$sqltbl_arr['pin']."_".$details_check]) || ($history_deals_wallet_action[$sqltbl_arr['pin']."_".$details_check]<$type_action || ($type_action==12 && $history_deals_wallet_action[$sqltbl_arr['pin']."_".$details_check]==16) || ($type_action==16 && $history_deals_wallet_action[$sqltbl_arr['pin']."_".$details_check]==12)))){
							if(isset($history_deals_wallet_action[$sqltbl_arr['pin']."_".$details_check]) && (($type_action==12 && $history_deals_wallet_action[$sqltbl_arr['pin']."_".$details_check]==16) || ($type_action==16 && $history_deals_wallet_action[$sqltbl_arr['pin']."_".$details_check]==12)))$history_deals_wallet_action[$sqltbl_arr['pin']."_".$details_check]= 17;
							else $history_deals_wallet_action[$sqltbl_arr['pin']."_".$details_check]= $type_action;
							$history_deals_wallet[$sqltbl_arr['pin']."_".$details_check]= "'".$sqltbl_arr['pin']."','".$details_check."'";
						}
					}
					unset($type_action);
					unset($details_check);
					unset($wallet_check);
				}
				if(isset($history_deals_wallet))$history_deals_wallet= array_slice($history_deals_wallet,-50,50);
			}
			if(!isset($request['height'])){
				if(isset($request['recipient']))$where_add1= "`wallet`='".$request['recipient']."' and `pin`<1000000000000 and (`checkhistory`=5 or `checkhistory`=3 or `checkhistory`=1)";
				if(!isset($request['all']))$where_add2= "`date`>= '".($GLOBALS['date_synch']-3*86400+5*60)."' and `checkhistory`= 1";
				if(isset($history_deals_wallet))$where_add3= "((`checkhistory`= 3 and `date`>= '".($GLOBALS['date_synch']-4*86400+5*60)."') or `checkhistory`= 4 or `checkhistory`= 5) and (`wallet`,`date`) IN ((".implode("),(",($history_deals_wallet))."))";
				if(isset($where_add1) || isset($where_add2) || isset($where_add3)){
					if(isset($request['details']) && $request['details']) $where= "`details` LIKE '".$request['details']."%'"; else $where= "`details` != ''";
					$where.= " and `pin`<1000000000000";
					if(isset($request['date'])) $where.= " and `date`>= '".$request['date']."'";
					if(isset($request['dateto'])) $where.= " and `date`<= '".$request['dateto']."'";
					if(isset($request['wallet']) && strlen($request['wallet'])==18) $where.= " and `wallet`= '".$request['wallet']."'";
					if(isset($request['money']) && $request['money']>0) $where.= " and `pin`<= '".$request['money']."'";
					if(isset($request['pin']) && $request['pin']>0) $where.= " and `money`>= '".$request['pin']."'";
					$where.= " and (";
					$where_add_all= '';
					if(isset($where_add2) && isset($where_add3))$where_add_all.= "(".$where_add2.") or (".$where_add3.")";
					else if(isset($where_add2))$where_add_all.= $where_add2;
					else if(isset($where_add3))$where_add_all.= $where_add3;
					if(isset($where_add1)){
						if($where_add_all)$where_add_all= "(".$where_add1.") or (".$where_add_all.")";
						else $where_add_all.= $where_add1;
					}
					$where.= $where_add_all;
					$where.= ")";
					$where= "`wallet`!=1 and `money`>=120 and ".$where;
					$order= "`checkhistory`!=1 DESC, ".(isset($request['recipient'])?"`wallet`='".$request['recipient']."' DESC, ":'')."`date` ".(isset($request['date'])?"ASC":"DESC");
					if(!isset($request['checkhistory'])){
						query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE ".$where." ".(isset($request['limit']) && $request['limit']==1?"and `date`< UNIX_TIMESTAMP()-3":'')." and `checkhistory`<9 LIMIT 1;");
						if(isset($sqltbl['count']) && $sqltbl['count']>0)$history_count= (int)$sqltbl['count'];
					}
				}
			} else if(isset($request['recipient']) && isset($request['wallet']) && isset($request['date'])){
				query_bd("SELECT `wallet`, `recipient`, `money`, `pin`, `height`, `nodawallet`, `nodause`, `nodaown`, `details`, `date`, `checkhistory` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`= '".$request['wallet']."' and `height`= '".$request['height']."' and `date`= '".$request['date']."' and `pin`!='' and `details`!='' and `checkhistory`!=0 and `checkhistory`<9 ORDER BY `date` ASC LIMIT 1;");
				if(isset($sqltbl['height'])){
					$p2p_status= $sqltbl;
					if($p2p_status['checkhistory']==1 || $p2p_status['checkhistory']==3 || $p2p_status['checkhistory']==5){
						query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `pin`= '".$request['wallet']."' and `details` LIKE '%|".$request['date']."%' and `checkhistory`= 0 and `details` NOT LIKE '3|".$request['date']."|%' LIMIT 1;");
						if(isset($sqltbl['height']))$p2p_status['status']= "0";
						else $p2p_status['status']= "1";
					} else $p2p_status['status']= "1";
				}
			}
		} else if(isset($request['sms'])){
			$where= "`details` != '' and `checkhistory`=1";
			if(isset($request['pin']) && strlen($request['pin'])==13)$where.= " and `pin`=".$request['pin'];
			else if(!isset($request['checkhistory']))$where.= " and `pin`=1000000000000";
			else $where.= " and `pin`>=1000000000000 and `pin`<10000000000000";
			if(isset($request['wallet']) && strlen($request['wallet'])==18)$where.= " and (`wallet`='".$request['wallet']."' or `details` LIKE '".$request['wallet']."|%')";
			if(isset($request['date'])) $where.= " and `date`>= '".$request['date']."'";
			if(isset($request['dateto'])) $where.= " and `date`<= '".$request['dateto']."'";
			if(isset($request['order']) && $request['order']=='asc') $order= "`date` ASC";else $order= "`date` DESC";
			if(!isset($request['checkhistory'])){
				query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE ".$where." and `checkhistory`<9 LIMIT 1;");
				if(isset($sqltbl['count']) && $sqltbl['count']>0)$history_count= (int)$sqltbl['count'];
			}
		} else {
			if(isset($request['checkhistory'])) $where= "`checkhistory`= '".$request['checkhistory']."'";
			else if(isset($request['all']) && $request['all']>0 && $request['all']<=5){
				if($request['all']==1) $where= "`checkhistory`= 0";
				else if($request['all']==2) $where= "`checkhistory`!= 2";
				else if($request['all']==3) $where= "`checkhistory`>= 0";
				else if($request['all']==4) $where= "`checkhistory`> 2";
				else if($request['all']==5) $where= "`checkhistory`> 0 and `checkhistory`!=2";
			} else $where= "`checkhistory`= 1";
			if(isset($request['pin']) && $request['pin']!='0') $where.= " and `pin`= '".$request['pin']."'";
			if(isset($request['details']) && $request['details']) $where.= " and `details`= '".$request['details']."'";
			if(isset($request['date'])) $where.= " and `date`>= '".$request['date']."'";
			if(isset($request['dateto'])) $where.= " and `date`<= '".$request['dateto']."'";
			else if(isset($request['all']) && $request['all']==1)$where.= " and `date`< UNIX_TIMESTAMP()-3";
			if(isset($request['height']) && (isset($request['wallet']) or isset($request['recipient']))) $where.= " and `height`>= '".$request['height']."'";
			if(isset($request['nodause'])) $where.= " and `nodause`= '".$request['nodause']."'";
			if(isset($request['order']) && $request['order']=='asc') $order= "`date` ASC";else $order= "`date` DESC";
			if(isset($request['wallet']) && strlen($request['wallet'])==18 && isset($request['recipient']) && $request['wallet']==$request['recipient'])$request['history']=$request['wallet'];
		}
		if(!isset($start) && isset($request['start']) && $request['start']>0) $start= $request['start'];else $start=0;
		if(!isset($limit) && isset($request['limit']) && $request['limit']>0 && $request['limit']<=100) $limit= $request['limit'];else $limit= 25;
		if(isset($request['history']) && strlen($request['history'])==18){
			if((!isset($request['all']) || $request['all']==2)){
				query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE ".$where." and (`wallet`= '".$request['history']."' or `recipient`= '".$request['history']."') ".(isset($request['limit']) && $request['limit']==1?"and `date`< UNIX_TIMESTAMP()-3":'')." and `checkhistory`<9 LIMIT 1;");
				if(isset($sqltbl['count']) && $sqltbl['count']>0)$history_count= (int)$sqltbl['count'];
			}
			if(isset($history_count) || (isset($request['all']) && ($request['all']==3 || $request['all']==5))){
				$result= mysqli_query_bd("SELECT `wallet`, `recipient`, `money`, `pin`, `height`, `nodawallet`, `nodause`, `nodaown` ".(isset($request['all']) && $request['all']==5?", `details`":'').", `date`, `checkhistory` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE (`wallet`= '".$request['history']."' or `recipient`= '".$request['history']."') and ".$where." ".(isset($request['limit']) && $request['limit']==1?"and `date`< UNIX_TIMESTAMP()-3":'')." and `checkhistory`<9 ORDER BY ".$order." LIMIT ".$start.",".$limit.";");
				while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))$json_arr['history'][]= $sqltbl_arr;
			}
		} else {
			if(!isset($request['p2p']) && !isset($request['sms']) && isset($request['wallet']) && strlen($request['wallet'])==18) $where.= " and `wallet`= '".$request['wallet']."'";
			if(!isset($request['p2p']) && !isset($request['sms']) && isset($request['recipient']) && strlen($request['recipient'])==18) $where.= " and `recipient`= '".$request['recipient']."'";
			if(isset($request['p2p']) && isset($request['recipient'])){
				function p2p_history_view($sqltbl_arr){
					global $json_arr, $request, $history_deals_wallet, $history_deals_wallet_action;
					if($request['recipient']==$sqltbl_arr['wallet']){
						if($sqltbl_arr['checkhistory']==1)$sqltbl_arr['checkhistory']=2;
						else if($sqltbl_arr['checkhistory']==3)$sqltbl_arr['checkhistory']=7;
						else if($sqltbl_arr['checkhistory']==5)$sqltbl_arr['checkhistory']=9;
					}
					if(isset($history_deals_wallet[$sqltbl_arr['wallet']."_".$sqltbl_arr['date']]))$sqltbl_arr['checkhistory']= $history_deals_wallet_action[$sqltbl_arr['wallet']."_".$sqltbl_arr['date']];
					$json_arr['history'][]= $sqltbl_arr;
				}
			}
			if(isset($where) && !isset($p2p_status['status'])){
				$result= mysqli_query_bd("SELECT `wallet`, `recipient`, `money`, `pin`, `height`, `nodawallet`, `nodause`, `nodaown`, `details`, `date`, ".(isset($request['all']) && $request['all']==1?"`signpubreg`, `signreg`, `signpubnew`, `signnew`, `signpub`, `sign`,":'')." `checkhistory` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE ".$where." ".(!isset($request['checkhistory']) && (!isset($request['all']) || $request['all']!=5)?"and `wallet`!=1":'')." and `checkhistory`<9 ORDER BY ".$order." LIMIT ".$start.",".(!isset($request['p2p']) && !isset($request['sms']) && !isset($request['limit']) && isset($request['all']) && ($request['all']==1 || $request['all']==5)?$limit_synch:$limit).";");
				while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
					if(isset($request['p2p']) && isset($request['recipient']))p2p_history_view($sqltbl_arr);
					else $json_arr['history'][]= $sqltbl_arr;
				}
			} else if(isset($p2p_status['status']))p2p_history_view($p2p_status);
		}
		if(isset($request['p2p']) && isset($request['recipient']) && isset($json_arr['history']) && is_array($json_arr['history'])){
			foreach($json_arr['history'] as $key => $value){
				if($value['checkhistory']>=3)$json_arr_buyer_temp[$key]= "(".$value['wallet'].",'1|".$value['date']."')";
				if(in_array($value['checkhistory'],[3,4,5,8,9,10,11]))$json_arr_arbitr1_temp[$key]= "(".$value['wallet'].",'4|".$value['date'];
			}
			if(isset($json_arr['history'])){
				if(isset($json_arr_buyer_temp)){
					$result= mysqli_query_bd("SELECT `wallet`,`money`,`pin`,`details`,`date` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE (`pin`,`details`) IN (".implode(",",($json_arr_buyer_temp)).") and `checkhistory`<9;");
					while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
						$arr_id= array_search("(".$sqltbl_arr['pin'].",'".$sqltbl_arr['details']."')",$json_arr_buyer_temp);
						$json_arr['history'][$arr_id]['buyer']= $sqltbl_arr['wallet'];
						$json_arr['history'][$arr_id]['buymoney']= $sqltbl_arr['money'];
						$json_arr['history'][$arr_id]['buydate']= $sqltbl_arr['date'];
						if(isset($json_arr_arbitr1_temp[$arr_id]))$json_arr_arbitr2_temp[$arr_id]= $json_arr_arbitr1_temp[$arr_id]."|".$sqltbl_arr['wallet']."')";
					}
				}
				if(isset($json_arr['history']) && (isset($json_arr_arbitr1_temp) || isset($json_arr_arbitr2_temp))){
					if(isset($json_arr_arbitr1_temp) && is_array($json_arr_arbitr1_temp))foreach($json_arr_arbitr1_temp as $key => $value) $json_arr_arbitr1_temp[$key].= "')";
					if(isset($json_arr_arbitr1_temp) && is_array($json_arr_arbitr1_temp) && isset($json_arr_arbitr2_temp) && is_array($json_arr_arbitr2_temp))$json_arr_arbitr_temp= array_merge($json_arr_arbitr1_temp,$json_arr_arbitr2_temp);
					else if(isset($json_arr_arbitr1_temp) && is_array($json_arr_arbitr1_temp))$json_arr_arbitr_temp= $json_arr_arbitr1_temp;
					else if(isset($json_arr_arbitr2_temp) && is_array($json_arr_arbitr2_temp))$json_arr_arbitr_temp= $json_arr_arbitr2_temp;
					if(isset($json_arr_arbitr_temp)){
						$result= mysqli_query_bd("SELECT `wallet`,`pin`,`details` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE (`pin`,`details`) IN (".implode(",",($json_arr_arbitr_temp)).") and `checkhistory`<9;");
						while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
							$arr_id= '';
							if(isset($json_arr_arbitr1_temp) && is_array($json_arr_arbitr1_temp))$arr_id= array_search("(".$sqltbl_arr['pin'].",'".$sqltbl_arr['details']."')",$json_arr_arbitr1_temp);
							if(!$arr_id)$arr_id= array_search("(".$sqltbl_arr['pin'].",'".$sqltbl_arr['details']."')",$json_arr_arbitr2_temp);
							$json_arr['history'][$arr_id]['arbitr']= $sqltbl_arr['wallet'];
							if($json_arr['history'][$arr_id]['checkhistory']==3)$json_arr['history'][$arr_id]['checkhistory']=14;
							else if($json_arr['history'][$arr_id]['checkhistory']==4)$json_arr['history'][$arr_id]['checkhistory']=15;
						}
					}
					if(isset($json_arr['history']) && is_array($json_arr['history'])){
						foreach($json_arr['history'] as $key => $value)if(isset($value['checkhistory']) && (($value['checkhistory']>2 && (!isset($value['buyer']) || !isset($value['buymoney']) || !($value['checkhistory']==8 || !isset($request['recipient']) || $request['recipient']==$value['wallet'] || (isset($value['buyer']) && $request['recipient']==$value['buyer']) || (isset($value['arbitr']) && $request['recipient']==$value['arbitr']))) || (in_array($value['checkhistory'],[5,9,10,11,14,15]) && !isset($value['arbitr']))))){
							unset($json_arr['history'][$key]);
							if(isset($history_count) && $history_count>0)$history_count--;
						}
					}
				}
				if(isset($json_arr['history']) && is_array($json_arr['history'])){
					foreach($json_arr['history'] as $key => $value){
						if($value){
							if($value['checkhistory']==1)$json_arr_temp[0][]= $value;
							else if($value['checkhistory']==2 && $value['date']<$json_arr['time']-259200)$json_arr_temp[1][]= $value;
							else if($value['checkhistory']==2)$json_arr_temp[2][]= $value;
							else if(in_array($value['checkhistory'],[3,4,5,14,15]))$json_arr_temp[1][]= $value;
							else if(!in_array($value['checkhistory'],[1,2,9,10,11]) && isset($value['buydate']) && $value['buydate']<$json_arr['time']-86400)$json_arr_temp[1][]= $value;
							else if(in_array($value['checkhistory'],[9,10,11]) && isset($value['buydate']) && $value['buydate']<$json_arr['time']-259200)$json_arr_temp[1][]= $value;
							else $json_arr_temp[3][]= $value;
						}
					}
					if(isset($json_arr_temp) && is_array($json_arr_temp)){
						krsort($json_arr_temp);
						$json_arr['history']= [];
						foreach($json_arr_temp as $key1 => $value1){
							foreach($value1 as $key2 => $value2)$json_arr['history'][]= $value2;
						}
					}
				}
			}
		}
		if((isset($request['history']) && strlen($request['history'])==18 && isset($request['all']) && $request['all']==2) || (isset($request['p2p']) && isset($request['recipient']) && strlen($request['recipient'])==18))query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` SET `value`= '".(isset($request['history'])?$request['history']:$request['recipient'])."' WHERE `name`='synch_wallet_last_history';");
	}
  if(!isset($json_arr['history']) || !$json_arr['history'] || !is_array($json_arr['history'])){
	  $json_arr['history']['history']='not_found_history_this_noda';
  } else {
	  if(isset($history_count) && $history_count && $history_count>0)$json_arr['history']['count']= $history_count;
	  $json_arr['history'] = array_values($json_arr['history']);
  }
  $stop=1;
} else
if($stop!=1 && $request['type']=="referrals"){
	delay_now();
	if((!isset($request['ref']) && !isset($request['wallet']) && !isset($request['ref1'])  && !isset($request['ref2']) && !isset($request['ref3'])) || (isset($request['ref']) && strlen($request['ref'])==18) || (isset($request['wallet']) && strlen($request['wallet'])==18) || (isset($request['ref1']) && strlen($request['ref1'])==18) || (isset($request['ref2']) && strlen($request['ref2'])==18)|| (isset($request['ref3']) && strlen($request['ref3'])==18)){
		if(isset($request['wallet']) && strlen($request['wallet'])==18) $where= "`wallet`= '".$request['wallet']."'";
		if(isset($request['ref']) && strlen($request['ref'])==18){
			$where= (isset($where)?$where." and ":'')."((`ref1`= '".$request['ref']."' and `money1`>0) or (`ref2`= '".$request['ref']."' and `money2`>0) or (`ref3`= '".$request['ref']."' and `money3`>0))";
		} else {
			if(isset($request['ref1']) && strlen($request['ref1'])==18) $where= (isset($where)?$where." and ":'')."(`ref1`= '".$request['ref1']."' and `money1`>0)";
			if(isset($request['ref2']) && strlen($request['ref2'])==18) $where= (isset($where)?$where." and ":'')."(`ref2`= '".$request['ref2']."' and `money2`>0)";
			if(isset($request['ref3']) && strlen($request['ref2'])==18) $where= (isset($where)?$where." and ":'')."(`ref2`= '".$request['ref3']."' and `money3`>0)";
		}
		if(isset($request['height'])) $where= (isset($where)?$where." and ":'')."`height`>= '".$request['height']."'";
		if(isset($request['date'])) $where= (isset($where)?$where." and ":'')."`date`>= '".$request['date']."'";
		if(isset($request['dateto'])) $where= (isset($where)?$where." and ":'')."`date`<= '".$request['dateto']."'";
		if(isset($request['order']) && $request['order']=='asc') $order= "ASC";else $order= "DESC";
		if(isset($request['start']) && $request['start']>0) $start= $request['start'];else $start=0;
		if(isset($request['limit']) && $request['limit']>0 && ($request['limit']<=100 || (isset($request['all']) && $request['all']==1 && $request['limit']<=$limit_synch))) $limit= $request['limit'];else $limit=100;
		if(!isset($request['all']) || $request['all']!=1)query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals` WHERE ".$where." LIMIT 1;");
		if(!isset($sqltbl['count']) || $sqltbl['count']>0){
			if(isset($sqltbl['count']))$referrals_count= (int)$sqltbl['count'];
			$result= mysqli_query_bd("SELECT * FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals` WHERE ".$where." ORDER BY `date` ".$order." LIMIT ".$start.",".$limit.";");
			while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))$json_arr['referrals'][]= $sqltbl_arr;
		}
	}
  if(!isset($json_arr['referrals']) || !$json_arr['referrals']){
	  $json_arr['referrals']['referrals']='not_found_referrals_this_noda';
  } else {
	  if(isset($referrals_count))$json_arr['referrals']['count']= $referrals_count;
	  $json_arr['referrals'] = array_values($json_arr['referrals']);
  }
  $stop=1;
} else
if($stop!=1 && $request['type']=="referralwallets"){
	delay_now();
	if((!isset($request['ref']) && !isset($request['wallet']) && !isset($request['ref1'])  && !isset($request['ref2']) && !isset($request['ref3'])) || (isset($request['ref']) && strlen($request['ref'])==18) || (isset($request['wallet']) && strlen($request['wallet'])==18) || (isset($request['ref1']) && strlen($request['ref1'])==18) || (isset($request['ref2']) && strlen($request['ref2'])==18)|| (isset($request['ref3']) && strlen($request['ref3'])==18)){
		$where= "`wallet`!=''";
		if(isset($request['wallet']) && strlen($request['wallet'])==18) $where.= " and `wallet`= '".$request['wallet']."'";
		if(isset($request['ref']) && strlen($request['ref'])==18) $where.= " and (`ref1`= '".$request['ref']."' or `ref2`= '".$request['ref']."' or `ref3`= '".$request['ref']."')";
		else {
			if(isset($request['ref1']) && strlen($request['ref1'])==18) $where.= " and `ref1`= '".$request['ref1']."'";
			if(isset($request['ref2']) && strlen($request['ref2'])==18) $where.= " and `ref2`= '".$request['ref2']."'";
			if(isset($request['ref3']) && strlen($request['ref3'])==18) $where.= " and `ref3`= '".$request['ref3']."'";
		}
		if(isset($request['height'])) $where.= " and `height`>= '".$request['height']."'";
		if(isset($request['date'])) $where.= " and `date`>= '".$request['date']."'";
		if(isset($request['dateto'])) $where.= " and `date`<= '".$request['dateto']."'";
		if(isset($request['nodause'])) $where.= " and `nodause`= '".$request['nodause']."'";
		if(isset($request['order'])){
			if($request['order']=='asc')$order= "`date` ASC";
			else if($request['order']=='balanceasc')$order= "`balance` ASC, `date` DESC";
			else if($request['order']=='balancedesc')$order= "`balance` DESC, `date` DESC";
			else $order= "`date` DESC";
		} else $order= "`date` DESC";
		if(isset($request['start']) && $request['start']>0) $start= $request['start'];else $start=0;
		if(isset($request['limit']) && $request['limit']>0 && $request['limit']<100) $limit= $request['limit'];else $limit=100;
		query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE ".$where." LIMIT 1;");
		if(isset($sqltbl['count']) && $sqltbl['count']>0){
			$referrals_count= (int)$sqltbl['count'];
			$result= mysqli_query_bd("SELECT `wallet`, `ref1`, `ref2`, `ref3`, `noda`, `nodause`, `balance`, `date`, `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE  `view`>0 and ".$where." ORDER BY ".$order." LIMIT ".$start.",".$limit.";");
			while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
				$sqltbl_arr['percent']= strval((int)($sqltbl_arr['balance']*pow_percent($GLOBALS['percent_main'], $GLOBALS['percent_last'], $GLOBALS['percent_old'], $GLOBALS['date_synch'], $sqltbl_arr['date'], ($sqltbl_arr['noda']?1.25:1))));
				$json_arr['referralwallets'][]= $sqltbl_arr;
			}
		}
	}
  if(!isset($json_arr['referralwallets']) || !$json_arr['referralwallets'] || !isset($referrals_count)){
	  $json_arr['referralwallets']['referralwallets']='not_found_referral_wallets_this_noda';
  } else {
	  if(isset($referrals_count))$json_arr['referralwallets']['count']= $referrals_count;
	  $json_arr['referralwallets'] = array_values($json_arr['referralwallets']);
  }
  $stop=1;
} else
if($stop!=1 && $request['type']=="referralresults"){
	delay_now();
	if((isset($request['ref']) && strlen($request['ref'])==18) || (isset($request['ref1']) && strlen($request['ref1'])==18) || (isset($request['ref2']) && strlen($request['ref2'])==18)|| (isset($request['ref3']) && strlen($request['ref3'])==18)){
		$where= "`wallet`!=''";
		if(isset($request['ref']) && strlen($request['ref'])==18){
			$request['ref1']=$request['ref'];
			$request['ref2']=$request['ref'];
			$request['ref3']=$request['ref'];
		}
		if(isset($request['ref1']) && strlen($request['ref1'])==18){
			query_bd("SELECT count(*) as count,sum(`balance`) as balance FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `ref1`= '".$request['ref1']."' LIMIT 1;");
			if($sqltbl['count']>0){
				$json_arr['referralresults']['count1']= $sqltbl['count'];
				$json_arr['referralresults']['balance1']= $sqltbl['balance'];
			}
		}
		if(isset($request['ref2']) && strlen($request['ref2'])==18){
			query_bd("SELECT count(*) as count,sum(`balance`) as balance FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `ref2`= '".$request['ref2']."' LIMIT 1;");
			if($sqltbl['count']>0){
				$json_arr['referralresults']['count2']= $sqltbl['count'];
				$json_arr['referralresults']['balance2']= $sqltbl['balance'];
			}
		}
		if(isset($request['ref3']) && strlen($request['ref3'])==18){
			query_bd("SELECT count(*) as count,sum(`balance`) as balance FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `ref3`= '".$request['ref3']."' LIMIT 1;");
			if($sqltbl['count']>0){
				$json_arr['referralresults']['count3']= $sqltbl['count'];
				$json_arr['referralresults']['balance3']= $sqltbl['balance'];
			}
		}
	}
  if(!isset($json_arr['referralresults']) || !$json_arr['referralresults'])$json_arr['referralresults']['referralresults']='not_found_wallets_this_noda';
  $stop=1;
} else
if($stop!=1 && ($request['type']=="synch" || $request['type']=="send")){
	usleep(mt_rand(0.01,0.5)*1000000);
  ignore_user_abort(1);
  set_time_limit(120);
  $skip=0;
  if($request['type']=="synch" || (!file_exists($GLOBALS['filename_temp_synch']) && (int)date("s",$json_arr['time'])>5)){
		$result= mysqli_query_bd("SELECT * FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` WHERE `name` IN ('synch_now','synch_wallet','check_wallet');");
		while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
		  if($sqltbl_arr['name']=='synch_now' && date("Y-m-d H:i",$sqltbl_arr['value'])==date("Y-m-d H:i",$json_arr['time'])){
			if($request['type']!="send"){echo '{"synch":"now"}';exit_now();}
			else $skip=1;
		  } else if($sqltbl_arr['name']=='synch_wallet')$synch_wallet= $sqltbl_arr['value'];
				else if($sqltbl_arr['name']=='check_wallet'){
					query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `view`= 3 LIMIT 1;");
					if(isset($sqltbl['count'])){
						$check_wallet_count= mt_rand((int)($limit_synch/10),(int)($limit_synch/3));
						if($sqltbl['count']+$check_wallet_count> $limit_synch)$check_wallet_count= $limit_synch - $sqltbl['count'];
						if($check_wallet_count>0){
							query_bd("SELECT `wallet` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`>=".$sqltbl_arr['value']." ORDER BY `wallet` ASC LIMIT ".$check_wallet_count.",1;");
							if(isset($sqltbl['wallet']) && $sqltbl['wallet']>0){
								$max_wallet= $sqltbl['wallet'];
								query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `view`= 3 WHERE `wallet` >= '".$sqltbl_arr['value']."' and `wallet` < '".$max_wallet."' and `view`=1;");
								query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` SET `value`= '".$max_wallet."' WHERE `name`= 'check_wallet';");
							} else query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` SET `value`= 1 WHERE `name`= 'check_wallet';");
						}
					}
				}
		}
	} else $skip=1;
	if($skip!=1){
		query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_settings` SET `value`= '".$GLOBALS['date_synch']."' WHERE `name`= 'synch_now';");
		foreach(glob($GLOBALS['dir_temp']."/*") as $file){if(file_exists($file)){
			if(strpos($file, '/backup') === FALSE){
				if(time()-@filectime($file)>5 && $file!=$dir_temp_index)@unlink($file);
				else if((time()-@filectime($file)>2 && (strpos($file, '/walletscount_') !== FALSE || strpos($file, '/balanceall_') !== FALSE)) || strpos($file, '/nodas') !== FALSE)@unlink($file);
			} else if(time()-@filectime($file)>=7200)@unlink($file);
		}}
		$walletscount= 0;
		query_bd("SELECT count(*) as walletscount, SUM(`balance`) as balanceall FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `view`>0 LIMIT 1;");
		if(isset($sqltbl['balanceall']) && !file_exists($GLOBALS['dir_temp'].'/balanceall_'.$sqltbl['balanceall']))file_put_contents($GLOBALS['dir_temp'].'/balanceall_'.$sqltbl['balanceall'], "");
		if(isset($sqltbl['walletscount']) && !file_exists($GLOBALS['dir_temp'].'/walletscount_'.$sqltbl['walletscount'])){
			file_put_contents($GLOBALS['dir_temp'].'/walletscount_'.$sqltbl['walletscount'], "");
			$walletscount= $sqltbl['walletscount'];
			if((!isset($fast_first_synch_bd) || $fast_first_synch_bd==1) && $walletscount<1000 && !file_exists($GLOBALS['dir_temp'].'/backup_start.sql.gz') && isset($noda_trust) && count($noda_trust)>=1){
				exec('wget -bqc -N -O \''.$GLOBALS['dir_temp'].'/backup_start.sql.gz\' \'http://'.$noda_trust[mt_rand(0,count($noda_trust)-1)].'/egold_temp/backup.log\' || rm -f \''.$GLOBALS['dir_temp'].'/backup_start.sql.gz\'');
			}
		}
		if(!file_exists($GLOBALS['filename_temp_synch']))file_put_contents($GLOBALS['filename_temp_synch'], "");
		wallet_check();
		$checkbalancenodatime=0;
		$noda_balance_noda_ip=1;
		$nodas_balance_sum_bd=0;
		$result= mysqli_query_bd("SELECT `noda`, SUBSTRING_INDEX(GROUP_CONCAT(CEILING(`balance`) ORDER BY `date` DESC), ',', 1) as balance, MAX(`checknoda`) as checknoda FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`!='' and (`noda`= '".$noda_ip."' or `checknoda`='' or `checknoda`<= ".$GLOBALS['date_synch'].") and `balance`>=100 and `view`>0 GROUP BY `noda` ORDER BY `noda`= '".$noda_ip."' DESC, `checknoda` LIMIT 33;");
		while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
			if($sqltbl_arr['noda']!=$noda_ip){
				$noda_balance[$sqltbl_arr['noda']]= $sqltbl_arr['balance'];
				if(!isset($checkbalancenodatime) || $checkbalancenodatime< $sqltbl_arr['checknoda'])$checkbalancenodatime= $sqltbl_arr['checknoda']-1;
			} else $noda_balance_noda_ip= (int)$sqltbl_arr['balance'];
		}
		$result= mysqli_query_bd("SELECT `noda` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`!='' and `noda`!='".$noda_ip."' and `view`>0 and (`checknoda`='' or `checknoda`<= ".$GLOBALS['date_synch'].") and `balance`>=100000 and `balance`>".(int)($noda_balance_noda_ip/2)." ORDER BY `checknoda` LIMIT 33;");
		while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))if($sqltbl_arr['noda']!=$noda_ip)$noda_balance_history_synch[]= $sqltbl_arr['noda'];
		timer(5);
		history_synch();
		delay_now();
		if(isset($email_domain) && function_exists('mail') && $email_domain && isset($email_limit) && (int)$email_limit>0 && isset($email_delay) && (float)$email_delay>0){
			if($email_limit*$email_delay>15)$email_limit= (int)(15/$email_delay);
			$result= mysqli_query_bd("SELECT * FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` WHERE `nodatrue`=1 and `email`!='' ORDER BY `date` ASC;");
			while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
				if(!isset($date_limit) || $date_limit<$sqltbl_arr['date'])$date_limit= $sqltbl_arr['date'];
				$user_mail[$sqltbl_arr['wallet']]=$sqltbl_arr;
			}
			if(isset($user_mail) && isset($date_limit)){
				$result= mysqli_query_bd("SELECT `wallet`,`height`,`recipient`,`money`,`nodause`,`date`,`checkemail` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `date`>".$json_arr['time']."-7*60 and `wallet`!=1 and `checkhistory`=1 and `checkemail`!=3 ORDER BY `date` ASC, `wallet` ASC, `height` ASC LIMIT 100;");
				while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))$user_mail_wallet[$sqltbl_arr['wallet']."_".$sqltbl_arr['height']]= $sqltbl_arr;
				if(isset($user_mail_wallet) && $user_mail_wallet && is_array($user_mail_wallet)){
					function send_mail($TO_EMAIL,$subject,$message){
						global $email_domain,$email_delay;
						if($email_delay>=1)mysqli_connect_close();
						usleep((float)$email_delay*1000000);
						$fromUserName = "eGOLD";
						$fromUserEmail= "egold@".$email_domain;
						$ReplyToEmail = $fromUserEmail;
						$subject = "=?utf-8?b?" . base64_encode($subject) . "?=";
						$from = "=?utf-8?B?" . base64_encode($fromUserName) . "?= <" . $fromUserEmail . ">";
						$headers = "From: " . $from . "\r\nReply-To: " . $ReplyToEmail . "\"";
						$headers .= "\r\nContent-Type: text/html; charset=\"utf-8\"";
						if(@mail($TO_EMAIL, $subject, $message, $headers)) $return_temp= 1;
						else $return_temp= 0;
						mysqli_connect_open();
						return $return_temp;
					}
					$email_limit_up= (int)$email_limit;
					$email_limit_down= (int)$email_limit;
					if(isset($user_mail_wallet) && $user_mail_wallet && is_array($user_mail_wallet)){
						foreach ($user_mail_wallet as $key => $value) {
							if(!($email_limit_up>0) && !($email_limit_down>0))break;
							if($email_limit_up>0 && isset($user_mail[$value['wallet']]['email']) && $value['checkemail']!=3 && $value['checkemail']!=1){
								if($user_mail[$value['wallet']]['up']>0 && $user_mail[$value['wallet']]['up']<=$value['money']){
									$money_value= number_format($value['money'], 0, '.', ' ');
									$subject= "-".$money_value." | ".($value['recipient']==1?'eGOLD':gold_wallet_view($value['recipient']))." < ".substr(gold_wallet_view($value['wallet']),0,6);
									$message= "<b>-".$money_value." | <a href='http://".$noda_ip."/egold.php?type=history&history=".$value['recipient']."' target='_blank'>".($value['recipient']==1?'eGOLD':gold_wallet_view($value['recipient']))."</a> < <a href='http://".$noda_ip."/egold.php?type=history&history=".$value['wallet']."' target='_blank'>".gold_wallet_view($value['wallet'])."</a> | ".date("Y-m-d H:i:s",$value['date'])."</b>";
									if(send_mail($user_mail[$value['wallet']]['email'],$subject,$message)==1)$email_limit_up--;
								}
								$value['checkemail']= ($value['checkemail']==2?3:1);
								query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkemail`=".$value['checkemail']." WHERE `wallet`= '".$value['wallet']."' and `height`= '".$value['height']."' and `checkhistory`=1;");
							}
							if($email_limit_down>0 && isset($user_mail[$value['recipient']]['email']) && $value['checkemail']!=3 && $value['checkemail']!=2){
								if($user_mail[$value['recipient']]['down']>0 && $user_mail[$value['recipient']]['down']<=$value['money']){
									$money_value= number_format($value['money'], 0, '.', ' ');
									$subject= "+".$money_value." | ".gold_wallet_view($value['wallet'])." > ".substr(gold_wallet_view($value['recipient']),0,6);
									$message= "<b>+".$money_value." | <a href='http://".$noda_ip."/egold.php?type=history&history=".$value['wallet']."' target='_blank'>".gold_wallet_view($value['wallet'])."</a> > <a href='http://".$noda_ip."/egold.php?type=history&history=".$value['recipient']."' target='_blank'>".gold_wallet_view($value['recipient'])."</a> | ".date("Y-m-d H:i:s",$value['date'])."</b>";
									if(send_mail($user_mail[$value['recipient']]['email'],$subject,$message)==1)$email_limit_down--;
								}
								$value['checkemail']= ($value['checkemail']==1?3:2);
								query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `checkemail`=".$value['checkemail']." WHERE `wallet`= '".$value['wallet']."' and `height`= '".$value['height']."' and `checkhistory`=1;");
							}
						}
					}
				}
			}
		}
		delay_now();
		$wallet_check_del= [];
		query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE (`checkhistory`= 0 or `checkhistory`=2 or `checkhistory`=9) and `date`<".($GLOBALS['date_synch']-3*60).";");
		if($date_m==51){
			query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `date`< ".$GLOBALS['date_synch']." - ".$GLOBALS['history_day_sec'].";");
			if(mysqli_affected_rows($mysqli_connect)>=1)delay_now();
			if(isset($history_size) && (int)$history_size>=1){
				query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history`;");
				if(isset($sqltbl['count']) && $sqltbl['count']>$history_size){
					$history_size= (int)$history_size;
					$history_size_extra= $sqltbl['count']-$history_size;
					query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `date`< ".$GLOBALS['date_synch']."-86400 ORDER by `date` ASC ".($history_size_extra>0?"LIMIT ".$history_size_extra:'').";");
					if(mysqli_affected_rows($mysqli_connect)>=1)delay_now();
				}
			}
			query_bd("SELECT `date` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` ORDER by `date` ASC LIMIT 1;");
			if(isset($sqltbl['date']))query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_referrals` WHERE `date`< '".$sqltbl['date']."';");
			query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` WHERE `date`< ".$GLOBALS['date_synch']."-365*86400;");
			if(mysqli_affected_rows($mysqli_connect)>=1){
				delay_now();
				query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_contacts` WHERE `wallet` NOT IN (SELECT DISTINCT(`wallet`) FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users`);");
			}
			$result= mysqli_query_bd("SELECT `wallet`,`height`,`details` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`!=1 and `details`!='' and `date`<".($GLOBALS['date_synch']-7*86400).";");
			while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
				query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`='".$sqltbl_arr['wallet']."' and `height`='".$sqltbl_arr['height']."' and `details`='' LIMIT 1;");
				if(isset($sqltbl['height']))query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `wallet`='".$sqltbl_arr['wallet']."' and `height`='".$sqltbl_arr['height']."';");
			}
			query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` SET `details`='' WHERE `wallet`!=1 and `details`!='' and `date`<".($GLOBALS['date_synch']-7*86400).";");
			query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `nodacheckwallets`='' WHERE `nodacheckwallets`!='' and `nodacheckwallets`< '".($GLOBALS['date_synch']-2*60)."';");
		} else if($date_m==4 || $date_m==19 || $date_m==34 || $date_m==49){
			$result= mysqli_query_bd("SELECT `wallet`, `money`, `height`, `nodawallet`, `nodause`, `date` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `details`!='' and `date`<".($GLOBALS['date_synch']-3*86400)." and `checkhistory`=1 and `pin`<1000000000000 and `wallet`!=1 and `recipient`=1 ORDER BY `date`,`wallet`,`height` LIMIT 10000;");
			while($sqltbl_arr_deals_close= mysqli_fetch_array($result,MYSQLI_ASSOC))deals_close($sqltbl_arr_deals_close,0);
		} else if($date_m==9 || $date_m==24 || $date_m==39 || $date_m==54){
			$result= mysqli_query_bd("SELECT `wallet`, `height`, `money`, `pin`, `details`, `date` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `details` LIKE '1|%' and `date`<".($GLOBALS['date_synch']-86400)." and `checkhistory`=1 and `pin`>=100000000000000000 and `wallet`!=1 and `recipient`=1 ORDER BY `date`,`wallet`,`height` LIMIT 10000;");
			while($sqltbl_arr_deals_cancel= mysqli_fetch_array($result,MYSQLI_ASSOC))deals_cancel($sqltbl_arr_deals_cancel,0);
		}
		query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `noda`='', `nodaping`='', `view`=IF(`view`=1,3,`view`) WHERE `noda`!='' and `balance`<100;");
		if($date_m==21)query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `noda`='', `nodaping`='', `view`=IF(`view`=1,3,`view`) WHERE `noda`!='' and `date`< ".$GLOBALS['date_synch']."-21600 and `noda` NOT IN (SELECT * FROM (SELECT `nodause` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `nodause`!='' and `date`>= ".$GLOBALS['date_synch']."-14*86400 GROUP BY `nodause`) as t);");
		$checkwallets= [];
		wallet_synch();
		if($nodas_balance_sum_bd>0 && $noda_balance_noda_ip>0 && (0.9*$nodas_balance_sum_bd-$noda_balance_noda_ip)>0){
			$result= mysqli_query_bd("SELECT * FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `view`=0 and ((`signpub`='' and `date`<= ".$GLOBALS['date_synch'].") or (`checkbalanceall`!='' and `checkbalanceall`>= ".(0.9*$nodas_balance_sum_bd-$noda_balance_noda_ip)." and (`checkbalance`='' or `checkbalance`<= 0.5*`checkbalanceall`)));");
			while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
				if($sqltbl_arr['signpub'] || $sqltbl_arr['date']>1000){
					query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`= '".$sqltbl_arr['wallet']."';");
				} else {
					query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `date`= ".$GLOBALS['date_synch']." WHERE `wallet`= '".$sqltbl_arr['wallet']."';");
				}
				$wallet_check_del[$sqltbl_arr['wallet']]= 1;
			}
		}
		$result= mysqli_query_bd("SELECT * FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `date`< ".$GLOBALS['date_synch']."-365*86400 and (`balance`<10 or `date`< ".$GLOBALS['date_synch']."-10*365*86400) and `signpub`!='';");
		while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC)){
			query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`= '".$sqltbl_arr['wallet']."';");
			if(mysqli_affected_rows($mysqli_connect)>=1){
				query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `ref1`= 0 WHERE `ref1`= '".$sqltbl_arr['wallet']."';");
				query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `ref2`= 0 WHERE `ref2`= '".$sqltbl_arr['wallet']."';");
				query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `ref3`= 0 WHERE `ref3`= '".$sqltbl_arr['wallet']."';");
				$wallet_check_del[$sqltbl_arr['wallet']]= 1;
			}
		}
		query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `date`>= ".$GLOBALS['date_synch']."-3*86400-3600 and `date`< ".$GLOBALS['date_synch']."-3*86400 and `height`= 0 and `balance`<= 3 and `signpub`!='';");
		history_synch_old();
		timer(25);
		history_synch();
		$checkbalancenodatime++;
		wallet_synch();
		timer(45);
		history_synch();
		if(isset($checkwallets) && is_array($checkwallets) && count($checkwallets)>=1 && isset($noda_balance) && count($noda_balance)>=5 && count($checkwallets)<count($noda_balance)/2){
			foreach($checkwallets as $key1 => $value1)if(!$value1)unset($checkwallets[$key1]);
			if(isset($checkwallets) && is_array($checkwallets) && count($checkwallets)>=1){
				$json= connect_noda_multi(array_keys($checkwallets),'',$checkwallets,5,1);
				query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `nodaping`=IF(`nodaping`!='',`nodaping`,0)+IF(`nodaping`='' or `nodaping`<1440,3,0),`checknoda`=".$GLOBALS['date_synch']."+IF(`nodaping`!='',`nodaping`,1)*60 WHERE `noda` IN ('".implode("','",array_keys($checkwallets))."') and `noda`!= '".$noda_ip."';");
			}
		}
		query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `nodaping`=IF(`nodaping`!='' and `nodaping`>1,`nodaping`-1,'') WHERE `nodaping`!='' and (`checknoda`='' or `checknoda`<= ".$GLOBALS['date_synch'].");");
		if(isset($walletscount) && $walletscount>=1000 && (!file_exists($GLOBALS['dir_temp'].'/backup.log') || (mt_rand(1,5)==1 && time()-@filemtime($GLOBALS['dir_temp'].'/backup.log')>=3600))){
			exec('mysqldump --insert-ignore --complete-insert --tables --compact --no-create-info -u'.$GLOBALS['user_db'].' -h'.$GLOBALS['host_db'].' -p'.$GLOBALS['password_db'].' '.$GLOBALS['database_db'].' '.$GLOBALS['prefix_db'].'_wallets |\sed -e \'s/`'.$GLOBALS['prefix_db'].'_wallets`/`eGOLD_wallets`/\'| gzip > \''.$GLOBALS['dir_temp'].'/backup.sql.gz\' | mv \''.$GLOBALS['dir_temp'].'/backup.sql.gz\' \''.$GLOBALS['dir_temp'].'/backup.log\'');
			if(file_exists($GLOBALS['dir_temp'].'/backup.log'))exec('chmod 766 \''.$GLOBALS['dir_temp'].'/backup.log\'');
		} else if((!isset($fast_first_synch_bd) || $fast_first_synch_bd==1) && isset($walletscount) && $walletscount<1000 && file_exists($GLOBALS['dir_temp'].'/backup_start.sql.gz')){
			if(!(filesize($GLOBALS['dir_temp'].'/backup_start.sql.gz')>25000))@unlink($GLOBALS['dir_temp'].'/backup_start.sql.gz');
			else if(time()-@filemtime($GLOBALS['dir_temp'].'/backup_start.sql.gz')>3){
				exec('gunzip -c \''.$GLOBALS['dir_temp'].'/backup_start.sql.gz\' |\sed -e \'s/`eGOLD_wallets`/`'.$GLOBALS['prefix_db'].'_wallets`/\'| mysql -u'.$GLOBALS['user_db'].' -h'.$GLOBALS['host_db'].' -p'.$GLOBALS['password_db'].' '.$GLOBALS['database_db']);
				@unlink($GLOBALS['dir_temp'].'/backup_start.sql.gz');
				query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `checkbalance`='', `checkbalanceall`='', `checkwallet`='', `view`=1, `checknoda`='', `nodaping`='', `nodacheckwallets`='' WHERE `signpub`!='' and `signpub`>0;");
			}
		}
		if(!isset($json_arr['synch']))$json_arr['synch']= 'true';
	}
  ignore_user_abort(0);
}
if($stop!=1 && $request['type']=="send" && isset($request['wallet']) && isset($request['recipient']) && isset($request['money']) && isset($request['pin']) && isset($request['height']) && isset($request['signpub']) && isset($request['sign'])){
  ignore_user_abort(1);
  set_time_limit(30);
  if($request['wallet']==$request['recipient'])$json_arr['recipient']= 'false';
  else {
    $wallet= wallet($request['wallet'],(isset($request['date'])?$request['date']:$json_arr['time']),0,0,0);
    if(!isset($wallet['wallet']))$json_arr['wallet']= 'false';
    else if($wallet['height']>=$request['height'])$json_arr['height']= 'false';
    else if($json_arr['send_noda']!=1 && $wallet['balance']+$wallet['percent_4']< $request['money']+2)$json_arr['balance']= $wallet['balance']+$wallet['percent_4'];
    else if($wallet['view']==0 || ($wallet['view']==2 && $json_arr['send_noda']!=1))$json_arr['wallet']= 'synch';
		else{
			if($request['wallet']!=$noda_wallet || !($request['recipient']==1 && $request['money']==100 && $request['pin']==preg_replace("/[^0-9]/",'',$noda_ip))){
				query_bd("SELECT `wallet` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`= '".$noda_ip."' ORDER BY `date` DESC LIMIT 1;");
				if(!isset($sqltbl['wallet'])){echo '{"noda":"not_activated"}';exit_now();}
			}
			if($json_arr['send_noda']!=1){
				query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `date`>= ".$json_arr['time']."-3 and `date`<= ".$json_arr['time']."+3 and `wallet`= '".$request['wallet']."' LIMIT 1;");
				if(isset($sqltbl['height'])){
					$json_arr['date']= 'false';
					$stop=1;
				}
				if($stop!=1 && isset($request['details']) && $request['details'] && $request['recipient']==1 && strlen($request['pin'])==18){
					$details_arr= explode('|',$request['details']);
					if(is_array($details_arr) && (count($details_arr)==2 || (count($details_arr)==3 && strlen($details_arr[2])==strlen((int)$details_arr[2]) && strlen($details_arr[2])==18 && $request['wallet']!=$details_arr[2])) && strlen($details_arr[0])==strlen((int)$details_arr[0]) && $details_arr[0]>0 && strlen($details_arr[1])==strlen((int)$details_arr[1]) && strlen($details_arr[1])>0){
						$type_action= (int)$details_arr[0];
						$details_check= (int)$details_arr[1];
						if((count($details_arr)==2 && $type_action!=3 && $type_action!=6) || (count($details_arr)==3 && ($type_action==3 || $type_action==4 || ($type_action==6 && $request['pin']==$details_arr[2])))){
							if(isset($request['details']) && $request['details'] && isset($request['pin']) && strlen($request['pin'])==18){
								query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE `details`='".mysqli_real_escape_string($mysqli_connect,$request['details'])."' and `pin`='".$request['pin']."' and `recipient`=1 and `checkhistory`!=2 LIMIT 1;");
								if(isset($sqltbl['height'])){
									$json_arr['transaction']= 'double';
									$stop=1;
								}
							}
							if($stop!=1){
								query_bd("SELECT `height`,`checkhistory`,`details` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_history` WHERE (`details` LIKE '%|".$details_check."%' and `details` NOT LIKE '3|".$details_check."%') and `pin`='".$request['pin']."' and `recipient`=1 and `checkhistory`=0 LIMIT 1;");
								if(isset($sqltbl['height'])){
									$json_arr['transaction']= 'wait';
									$stop=1;
								}
							}
						}
					}
				}
			}
			if($stop!=1)send($request,$wallet);
			if(isset($json_arr['send']) && $json_arr['send']== 'true'){
				if(isset($request['password']) && $request['nodause']==$noda_ip){
					if(strlen($request['password'])==128){
					query_bd("SELECT `wallet`,`password` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` WHERE `wallet`= '".$wallet['wallet']."';");
					if(isset($sqltbl['wallet'])){
						if($sqltbl['password']!=$request['password']){
							query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_contacts` WHERE `wallet`= '".$wallet['wallet']."';");
							query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` SET `password`= '".$request['password']."', `nodatrue`=1, `date`= '".$json_arr['time']."' WHERE `wallet`= '".$wallet['wallet']."';");
						} else $json_arr['password']= 'true';
					} else query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` SET `wallet`= '".$wallet['wallet']."', `password`= '".$request['password']."', `nodatrue`=1, `date`= '".$json_arr['time']."';");
					if(mysqli_affected_rows($mysqli_connect)>=1)$json_arr['password']= 'true';
					else $json_arr['password']= 'false';
					} else $json_arr['password']= 'false';
				}
			}
    }
  }
  if(!isset($json_arr['send']))$json_arr['send']= 'false';
  ignore_user_abort(0);
  $stop=1;
} else
if($stop!=1 && $request['type']=="synchwallets"){
	delay_now();
  ignore_user_abort(1);
  set_time_limit(10);
  $limit=$limit_synch;
  if(isset($_POST['wallets'])){
    $wallets_temp= json_decode($_POST['wallets'],true);
    if(is_array($wallets_temp) && count($wallets_temp)>0){
      array_splice($wallets_temp, $limit);
      foreach($wallets_temp as $key => $value)if((int)$value==$value && strlen($value)==18)$wallets[]=$value;
      if(isset($wallets)){
        $result= mysqli_query_bd("SELECT `wallet`, `ref1`, `ref2`, `ref3`, `noda`, `nodause`, `balance`, `date`, `percent_ref`, `height`, `signpub`, `view` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet` IN ('".implode("','",$wallets)."') and (`view`=1 or `view`=3) ORDER BY `date`,`wallet` LIMIT ".$limit.";");
        while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))$json_arr['synchwallets'][$sqltbl_arr['wallet']]= $sqltbl_arr;
				usleep(mt_rand(0.0001,0.1)*1000000);
				$walletadd_check_file_temp= $GLOBALS['dir_temp']."/walletsadd";
				if((($date_s>5 && $date_s<19) || ($date_s>25 && $date_s<39) || $date_s>45) && (!file_exists($walletadd_check_file_temp) || (time()-@filectime($walletadd_check_file_temp)>=15 && is_writable($walletadd_check_file_temp)))){
					$wallets_add= [];
					foreach($wallets as $key => $value){
						if(!isset($json_arr['synchwallets'][$value]))$wallets_add[]= $value;
						if(count($wallets_add)>$limit_synch/9)break;
					}
					if(count($wallets_add)>0){
						if(!file_exists($walletadd_check_file_temp) || is_writable($walletadd_check_file_temp))file_put_contents($walletadd_check_file_temp, "");
						query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `view`=0 and `date`<= ".$GLOBALS['date_synch']." LIMIT 1;");
						if(!isset($sqltbl['count']) || $sqltbl['count']<=$limit_synch/9){
							delay_now();
							if(!file_exists($walletadd_check_file_temp) || (time()-@filectime($walletadd_check_file_temp)>=15 && is_writable($walletadd_check_file_temp))){
								query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` (`wallet`) VALUES ('".implode("'),('",$wallets_add)."');");
							}
						}
					}
				}
      }
    }
  }
  if(isset($request['synch_wallet']) && $request['synch_wallet']>=0){
    if($limit>0 && isset($json_arr['synchwallets']) && count($json_arr['synchwallets'])>0) $limit= $limit-count($json_arr['synchwallets']);
    if($limit<=0)$limit=1;
    $result= mysqli_query_bd("SELECT `wallet`, `ref1`, `ref2`, `ref3`, `noda`, `nodause`, `balance`, `date`, `percent_ref`, `height`, `signpub`, `view` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `wallet`>'".$request['synch_wallet']."' ".(isset($json_arr['synchwallets']) && count($json_arr['synchwallets'])>0?"and `wallet` NOT IN ('".implode("','",array_keys($json_arr['synchwallets']))."')":'')." and `view`>0 and `date`< UNIX_TIMESTAMP()-60 ORDER BY `wallet` LIMIT ".$limit.";");
    while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))$json_arr['synchwallets'][$sqltbl_arr['wallet']]= $sqltbl_arr;
  }
  ignore_user_abort(0);
  $stop=1;
} else
if($stop!=1 && $request['type']=="checkwallets"){
  if($date_s>=45 && isset($_POST['wallets'])){
		usleep(mt_rand(0.001,1)*1000000);
		$walletscheck_check_file_temp= $GLOBALS['dir_temp']."/walletscheck";
		if(!file_exists($walletscheck_check_file_temp) || (time()-@filectime($walletscheck_check_file_temp)>=2 && is_writable($walletscheck_check_file_temp))){
			ignore_user_abort(1);
			set_time_limit(10);
			$checkwallets= json_decode($_POST['wallets'],true);
			if(isset($checkwallets) && is_array($checkwallets) && count($checkwallets)>= 1){
				foreach($checkwallets as $key => $value){
					$value_temp= preg_replace("/[^0-9]/",'',$value);
					if(strlen($value_temp)!= 18 || $value_temp!=preg_replace("/[^0-9]/",'',$value))unset($checkwallets[$key]);
				}
			}
			if(isset($checkwallets) && is_array($checkwallets) && count($checkwallets)>= 1){
				if(!file_exists($walletscheck_check_file_temp) || is_writable($walletscheck_check_file_temp))file_put_contents($walletscheck_check_file_temp, "");
				query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `nodacheckwallets`='".$GLOBALS['date_synch']."' WHERE `noda`= '".$host_ip."' and (`nodacheckwallets`='' or `nodacheckwallets`< '".$GLOBALS['date_synch']."');");
				if(mysqli_affected_rows($mysqli_connect)>=1){
					query_bd("SELECT count(*) as count FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `view`= 3 LIMIT 1;");
					if(isset($sqltbl['count']) && $sqltbl['count']<= $limit_synch/2){
						if($sqltbl['count']> $limit_synch/3){
							query_bd("SELECT (`balance`+`percent_ref`) as balance FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`= '".$host_ip."' LIMIT 1;");
							if(isset($sqltbl['balance']) && $sqltbl['balance']>= 100){
								query_bd("SELECT `height` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` WHERE `noda`= '".$noda_ip."' and (`balance`+`percent_ref`)<= ".(5*$sqltbl['balance'])." LIMIT 1;");
								if(!isset($sqltbl['height']))$stop=1;
							} else $stop=1;
						}
						if($stop!=1){
							$checkwallets= random($checkwallets,ceil($limit_synch/30));
							query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_wallets` SET `view`=3 WHERE `wallet` IN ('".implode("','",$checkwallets)."') and `view`=1 LIMIT 1;");
						}
					}
				}
			}
			$json_arr['checkwallets']['synch']= date('Y:m:d H:i:s', $json_arr['time']);
			ignore_user_abort(0);
		}
  }
  $stop=1;
} else
if($stop!=1 && $request['type']=="contacts" && isset($request['wallet']) && isset($request['password'])){
  query_bd("SELECT `password` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` WHERE `wallet`= '".$request['wallet']."' and `nodatrue`=1 LIMIT 1;");
  if(isset($sqltbl['password']) && gen_sha3($sqltbl['password'],256)==$request['password']){
    ignore_user_abort(1);
    set_time_limit(10);
    if(isset($_POST['contacts'])){
      query_bd("DELETE FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_contacts` WHERE `wallet`= '".$request['wallet']."';");
      $json_arr['contact']= 'del';
      $_POST['contacts']= json_decode($_POST['contacts'],true);
      if(count($_POST['contacts'])>=1 && count($_POST['contacts'])<=100){
        delay_now();
				$index=1;
        foreach($_POST['contacts'] as $key => $value){
          if(isset($key) && preg_replace("/[^0-9a-zA-Z]/",'',$key)==$key
					&& mysqli_real_escape_string($mysqli_connect,$key)==$key && strlen($key)<=255
					&& isset($value) && preg_replace("/[^0-9a-zA-Z]/",'',$value)==$value
					&& mysqli_real_escape_string($mysqli_connect,$value)==$value && strlen($value)<=255
          ){
            query_bd("INSERT IGNORE INTO `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_contacts` SET `wallet`= '".$request['wallet']."',`number`= '".$index."', `recipient`= '".$key."', `name`= '".$value."';");
            if(mysqli_affected_rows($mysqli_connect)==1){
							$json_arr['contact']= 'save';
							$index++;
            } else {$json_arr['contact']= 'false';break;}
          }
        }
      }
    } else {
				$result= mysqli_query_bd("SELECT `recipient`,`name` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_contacts` WHERE `wallet`= '".$request['wallet']."' ".(isset($request['recipient'])?"and `recipient`= '".$request['recipient']."'":'')." ORDER BY `number` LIMIT 100;");
        while($sqltbl_arr= mysqli_fetch_array($result,MYSQLI_ASSOC))$json_arr['contacts'][]= $sqltbl_arr;
        if(!isset($json_arr['contacts']))$json_arr['contacts']['contacts']='not_found_contacts_this_noda';
				else $json_arr['contacts']= array_values($json_arr['contacts']);
    }
    ignore_user_abort(0);
  }
  $stop=1;
} else
if($stop!=1 && $request['type']=="email" && isset($request['wallet']) && strlen($request['wallet'])==18 && isset($request['password'])){
    query_bd("SELECT `password` FROM `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` WHERE `wallet`= '".$request['wallet']."' and `nodatrue`=1 LIMIT 1;");
    if(isset($sqltbl['password']) && gen_sha3($sqltbl['password'],256)==$request['password']){
      ignore_user_abort(1);
      set_time_limit(10);
      if(!isset($request['email'])){
        query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` SET `email`= '', `up`= '0', `down`= '0', `date`= '".$json_arr['time']."' WHERE `wallet`= '".$request['wallet']."' LIMIT 1;");
        $json_arr['emailwallet']= 'del';
      } else {
        query_bd("UPDATE `".$GLOBALS['database_db']."`.`".$GLOBALS['prefix_db']."_users` SET `email`= '".$request['email']."', `up`= '".(isset($request['up'])?$request['up']:'0')."', `down`= '".(isset($request['down'])?$request['down']:'0')."' WHERE `wallet`= '".$request['wallet']."' LIMIT 1;");
        $json_arr['emailwallet']= 'save';
      }
      ignore_user_abort(0);
    }
  $stop=1;
}
if($json_arr){
  if(isset($json_arr['countconnect']))unset($json_arr['countconnect']);
  if(isset($json_arr['nodas_send']))unset($json_arr['nodas_send']);
  if(isset($json_arr['send_noda']))unset($json_arr['send_noda']);
  if(isset($json_arr['transaction_check']))unset($json_arr['transaction_check']);
  if(isset($json_arr['walletnew']))unset($json_arr['recipient']);
  if(isset($json_arr['history']))$print= json_encode($json_arr['history']);
  else if(isset($json_arr['referrals']))$print= json_encode($json_arr['referrals']);
  else if(isset($json_arr['referralwallets']))$print= json_encode($json_arr['referralwallets']);
  else if(isset($json_arr['referralresults']))$print= json_encode($json_arr['referralresults']);
  else if(isset($json_arr['contacts']))$print= json_encode($json_arr['contacts']);
  else if(isset($json_arr['nodas']))$print= json_encode($json_arr['nodas']);
  else if(isset($json_arr['checkwallets']))$print= json_encode($json_arr['checkwallets']);
  else $print= json_encode($json_arr);
  echo $print;
}
exit_now();
?>