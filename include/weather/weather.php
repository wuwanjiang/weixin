<?php
/**
 * Created by PhpStorm.
 * User: wwj
 * Date: 2018/1/6
 * Time: 13:53
 */
function getWeatherInfo($cityName)
{
    include("weather_cityId.php");
    $cityCode = $weather_cityId[$cityName];
    if ($cityCode == "")
    {
        return "";
    }
    //获取实时天气
    $url = "http://www.weather.com.cn/data/sk/".$cityCode.".html";
    $output = httpRequest($url);
    $weather = json_decode($output, true);
    $info = $weather['weatherinfo'];

    $weatherArray = array();
    $weatherArray[] = array("Title"=>$info['city']."天气预报", "Description"=>"", "PicUrl"=>"", "Url" =>"");
    if ((int)$cityCode < 101340000){
        $result = "实况 温度：".$info['temp']."℃ 湿度：".$info['SD']." 风速：".$info['WD'].$info['WSE']."级";
        $weatherArray[] = array("Title"=>str_replace("%", "﹪", $result), "Description"=>"", "PicUrl"=>"", "Url" =>"");
    }

    //获取六日天气
    $url = "http://m.weather.com.cn/data/".$cityCode.".html";
    $output = httpRequest($url);
    $weather = json_decode($output, true);
    $info = $weather['weatherinfo'];

    if (!empty($info['index_d'])){
        $weatherArray[] = array("Title" =>$info['index_d'], "Description" =>"", "PicUrl" =>"", "Url" =>"");
    }

    $weekArray = array("日","一","二","三","四","五","六");
    $maxlength = 3;
    for ($i = 1; $i <= $maxlength; $i++) {
        $offset = strtotime("+".($i-1)." day");
        $subTitle = date("m月d日",$offset)." 周".$weekArray[date('w',$offset)]." ".$info['temp'.$i]." ".$info['weather'.$i]." ".$info['wind'.$i];
        $weatherArray[] = array("Title" =>$subTitle, "Description" =>"", "PicUrl" =>"http://discuz.comli.com/weixin/weather/"."d".sprintf("%02u",$info['img'.(($i *2)-1)]).".jpg", "Url" =>"");
    }
    return $weatherArray;
}
function httpRequest($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    if ($output === FALSE){
        return "cURL Error: ". curl_error($ch);
    }
    return $output;
}