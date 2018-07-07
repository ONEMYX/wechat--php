<?php
/*
 * 六月十九号群发接口 中的图片上传
 *
 * */
namespace Home\Controller;
use Common\Model\IndexModel;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        //获得参数 signature nonce token timestamp echostr
        $nonce     = $_GET['nonce'];
        $token     = 'ceshi';
        $timestamp = $_GET['timestamp'];
        $echostr   = $_GET['echostr'];
        $signature = $_GET['signature'];
        //形成数组，然后按字典序排序
        $array = array();
        $array = array($nonce, $timestamp, $token);
        sort($array);
        //implode拼接成字符串,sha1加密 ，然后与signature进行校验
        $str = sha1( implode( $array ) );
        if( $str  == $signature && $echostr ){
            //第一次接入weixin api接口的时候
            echo  $echostr;
            exit;
        }else{
            $this->reponseMsg();
        }
    }//index end


    //接收事件推送并回复
    public function reponseMsg(){
        //1.获取到微信推送过来post信息（xml)格式
        $postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
        //2.处理消息类型，并设置回复类型和内容
        //把xml 字符串载入对象中
        $postObj =simplexml_load_string($postArr);

        //判断该数据包是否是订阅的事件推送
        //strtolower 将字母转化为小写
        if(strtolower($postObj->MsgType) == 'event'){
            //如果是关注subscribe 事件
            if (strtolower($postObj->Event == 'subscribe')){
                $this->guanzhu($postObj);
            }
            if (strtolower($postObj->Event =='CLICK')){
                if (strtolower($postObj->EventKey=='item1')){
                    $host = "http://jisutqybmf.market.alicloudapi.com";
                    $path = "/weather/query";
                    $method = "GET";
                    $appcode = "d070f0c2e97d4945a581fdbaf5bab707";
                    $headers = array();
                    array_push($headers, "Authorization:APPCODE " . $appcode);
                    $querys = "city=南阳";
                    $bodys = "";
                    $url = $host . $path . "?" . $querys;

                    $curl = curl_init();
                    //使用一个自定义的请求信息 来代替"GET"或"HEAD"作为HTTP请求
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                    // 获取url 地址
                    curl_setopt($curl, CURLOPT_URL, $url);
                    //设置http 的头
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                    //显示HTTP状态码
                    curl_setopt($curl, CURLOPT_FAILONERROR, false);
                    //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    if (1 == strpos("$".$host, "https://"))
                    {
                        //禁止后curl将终止从服务器端进行验证
                        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                    }
                    $res = curl_exec($curl);
                    curl_close($curl);
                    $arr=json_decode($res,true);
                    $Content='时间:'.$arr['result']['date'].$arr['result']['week']."\n"
                        ."地点:".$arr['result']['city']."\n"
                        .'气温:'.$arr['result']['temp'] ."度"."\n"
                        ."最高气温:". $arr['result']['temphigh']."度"."\n"
                        ."最低气温:".$arr['result']['templow']."度"."\n"
                        ."风向:".$arr['result']['winddirect']."\n"
                        ."风级:".$arr['result']['windpower'];
                    $IndexModel=new  IndexModel();
                    $IndexModel->responseText($postObj,$Content);
                }
                if (strtolower($postObj->EventKey =='songs')){
                    $Content='这是歌曲的事件推送';
                    $IndexModel=new IndexModel();
                    $IndexModel->responseText($postObj,$Content);
                }
            }
        }


        //  图文消息
        if(strtolower($postObj->MsgType) == 'text'&& trim($postObj->Content) == '图文'){
            $this->tuwen($postObj);
        }elseif (strtolower($postObj->MsgType)== 'text'&& is_numeric(trim($postObj->Content)) ){
            $this->danwenben($postObj);
        } elseif (strtolower($postObj->MsgType)== 'text'){
           $this->tianqi($postObj);
        }//if end
    }//reponserMsg end

    //自定义菜单
    public function definedItem(){
        //1.获取token
        $acessToken = $this->getWxAccessToken();
        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$acessToken;
        //2. 组装array
        $postArr = array(
            "button" => array(
                array(
                    'name'=>urlencode('天气'),
                    'type'=>'click',
                    'key'=>'item1',
                ),//第一个一级菜单
                array(
                    'name'=>urlencode('菜单二'),
                    'sub_button'=>array(
                        array(
                            'name'=>urlencode('歌曲'),
                            'type'=>'click',
                            'key'=>'songs',
                        ),//第一个二级菜单
                        array(
                            'name'=>urlencode('电影'),
                            'type'=>'view',
                            'url'=>'http://www.baidu.com'
                        ),//第二个二级菜单
                    ),
                ),// 第一个二级菜单 end
                array(
                    'name'=>urlencode('菜单三'),
                    'type'=>'view',
                    'url'=>'http://www.qq.com'
                ),//第二个一级菜单 end
            ),// button end
        );// psotArr end
        $postJson = urldecode(json_encode( $postArr ));
        $res = $this->http_url($url,'post','json',$postJson);
        var_dump($res);
    }// definedItem end

    // 群发接口
    public function sendMsagAll(){
        // 1. 获取token
        $acessToken = $this->getWxAccessToken();
        $url="https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=".$acessToken;
        //2. 组装群发接口array
        $array = array(
            'touser' => 'o-8sQ1aUq54fKDQbY4tVEg7UV1CU',
            'text' => array(
                'content'=> "I'll try anything once."
            ),
            'msgtype' => 'text'
        );
        //3. array -> json
        $postJosn = urldecode(json_encode( $array ));
        //4. 调用 curl
        $res = $this->http_url($url,'post','json',$postJosn);

    }// sedMsgAll end

    //模板消息
    public function sendTemolateMsg(){
        //获取token
        $acessToken = $this->getWxAccessToken();
        var_dump($acessToken);
        echo'<hr>';

        $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$acessToken;
        //组装数组
        $array = array(
            'touser'=>'o-8sQ1aUq54fKDQbY4tVEg7UV1CU',
            'template_id'=>'hAFOrzkG4DB8uPq_O50lWQ521gyHbigePC1A-Dy0dsc',
            'url'=>'https://www.baidu.com',
            'data'=>array(
                'name'=>array('value'=>'hello','color'=>'#173177'),
                'money'=>array('value'=>100,'color'=>'#173177'),
                'date'=>array('value'=>date('Y-m-d H:i:s'),'color'=>'#173177')
            ),
        );
        //数组转换json
        $postJosn = json_encode( $array );
        var_dump($postJosn);
        echo'<hr>';
        //发送请求。
        $res = $this->http_url($url,'post','json',$postJosn);
        var_dump($res);
    }

    //网页授权
    public function getBaseInfo(){
        //1.获取code
        $appid = '';
        $redirect_url = urlencode('');
        $response_type = 'code';
        $scope = 'snsapi_base';
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_url."&response_type=".$response_type."&scope=".$scope."&state=123#wechat_redirect";
        header('location:'.$url);
    }
    public function getUserOpenId(){
        $appid ='';
        $secret = '';
        $code = $_GET['code'];
        $grant_type = 'authorization_code';
        $url ="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$secret."&code=".$code."&grant_type=".$grant_type;
        $res = $this->http_url($url,'get');
        var_dump($res);
    }
    public function getUserDetail(){
        //1.获取code
        $appid = '';
        $redirect_url = urlencode('');
        $response_type = 'code';
        $scope = 'snsapi_userinfo';
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_url."&response_type=".$response_type."&scope=".$scope."&state=123#wechat_redirect";
        header('location:'.$url);
    }
    public function getUserInfo(){
        $appid ='';
        $secret = '';
        $code = $_GET['code'];
        var_dump($code);
        $grant_type = 'authorization_code';
        $url ="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$secret."&code=".$code."&grant_type=".$grant_type;
        $res = $this->http_url($url,'get');
        //拉取用户信息
        $access_token = $res['access_token'];
        $openid = $res['openid'];
        $lang = 'zh_CN';
        unset($url);
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=".$lang;
        $user = $this->http_url($url,'get');
        var_dump($user);
    }
    
    
    //获取jsapi
    public function getJsApiTicket(){
        //1. 获取token
        if ($_SESSION['jsapi_token_expire_time'] > time() && isset($_SESSION['jsapi_token']) && empty($_SESSION['jsapi_ticket'])){
            $jsapi_ticket =$_SESSION['jsapi_token'];
        }else{
            $access_token = $this->getWxAccessToken();
            $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi';
            $res = $this->http_url($url);
            $jsapi_ticket = $res['ticket'];
            $_SESSION['jsapi_ticket'] = $jsapi_ticket;
            $_SESSION['jsapi_ticket_expire_time'] = time()+7000;
        }
        return $jsapi_ticket;
    }
    
    //获取随机数
    public function getRandCode($num=16){
        $array = array(
            'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
            '0','1','2','3','4','5','6','7','8','9'
        );
        $tmpstr = '';
        $max = count($array);
        for ($i=1;$i<=$num;$i++){
            $key = rand(0,$max-1);
            $tmpstr .= $array[$key];
        }
        return $tmpstr;

    }

    //js-sdk 分享朋友圈
    public function shareWx(){
        //获取票据jsapi_ticket
        $jsapi_ticket = $this->getJsApiTicket();
        $timestamp = time();
        $noncestr = $this->getRandCode();

        $url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        //2.获取signature
        $str1 = 'jsapi_ticket='.$jsapi_ticket.'&noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.$url;

        $signature = sha1($str1);


        $this->assign('timestamp',$timestamp);
        $this->assign('noncestr',$noncestr);
        $this->assign('signature',$signature);
        $this->display('Index/share');
    }



    //返回access_token /
    public function getWxAccessToken(){
        //将 token 存在seession/cookie 中
        if(isset($_SESSION['access_token']) && $_SESSION['expire_time']>time()){
            return $_SESSION['access_token'];
        }else{
            // 如果access_token 不存在或者已经过期，重新获取
            //1.请求url的地址
            $appid ='';
            $appsecret='';
            $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret;
            $res = $this->http_url($url,'get','json');
            $access_token = $res['access_token'];
            //将重新获取到的access_token 存到session
            $_SESSION['access_token']=$access_token;
            $_SESSION['expire_time'] = time()+7000;
            return $access_token;
        }

    }//getWxAccessToken end

    // curl的使用
    public function http_url($url,$type='get',$res='json',$arr=''){
        //获取imooc
        //1.初始化curl
        $ch =curl_init();
        //2.设置curl的参数
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        if ($type='post'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer

            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$arr);

            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
            curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容

        }

        //3.采集
        $output = curl_exec($ch);
        //4.关闭
        curl_close($ch);
        if ($res=='json'){
            if (curl_errno($ch)){
                //请求失败，返回错误信息
                return curl_errno($ch);
            }else{
                //请求成功
                return json_decode($output,true);
            }
        }
    }



    // 关注回复
    public function guanzhu($postObj){
        //回复用户消息（纯文本格式）
        /*
        <xml>
        <ToUserName>< ![CDATA[toUser] ]></ToUserName>
        <FromUserName>< ![CDATA[fromUser] ]></FromUserName>
        <CreateTime>12345678</CreateTime>
        <MsgType>< ![CDATA[text] ]></MsgType>
        <Content>< ![CDATA[你好] ]></Content>
        </xml>
        */
        $ToUserName   = $postObj->FromUserName;
        $FromUserName = $postObj->ToUserName;
        $CreateTime   = time();
        $MsgType      = 'text';
        $Content      = '欢迎关注我们的微信公众账号！';
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
    }//guanzhu end
    // 多图文回复
    public function tuwen($postObj){
        $arr          =array(
            array(
                'title'=>'百度',
                'description'=>"百度 is very cool",
                'picUrl'=>'https://www.baidu.com/img/bdlogo.png',
                'url'=>'http://www.baidu.com',
            ),
            array(
                'title'=>'qq',
                'description'=>"qq is very cool",
                'picUrl'=>'http://sqimg.qq.com/qq_product_operations/im/qqlogo/imlogo_b.png',
                'url'=>'http://www.qq.com',
            ),
        );
        $IndexModel=new IndexModel();
        $IndexModel->responseNews($postObj,$arr);
    }//tuwen end
    //单图文回复
    public function danwenben($postObj){
        switch (trim($postObj->Content)){
            case 1:
                $Content ='1';
                break;
            case '2':
                $Content='<a href="https://www.baidu.com">百度网</a>';
                break;
            case '中文':
                $Content ='中 文';
                break;
            case 'tp':
                $Content ='this is tp.';
                break;
            default:
                $Content='没有';
                break;
        }
        $IndexModel=new IndexModel();
        $IndexModel->responseText($postObj,$Content);
    }//danwenben end
    //天气
    public function tianqi($postObj){
        $host = "http://jisutqybmf.market.alicloudapi.com";
        $path = "/weather/query";
        $method = "GET";
        $appcode = "d070f0c2e97d4945a581fdbaf5bab707";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "city=".urlencode($postObj->Content);
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        //使用一个自定义的请求信息 来代替"GET"或"HEAD"作为HTTP请求
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        // 获取url 地址
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置http 的头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //显示HTTP状态码
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (1 == strpos("$".$host, "https://"))
        {
            //禁止后curl将终止从服务器端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $res = curl_exec($curl);
        curl_close($curl);
        $arr=json_decode($res,true);
        $Content='时间:'.$arr['result']['date'].$arr['result']['week']."\n"
            ."地点:".$arr['result']['city']."\n"
            .'气温:'.$arr['result']['temp'] ."度"."\n"
            ."最高气温:". $arr['result']['temphigh']."度"."\n"
            ."最低气温:".$arr['result']['templow']."度"."\n"
            ."风向:".$arr['result']['winddirect']."\n"
            ."风级:".$arr['result']['windpower'];
        $IndexModel=new  IndexModel();
        $IndexModel->responseText($postObj,$Content);
    }//tianqi end
    //获取微信服务器的ip
    function getWxServerIp(){
        $accessToken = "10_y7W-20s3lDK0AgjGJM9EwMCQCeKzf1H2QqjQyl-1d38k167zGHthPj1VEaP7acBB9CyzPhtZ7hmW_bYzhKVj_KFXGMm_QExkdRWGILl9YjINzPbP07jwF2RMewMLBSjAJADTK";
        $url = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=".$accessToken;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $res = curl_exec($ch);
        curl_close($ch);
        if(curl_errno($ch)){
            var_dump(curl_error($ch));
        }
        $arr = json_decode($res,true);
        echo "<pre>";
        var_dump( $arr );
        echo "</pre>";


    }

    // 获取天气实验接口
    public function ceModel(){
        header("Content-type:text/html;charset=utf-8");
        $host = "http://jisutqybmf.market.alicloudapi.com";
        $path = "/weather/query";
        $method = "GET";
        $appcode = "d070f0c2e97d4945a581fdbaf5bab707";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "city=".urlencode('北京');
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $res = curl_exec($curl);
        curl_close($curl);
        $arr=json_decode($res,true);
        var_dump($arr);
        
        
        
        
        
        
        
        
        echo'<br>';
        echo $arr['result']['city'];
        echo $arr['result']['date'];
        echo $arr['result']['week'];
        echo $arr['result']['weather'];
        echo $arr['result']['temp'];
        echo $arr['result']['temphigh'];
        echo $arr['result']['templow'];
        echo $arr['result']['winddirect'];
        echo $arr['result']['windpower'];


    }








}
