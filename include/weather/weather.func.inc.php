<?php
/**
 * 乐思乐享微信公众平台-天气预报功能源代码
 * ================================
 * Copyright 2013-2014 David Tang
 * 乐思乐享博客园
 * http://www.cnblogs.com/mchina/
 * 乐思乐享微信论坛
 * http://www.joythink.net/
 * ================================
 * Author:David|唐超
 * 个人微信：mchina_tang
 * 公众微信：zhuojinsz
 * Date:2013-10-12
 */

function weather($city_name){
	include("weather_cityId.php");
	$city_code = $weather_cityId[$city_name];
	if(!empty($city_code))
	{
		$json = file_get_contents("http://m.weather.com.cn/data/".$city_code.".html");
		$data = json_decode($json);
		
		if(empty($data->weatherinfo))
		{
			return "抱歉，没有查询到\"".$city_name."\"的天气信息！";
		}else{
			return "【".$data->weatherinfo->city."天气预报】\n".$data->weatherinfo->date_y." ".$data->weatherinfo->fchh."时发布"."\n\n实时天气\n".$data->weatherinfo->weather1." ".$data->weatherinfo->temp1." ".$data->weatherinfo->wind1."\n\n温馨提示：".$data->weatherinfo->index_d."\n\n明天\n".$data->weatherinfo->weather2." ".$data->weatherinfo->temp2." ".$data->weatherinfo->wind2."\n\n后天\n".$data->weatherinfo->weather3." ".$data->weatherinfo->temp3." ".$data->weatherinfo->wind3;
		}
	}else{
		return "请输入要查询天气的城市：如：北京、上海、苏州";
	}
}


?>