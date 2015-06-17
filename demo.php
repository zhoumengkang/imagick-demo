<?php
/**
 * description  : null
 * @author      : mengkang.zhou <zhoumengkang@php.net>
 * createTime   : 25/4/15 21:27
 */
include "/phpqrcode/phpqrcode.php";
define("FONT_PATH",__DIR__);

/**
 * 生成二维码
 * @param $id   活动的 id
 * @return string
 */
function createQRcode($url,$size = "l"){

    $tempDir = __DIR__."/image";
    $urlArray = explode("/",$url);
    // url  http://m.topit.me/#/tag/1
    // url  http://m.topit.me/#/profile/1
    $id = array_pop($urlArray);
    $type = array_pop($urlArray);

    $fileName = $id.'.png';
    $pngAbsoluteFilePath = $tempDir."/".$type."/".$fileName;

    if (!file_exists($pngAbsoluteFilePath)) {
        if($size == 'm'){
            QRcode::png($url, $pngAbsoluteFilePath,QR_ECLEVEL_M, 5);
        }else{
            QRcode::png($url, $pngAbsoluteFilePath,QR_ECLEVEL_L, 5);
        }
    }

    return $pngAbsoluteFilePath;
}

/**
 * 返回一个字符的数组
 *
 * @param $str      文字
 * @param $charset  字符编码
 * @return $match   返回一个字符的数组
 */

function charArray($str,$charset="utf-8"){

    $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";

    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";

    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";

    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";

    preg_match_all($re[$charset], $str, $match);

    return $match;

}



/**
 * 返回一个字符串在图片中所占的宽度
 * @param $fontsize  字体大小
 * @param $fontangle 角度
 * @param $ttfpath   字体文件
 * @param $char      字符
 * @return $width
 */

function charwidth($fontsize,$fontangle,$ttfpath,$char){

    $box = @imagettfbbox($fontsize,$fontangle,$ttfpath,$char);

    $width = max($box[2], $box[4]) - min($box[0], $box[6]);

    return $width;

}



/**
 * 根据预设宽度让文字自动换行
 * @param $fontsize   字体大小
 * @param $ttfpath    字体名称
 * @param $str    字符串
 * @param $width    预设宽度
 * @param $fontangle  角度
 * @param $charset    编码
 * @return $_string  字符串
 */

function autowrap($fontsize,$ttfpath,$str,$width,$fontangle=0,$charset='utf-8'){

    $_string = "";

    $_width  = 0;

    $temp    = chararray($str);

    foreach ($temp[0] as $v){
        $w = charwidth($fontsize,$fontangle,$ttfpath,$v);
        $_width += intval($w);

        if (($_width > $width) && ($v !== "")){
            $_string .= PHP_EOL;
            $_width = 0;
        }

        $_string .= $v;
    }

    return $_string;

}

function curl_get($url){
    $curl = curl_init();

    $header[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: "; // browsers keep this blank.

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 5);

    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

function createUserCard($username,$num,$desc,$id,$url){
    //error_reporting(E_ALL);
    //ini_set("display_errors","On");
    // 新建一个空白图片用来做画布
    $canvas = new Imagick;
    $canvasWidth = 588;
    $canvasHeight = 684;
    $canvas->newimage($canvasWidth, $canvasHeight, 'white');
    $canvas->setImageFormat('jpg');

    $fontFile = FONT_PATH."/msyh.ttf";
    $fontSize = 20;
    // 封面
    $face = new Imagick();
    $face->readimageblob(curl_get($url)); // 读取 url 里面的图片
    $face->cropThumbnailImage(200, 200);

    // 读取图片
    $pic = new Imagick;
    $QRcodeFile = createQRcode("http://m.topit.me/#/profile/".$id,"m");
    $pic->readImage($QRcodeFile);
    $codeWith = 256;
    $pic->cropThumbnailImage($codeWith, $codeWith);

    // 背景图片
    $background = new Imagick;
    $background->readimage("/data0/logs/static/user/template.png");

    // 将图片合并到画布
    $canvas->compositeImage($face, Imagick::COMPOSITE_OVER, 194, 0);
    $canvas->compositeImage($pic, Imagick::COMPOSITE_OVER, ($canvasWidth-$codeWith)/2, $canvasHeight-$codeWith-31);
    $canvas->compositeImage($background, Imagick::COMPOSITE_OVER, 0, 0);


    $draw = new ImagickDraw;
    $draw->setFont($fontFile);
    $draw->setFontSize($fontSize);
    $draw->setFillColor(new ImagickPixel('#000000'));
    $draw->setTextAlignment(Imagick::ALIGN_CENTER);
    $canvas->annotateImage($draw,$canvasWidth/2, 230,0,$username);


    $desc = autowrap($fontSize,$fontFile,$desc,460);
    $draw->setFont($fontFile);
    $draw->setFontSize($fontSize);
    $draw->setFillColor(new ImagickPixel('#000000'));
    $draw->setTextAlignment(Imagick::ALIGN_CENTER);
    $canvas->annotateImage($draw,$canvasWidth/2, 310,0,$desc);


    $draw->setFont($fontFile);
    $draw->setFontSize($fontSize);
    $draw->setFillColor(new ImagickPixel('#E23B3B'));
    $draw->setTextAlignment(Imagick::ALIGN_LEFT);
    $canvas->annotateImage($draw,290, 275,0,$num);

    $draw->setFont($fontFile);
    $draw->setFontSize($fontSize);
    $draw->setFillColor(new ImagickPixel('#ffffff'));
    $draw->setTextAlignment(Imagick::ALIGN_CENTER);
    $canvas->annotateImage($draw,$canvasWidth/2, $canvasHeight-20,0,"来找我玩，先长按识别二维码");

    // 保存图片到另一目录
    $userCardPath = "/data0/logs/static/user";
    $canvas->writeimage($userCardPath ."/".$id.".jpg");
}
