<?php
/**
 * Created by PhpStorm.
 * User: MYX
 * Date: 2018/5/22
 * Time: 20:16
 * 公共文件放在common下的model
 */
namespace Common\Model;

class IndexModel  {
    //回复多图文类型的微信消息
    public function responseNews($postObj,$arr){
        $ToUserName   = $postObj->FromUserName;
        $FromUserName = $postObj->ToUserName;
        $template     ="<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <ArticleCount>".count($arr)."</ArticleCount>
                        <Articles>";
        foreach ($arr as $k=>$s){
            $template .=" <item>
                        <Title><![CDATA[".$s['title']."]]></Title>
                         <Description><![CDATA[".$s['description']."]]></Description>
                        <PicUrl><![CDATA[".$s['picUrl']."]]></PicUrl>
                        <Url><![CDATA[".$s['url']."]]></Url>
                        </item>";
        };
        $template    .=  "</Articles>
                              </xml>";
        echo sprintf($template, $ToUserName, $FromUserName, time(), 'news');
    }
    // 回复单文本
    public function responseText($postObj,$Content){
        $ToUserName   = $postObj->FromUserName;
        $FromUserName = $postObj->ToUserName;
        $CreateTime   = time();
        $MsgType      = 'text';
        $template     ="<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                </xml>";
        // 把百分号（%）符号替换成一个作为参数进行传递的变量：
        $info     = sprintf($template,$ToUserName,$FromUserName,$CreateTime,$MsgType,$Content);
        echo $info;
    }
    //ceshi

    /**
     * @return string
     */
public function ceshi (){
    echo 111111;
}



}
