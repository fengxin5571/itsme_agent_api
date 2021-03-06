<?php

require_once(dirname(__FILE__) . '/' . 'utils/GTHttpManager.php');
require_once(dirname(__FILE__) . '/' . 'utils/GTConfig.php');
require_once(dirname(__FILE__) . '/' . 'request/user/GTAliasRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/auth/GTAuthRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/user/GTTagSetRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/user/GTBadgeSetRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/user/GTUserQueryRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTPushRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTPushBatchRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTAudienceRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTSettings.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTStrategy.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTNotification.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTRevoke.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTPushChannel.php');
require_once(dirname(__FILE__) . '/' . 'request/push/ios/GTIos.php');
require_once(dirname(__FILE__) . '/' . 'request/push/ios/GTAps.php');
require_once(dirname(__FILE__) . '/' . 'request/push/ios/GTAlert.php');
require_once(dirname(__FILE__) . '/' . 'request/push/ios/GTMultimedia.php');
require_once(dirname(__FILE__) . '/' . 'request/GTApiRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/push/android/GTAndroid.php');
require_once(dirname(__FILE__) . '/' . 'request/push/android/GTUps.php');
require_once(dirname(__FILE__) . '/' . 'request/push/android/GTThirdNotification.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTCondition.php');
require_once(dirname(__FILE__) . '/' . 'request/user/GTUserQueryRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTPushRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTPushBatchRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTAudienceRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/user/GTTagBatchSetRequest.php');
require_once(dirname(__FILE__) . '/' . 'request/push/GTCondition.php');
require_once(dirname(__FILE__) . '/' . 'GTPushApi.php');
require_once(dirname(__FILE__) . '/' . 'GTStatisticsApi.php');
require_once(dirname(__FILE__) . '/' . 'GTPushApi.php');
require_once(dirname(__FILE__) . '/' . 'GTUserApi.php');

/**
 * ???????????????????????????????????????????????????????????????????????????
 * ???new??????GTclient???????????????????????????????????????????????????GTclient?????????????????????
 **/
class GTClient
{
    //????????? ??????
    private $appkey;
    //????????? ??????
    private $masterSecret;
    //??????token
    private $authToken;
    //????????? appid
    private $appId;

    //????????????https?????? ??????????????????
    private $useSSL = null;
    //????????????????????????????????????
    private $domainUrlList = null;
    //???????????????????????????????????????????????????????????????????????????
    private $isAssigned = false;

    //??????api
    private $pushApi = null;
    //??????api
    private $statisticsApi = null;
    //??????api
    private $userApi = null;

    public function __construct($domainUrl, $appkey, $appId, $masterSecret, $ssl = NULL)
    {
        $this->appkey = $appkey;
        $this->masterSecret = $masterSecret;
        $this->appId = $appId;
        $this->pushApi = new GTPushApi($this);
        $this->statisticsApi = new GTStatisticsApi($this);
        $this->userApi = new GTUserApi($this);

        $domainUrl = trim($domainUrl);
        if ($ssl == NULL && $domainUrl != NULL && strpos(strtolower($domainUrl), "https:") === 0) {
            $ssl = true;
        }

        $this->useSSL = ($ssl == NULL ? false : $ssl);

        if ($domainUrl == NULL || strlen($domainUrl) == 0) {
            $this->domainUrlList = GTConfig::getDefaultDomainUrl($this->useSSL);
        } else {
            if (GTConfig::isNeedOSAsigned()) {
                $this->isAssigned = true;
            }
            $this->domainUrlList = array($domainUrl);
        }
        //??????
        try {
            $this->auth($appkey,$masterSecret);
        } catch (Exception $e) {
            echo  $e->getMessage();
        }
    }

    public function getAuthToken()
    {
        return $this->authToken;
    }

    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;
    }

    public function pushApi()
    {
        return $this->pushApi;
    }

    public function statisticsApi()
    {
        return $this->statisticsApi;
    }

    public function userApi()
    {
        return $this->userApi;
    }


    public function getHost()
    {
        return $this->domainUrlList[0];
    }

    public function setDomainUrlList($domainUrlList)
    {
        $this->domainUrlList = $domainUrlList;
    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function auth($appkey,$masterSecret)
    {
        $auth = new GTAuthRequest();
        $auth->setAppkey($appkey);
        $timeStamp = $this->getMicroTime();
        $sign = hash("sha256", $appkey . $timeStamp . $masterSecret);
        $auth->setSign($sign);
        $auth->setTimestamp($timeStamp);
        $rep = $this->userApi()->auth($auth);
        if ($rep["code"] == 0) {
            $this->authToken = $rep["data"]["token"];
            return true;
        }
        return false;
    }

    function getMicroTime()
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = ($sec . substr($usec, 2, 3));
        return $time;
    }
}