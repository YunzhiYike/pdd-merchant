<?php

declare(strict_types=1);
/**
 * This file is part of Yunzhiyike
 */

namespace Yunzhiyike\PddMerchant;

use GuzzleHttp\Client;
use stdClass;
use Yunzhiyike\PddMerchant\Exception\PddMerchantException;

class PddMerchant
{
    protected static int $REQUEST_OK = 1000000;

    protected static int $IS_NOT_DAREN = 3000000;

    protected Client $client;

    protected PddEncryptionRemoteApi $pddEncryptionRemoteApi;

    protected string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36';

    protected string $cookie;

    protected static string $PDD_MERCHANT_HOST = 'https://mms.pinduoduo.com';

    /**
     * @param int $requestTimeout 请求超时时间
     * @param string $pddServiceApi 远程拼多多API接口服务地址
     * @param string $token 远程拼多多API接口服务token
     */
    public function __construct(int $requestTimeout, string $pddServiceApi, string $token)
    {
        $this->client = new Client(['timeout' => $requestTimeout]);
        $this->pddEncryptionRemoteApi = new PddEncryptionRemoteApi($pddServiceApi, $token);
    }

    /**
     * @param string $accountName 拼多多商家账号
     * @param string $password 密码
     * @param string $smsCode 收到的短信验证码
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *                                               拼多多商家登录
     */
    public function login(string $accountName, string $password, string $smsCode): array
    {
        $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
        $encryptionPassword = $this->pddEncryptionRemoteApi->getEncryptionPassword($password);
        $uri = self::$PDD_MERCHANT_HOST . '/janus/api/auth';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Anti-Content' => $at,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/home/',
        ];
        $body = [
            'username' => $accountName,
            'password' => $encryptionPassword,
            'passwordEncrypt' => true,
            'verificationCode' => $smsCode,
            'mobileVerifyCode' => $smsCode,
            'sign' => '',
            'touchevent' => [
                'mobileInputEditStartTime' => 1700317167504,
                'mobileInputEditFinishTime' => 1700317168690,
                'mobileInputKeyboardEvent' => '0|1|0|2559-56-684-1170-524-75',
                'passwordInputEditStartTime' => 1700317168161,
                'passwordInputEditFinishTime' => 1700317168167,
                'passwordInputKeyboardEvent' => '0|0|0|',
                'captureInputEditStartTime' => '',
                'captureInputEditFinishTime' => '',
                'captureInputKeyboardEvent' => '',
                'loginButtonTouchPoint' => '227,607',
                'loginButtonClickTime' => 1700317168723,
            ],
            'fingerprint' => [
                'innerHeight' => 682,
                'innerWidth' => 501,
                'devicePixelRatio' => 2,
                'availHeight' => 803,
                'availWidth' => 1440,
                'height' => 900,
                'width' => 1440,
                'colorDepth' => 30,
                'locationHref' => 'https://mms.pinduoduo.com/login/?redirectUrl=https%3A%2F%2Fmms.pinduoduo.com%2F',
                'clientWidth' => 501,
                'clientHeight' => 907,
                'offsetWidth' => 501,
                'offsetHeight' => 907,
                'scrollWidth' => 1400,
                'scrollHeight' => 907,
                'navigator' => [
                    'appCodeName' => 'Mozilla',
                    'appName' => 'Netscape',
                    'hardwareConcurrency' => 8,
                    'language' => 'zh',
                    'cookieEnabled' => true,
                    'platform' => 'MacIntel',
                    'doNotTrack' => null,
                    'ua' => $this->userAgent,
                    'vendor' => 'Google Inc.',
                    'product' => 'Gecko',
                    'productSub' => '20030107',
                    'mimeTypes' => '2a05ac4783528564abb5534cb19317b5a76e846c',
                    'plugins' => '815724e1464029ae6166c291b0ab1753240abbc3',
                ],
                'referer' => '',
                'timezoneOffset' => -480,
            ],
            'riskSign' => 'X8wpVKEOyKZKsLfbaZM8+vI3r2US0iljeqQ2UQcBXiUmx1lxvzPMBQhnbz9m8/b+mbsPqqnPcyr8GLDuqV26oc16jIJ630CYlEXdECVg3y4hUcGKaRYXwNCsXGV9W/LYAY813aa/W7Bi5oDdv0kZCN6zKc/2qrRGp9sxBIQuTlI=',
            'timestamp' => 1701945511105,
            'crawlerInfo' => $at,
        ];
        $res = $this->client->post($uri, ['headers' => $headers, 'json' => $body]);
        $respCookies = $res->getHeader('Set-Cookie') ?? [];
        $this->cookie = '';
        foreach ($respCookies as $cookie) {
            $this->cookie = $this->cookie . $cookie . '; ';
        }
        $res = $res->getBody()->getContents();
        $res = json_decode($res, true);
        if ($res['errorCode'] != self::$REQUEST_OK) {
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd sendSmsCode Unknown Error');
        }
        return $res['result'];
    }

    /**
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *                                               财务二次授权
     */
    public function finances2Auth(): void
    {
        $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
        $uri = self::$PDD_MERCHANT_HOST . '/uranus/api/ticket/getJumpUrl/mallFinanceSiteBalanceBill';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Anti-Content' => $at,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/cashier/finance/payment-bills',
            'Cookie' => $this->cookie,
        ];
        $body = [
            'clientSource' => 1,
        ];
        $res = $this->client->post($uri, ['headers' => $headers, 'json' => $body])->getBody()->getContents();
        $res = json_decode($res, true);
        if ($res['errorCode'] != self::$REQUEST_OK) {
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd sendSmsCode Unknown Error');
        }
        // 使用 parse_url 解析 URL
        $urlComponents = parse_url($res['result']);
        if (! isset($urlComponents['query'])) {
            throw new PddMerchantException('参数提取失败');
        }
        parse_str($urlComponents['query'], $queryParams);
        if (! isset($queryParams['ticket'])) {
            throw new PddMerchantException('ticket参数不存在');
        }
        $ticket = $queryParams['ticket'];
        // 二跳授权
        $uri = 'https://cashier.pinduoduo.com/sherlock/api/auth/checkTicketV2';
        $body = [
            'ticket' => $ticket,
        ];
        $res = $this->client->post($uri, ['headers' => $headers, 'json' => $body]);
        $respCookies = $res->getHeader('Set-Cookie') ?? [];
        $res = $res->getBody()->getContents();
        $res = json_decode($res, true);
        if ($res['errorCode'] != self::$REQUEST_OK) {
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd sendSmsCode Unknown Error');
        }
        $this->cookie = '';
        foreach ($respCookies as $cookie) {
            $this->cookie = $this->cookie . $cookie . '; ';
        }
    }

    /**
     * @param string $startMonth Y-m
     * @param string $endMonth Y-m
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *                                               获取财务月度数据汇总
     */
    public function queryMallBalanceMonthlySummary(string $startMonth, string $endMonth): array
    {
        $uri = 'https://cashier.pinduoduo.com/templar/api/bill/queryMallBalanceMonthlySummary?__app_code=113';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://cashier.pinduoduo.com/main/bills?tab=4001&__app_code=113',
            'Cookie' => $this->cookie,
        ];
        $body = [
            'beginMonth' => $startMonth,
            'endMonth' => $endMonth,
        ];
        return $this->sendPost($uri, $headers, $body);
    }

    /**
     * @param int $beginTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param null|string $orderSn 订单号
     * @param null|mixed $minAmount 最小金额
     * @param null|mixed $maxAmount 最大金额
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createBillDownloadTask(int $beginTime, int $endTime, null|string $orderSn = null, mixed $minAmount = null, mixed $maxAmount = null): void
    {
        $uri = 'https://cashier.pinduoduo.com/templar/api/bill/createBillDownloadTask?__app_code=113';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://cashier.pinduoduo.com/main/bills?tab=4001&__app_code=113',
            'Cookie' => $this->cookie,
        ];
        $body = [
            'beginTime' => $beginTime,
            'endTime' => $endTime,
            'groupType' => 0,
            'classList' => [],
            'minAmount' => $minAmount,
            'maxAmount' => $maxAmount,
            'orderSn' => $orderSn,
        ];
        $this->sendPost($uri, $headers, $body);
    }

    /**
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *                                               获取财务流水下载任务列表 配合createBillDownloadTask使用
     */
    public function queryBillDownloadTaskList(int $page = 1, int $pageSize = 10): array
    {
        $uri = 'https://cashier.pinduoduo.com/templar/api/bill/queryBillDownloadTaskList?__app_code=113';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://cashier.pinduoduo.com/main/bills?tab=4001&__app_code=113',
            'Cookie' => $this->cookie,
        ];
        $body = [
            'page' => $page,
            'size' => $pageSize,
        ];
        return $this->sendPost($uri, $headers, $body);
    }

    /**
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *                                               创建订单导出任务
     */
    public function createOrderTask(int $startTime, int $endTime): array
    {
        $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
        $uri = self::$PDD_MERCHANT_HOST . '/mars/shop/recentOrders/export/task/add';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/orders/exportExcel',
            'Cookie' => $this->cookie,
            'Anti-Content' => $at,
        ];
        $body = [
            'orderType' => 0,
            'afterSaleType' => 0,
            'groupStartTime' => $startTime,
            'groupEndTime' => $endTime,
            'pageNumber' => 1,
            'pageSize' => 20,
            'templateName' => '自定义报表',
            'titles' => [1, 2, 3, 13, 14, 15, 18, 86, 19, 20, 21, 22, 23, 77, 78, 79, 80, 24, 25, 26, 27, 28, 29, 61, 62, 70, 71, 72, 73, 81, 82, 84, 85, 88, 92, 4, 7, 5, 6, 94, 8, 9, 10, 11, 12, 30, 31, 34, 35, 36, 89, 87, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 54, 55, 56, 57, 58, 76, 59, 60, 64, 65, 66, 68, 69, 83, 90, 93],
            'rememberTemplate' => true,
            'hideRegionBlackDelayShipping' => false,
            'crawlerInfo' => $at,
        ];
        return $this->sendPost($uri, $headers, $body, $at);
    }

    /**
     * @param int $page 页数
     * @param int $pageSize 页码
     * @throws PddMerchantException
     *                              获取订单导出任务列表
     */
    public function getOrderTaskList(int $page = 1, int $pageSize = 10): array
    {
        $uri = self::$PDD_MERCHANT_HOST . '/mars/shop/orders/export/task/list';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/orders/exportExcel',
            'Cookie' => $this->cookie,
        ];
        $body = [
            'pageNumber' => $page,
            'pageSize' => $pageSize,
        ];
        return $this->sendPost($uri, $headers, $body);
    }

    /**
     * @throws PddMerchantException
     *                              获取订单下载地址
     */
    public function getOrderTaskDownloadUrl(int $id): string
    {
        $uri = self::$PDD_MERCHANT_HOST . '/mars/shop/orders/export/task/getUrl';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/orders/exportExcel',
            'Cookie' => $this->cookie,
        ];
        $body = [
            'jobId' => $id,
        ];
        return $this->sendPost($uri, $headers, $body);
    }

    /**
     * @param string $oid 订单号
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param int $page 页数
     * @param int $pageSize 页码
     * @throws PddMerchantException
     *                              获取订单评价
     */
    public function getOrderComments(string $oid, int $startTime, int $endTime, int $page = 1, int $pageSize = 10): array
    {
        $uri = self::$PDD_MERCHANT_HOST . '/saturn/reviews/list';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/goods/evaluation/index',
            'Cookie' => $this->cookie,
        ];
        $body = [
            'pageNo' => $page,
            'pageSize' => $pageSize,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'orderSn' => $oid,
        ];
        return $this->sendPost($uri, $headers, $body);
    }

    /**
     * @throws PddMerchantException
     *                              获取昨天的经营数据
     */
    public function queryMallScoreOverView(): array
    {
        $uri = self::$PDD_MERCHANT_HOST . '/sydney/api/mallScore/queryMallScoreOverView';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/sycm/evaluation/overview',
            'Cookie' => $this->cookie,
        ];
        return $this->sendPost($uri, $headers, new stdClass());
    }

    /**
     * @throws PddMerchantException
     *                              登录检查
     */
    public function checkLogin(): bool
    {
        $uri = self::$PDD_MERCHANT_HOST . '/janus/api/checkLogin';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/sycm/evaluation/overview',
            'Cookie' => $this->cookie,
        ];
        $res = $this->sendPost($uri, $headers, new stdClass());
        return $res['login'];
    }

    /**
     * @return void
     * @throws PddMerchantException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * 拼多多推广二次授权
     */
    public function promotion2Auth(): void
    {
        $uri = self::$PDD_MERCHANT_HOST . '/janus/api/subSystem/generateAccessToken';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/home/',
            'Cookie' => $this->cookie,
        ];
        $body = [
            'redirectUrl' => 'https://yingxiao.pinduoduo.com/tools/index',
        ];
        $res = $this->sendPost($uri, $headers, $body);
        $accessToken = $res['accessToken'];
        $body = [
            'accessToken' => $accessToken,
            'subSystemId' => 7,
        ];
        $uri = 'https://yingxiao.pinduoduo.com/mms-gateway/user/getToken';
        $res = $this->client->post($uri, ['headers' => $headers, 'json' => $body]);
        $respCookies = $res->getHeader('Set-Cookie') ?? [];
        $res = $res->getBody()->getContents();
        $res = json_decode($res, true);
        if ($res['errorCode'] != self::$REQUEST_OK && $res['errorCode'] != 1000) {
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd promotion2Auth Unknown Error');
        }
        foreach ($respCookies as $cookie) {
            $this->cookie = $this->cookie . $cookie . '; ';
        }
    }


    /**
     * @return void
     * @throws PddMerchantException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * 拼多多视频二次授权
     */
    public function ddsp2Auth(): void
    {
        $uri = self::$PDD_MERCHANT_HOST . '/janus/api/subSystem/generateAccessToken';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/home/',
            'Cookie' => $this->cookie,
        ];
        $body = [
            'redirectUrl' => 'https://live.pinduoduo.com/login/checker?isNewCreatorFrom=video&referUrl=%2Fn-creator%2Fvideo%2Fhome%3Ffrom%3Dmms&from=mms',
        ];
        $res = $this->sendPost($uri, $headers, $body);
        $accessToken = $res['accessToken'];
        $body = [
            'out_token' => $accessToken,
            'token_type' => 1,
        ];
        $uri = 'https://live.pinduoduo.com/calpis/mms/user/login';
        $res = $this->client->post($uri, ['headers' => $headers, 'json' => $body]);
        $respCookies = $res->getHeader('Set-Cookie') ?? [];
        $res = $res->getBody()->getContents();
        $res = json_decode($res, true);
        if ($res['success'] === false) {
            throw new PddMerchantException($res['error_msg'] ?? 'Pdd promotion2Auth Unknown Error');
        }
        foreach ($respCookies as $cookie) {
            $this->cookie = $this->cookie . $cookie . '; ';
        }
    }

    /**
     * @param string $accountName 拼多多商家账号
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *                                               获取验证码
     */
    public function sendSmsCode(string $accountName): void
    {
        $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
        $uri = self::$PDD_MERCHANT_HOST . '/janus/api/user/getLoginVerificationCode';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Anti-Content' => $at,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/home/',
        ];
        $body = [
            'crawlerInfo' => $at,
            'username' => $accountName,
        ];
        $res = $this->client->post($uri, ['headers' => $headers, 'json' => $body])->getBody()->getContents();
        $res = json_decode($res, true);
        if ($res['errorCode'] != self::$REQUEST_OK) {
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd sendSmsCode Unknown Error');
        }
    }

    /**
     * @return string
     *                获取User-Agent请求头
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @return $this
     *               设置User-Agent请求头
     */
    public function setUserAgent(string $userAgent): PddMerchant
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @return string
     *                获取cookie
     */
    public function getCookie(): string
    {
        return $this->cookie;
    }

    /**
     * @return $this
     *               设置cookie
     */
    public function setCookie(string $cookies): PddMerchant
    {
        $this->cookie = $cookies;
        return $this;
    }

    protected function sendPost(string $uri, array $headers, array|stdClass $body, string $at = ''): array|bool|string
    {
        if ($at === '') {
            $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
            $headers['Anti-Content'] = $at;
        }
        $res = $this->client->post($uri, ['headers' => $headers, 'json' => $body])->getBody()->getContents();
        $res = json_decode($res, true);
        if ($res['errorCode'] != self::$REQUEST_OK && $res['errorCode'] != 0) {
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd sendPost Unknown Error');
        }
        return $res['result'];
    }
}
