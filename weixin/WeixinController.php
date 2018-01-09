<?php

namespace Service\Controller;

use Common\Controller\BaseController;

class WeixinController extends BaseController
{
    const TOKEN = '123456';
    const APP_ID = 'wx45345345345';
    const APP_SECERET = 'hfghfghgfhfghgfh';
    
    
    
    private function wx_get_token()
    {
        $token = S('access_token');
        if (!$token) {
            $res = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.self::APP_ID.'&secret='.self::APP_SECERET);
            $res = json_decode($res, true);
            $token = $res['access_token'];
            // 注意：这里需要将获取到的token缓存起来（或写到数据库中）
            // 不能频繁的访问https://api.weixin.qq.com/cgi-bin/token，每日有次数限制
            // 通过此接口返回的token的有效期目前为2小时。令牌失效后，JS-SDK也就不能用了。
            // 因此，这里将token值缓存1小时，比2小时小。缓存失效后，再从接口获取新的token，这样
            // 就可以避免token失效。
            // S()是ThinkPhp的缓存函数，如果使用的是不ThinkPhp框架，可以使用你的缓存函数，或使用数据库来保存。
            S('access_token', $token, 3600);
        }
        return $token;
    }

    private function wx_get_jsapi_ticket()
    {
        /* $ticket = "";
        do {
            $ticket = S('wx_ticket');
            if (!empty($ticket)) {
                break;
            }
            $token = S('access_token');
            if (empty($token)) {
                $this->wx_get_token();
            }
            $token = S('access_token');
            if (empty($token)) {
                // logErr("get access token error.");
                break;
            }
            $url2 = sprintf("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi",
            $token);
                $res = file_get_contents($url2);
                $res = json_decode($res, true);
                $ticket = $res['ticket'];
                // 注意：这里需要将获取到的ticket缓存起来（或写到数据库中）
                // ticket和token一样，不能频繁的访问接口来获取，在每次获取后，我们把它保存起来。
                S('wx_ticket', $ticket, 3600);
        } while (0);
        return $ticket; */


            $token = S('access_token');
            if (empty($token)) {
                $this->wx_get_token();
            }
            $token = S('access_token');                
  
            $url2 = sprintf("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi",
            $token);
                $res = file_get_contents($url2);
                $res = json_decode($res, true);
                $ticket = $res['ticket'];
    

        return $ticket;
    }

    public function getWxConfig()
    {
        //noncestr是你设置的任意字符串。
        // timestamp为时间戳。
        // var_dump($_SERVER);exit;
        $currentUrl=$_SERVER['HTTP_REFERER'];
        $data['timestamp'] = time();
        $data['wxnonceStr'] = "dfsdfsdfsdfsdfvbcbc";
        $data['wxticket'] = $this->wx_get_jsapi_ticket();
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s",
            $data['wxticket'], $data['wxnonceStr'], $data['timestamp'],
            $currentUrl
            );
        $data['wxSha1'] = sha1($wxOri);

        $this->ajaxReturn($data);
    }

    public function test()
    {
        if (isset($_GET['echostr'])) {
            $this->valid();
        } else {
            $this->responseMsg();
        }
    }
    public function callBack()
    {
        $code = $_GET['code'];
        import('Org.Wechat');
        $oWechat = new \Wechat();
        //3.通过code换取网页授权access_token
        $result = $oWechat->get_access_token($code);
        $access_token = $result['access_token'];
        $open_id = $result['openid'];
        
        //4.拉取用户信息
        $userInfo = $oWechat->get_user_info($access_token, $open_id);
        
        print_r($userInfo);
        exit;
    }
    
    public function getLogin()
    {
        //Alipay.AliPay
        import('Org.Wechat');
        $oWechat = new \Wechat();
        echo $oWechat->get_authorize_url('12345');
    }
    
    public function getToken()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.self::APP_ID.'&secret='.self::APP_SECERET;
        $content = post_curl($url);
        header('Content-Type:application/json; charset=utf-8');
        echo $content;
        $content = json_decode($content, true);
        $access_token = $content['access_token'];
    }
    
    
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) {
            header('content-type:text');
            echo $echoStr;
            exit;
        }
    }
    
    private function curl_post_send_information($token, $vars, $second = 120, $aHeader = array())
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //这里设置代理，如果有的话
        curl_setopt($ch, CURLOPT_URL, 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$token);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return $error;
        }
    }
    
    
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
    
        $token = self::TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
    
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
    
    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
    
        if (!empty($postStr)) {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
            if ($keyword == "?" || $keyword == "？") {
                $msgType = "text";
                $contentStr = date("Y-m-d H:i:s", time());
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }
        } else {
            echo "";
            exit;
        }
    }
}
