<?php

declare(strict_types=1);
/**
 * This file is part of Yunzhiyike
 */

namespace Yunzhiyike\PddMerchant;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use stdClass;
use Yunzhiyike\PddMerchant\Exception\PddMerchantException;
use Yunzhiyike\PddMerchant\Exception\PddSliderVerifyException;

class PddMerchant
{
    protected static int $REQUEST_OK = 1000000;

    protected static int $IS_NOT_DAREN = 3000000;

    // 移动滑块验证码
    protected static int $SLIDER_VERIFY = 54001;

    protected Client $client;

    protected PddEncryptionRemoteApi $pddEncryptionRemoteApi;

    protected string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36';

    protected string $cookie;

    // 验证码校验token
    protected string $verifyAuthToken = '';

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
     * @throws GuzzleException
     *                         拼多多商家登录
     */
    public function login(string $accountName, string $password, string $smsCode = '', string $verifyAuthToken = ''): array
    {
        $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
        $encryptionPassword = $this->pddEncryptionRemoteApi->getEncryptionPassword($password);
        $uri = self::$PDD_MERCHANT_HOST . '/janus/api/auth';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Anti-Content' => $at,
            'Verifyauthtoken' => $verifyAuthToken,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/home/',
            'Cookie' => 'api_uid=CiZ1YWTCD6GybQB0avHtAg==; _nano_fp=XpEbX0TqlpdJn5Xono_7i0~M7KNqZKf8Aa4cZdC8; _bee=u1czU5ZKCeEXJi2ervItVONpjgmscheu; _f77=a610acb0-2b3f-49ef-a6a7-d06e3bd17453; _a42=83f5b051-1eda-46c4-b28e-027000b7a952; rckk=u1czU5ZKCeEXJi2ervItVONpjgmscheu; ru1k=a610acb0-2b3f-49ef-a6a7-d06e3bd17453; ru2k=83f5b051-1eda-46c4-b28e-027000b7a952; webp=true; mms_b84d1838=3523,3254,3532,3474,3475,3477,3479,3482,1202,1203,1204,1205,3417,3397; x-visit-time=1703320276579; JSESSIONID=FF1E362C41020B766E86762974F7D532',
        ];
        $ts = time() * 1000;
        $s = sprintf('username=%s&password=%s&ts=%s', $accountName, $password, $ts);
        $riskSign = $this->pddEncryptionRemoteApi->getEncryptionPassword($s);
        $body = [
            'username' => $accountName,
            'password' => $encryptionPassword,
            'passwordEncrypt' => true,
            'verificationCode' => $smsCode,
            'mobileVerifyCode' => $smsCode,
            'sign' => '',
            'touchevent' => [
                'mobileInputEditStartTime' => $ts,
                'mobileInputEditFinishTime' => $ts,
                'mobileInputKeyboardEvent' => '0|1|1|522-712',
                'passwordInputEditStartTime' => $ts,
                'passwordInputEditFinishTime' => $ts,
                'passwordInputKeyboardEvent' => '0|0|0|573-620-272-226-249-81-249-268-236-261-2254-178',
                'captureInputEditStartTime' => '',
                'captureInputEditFinishTime' => '',
                'captureInputKeyboardEvent' => '',
                'loginButtonTouchPoint' => '1166,530',
                'loginButtonClickTime' => $ts,
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
                    'mimeTypes' => 'f5a1111231f589322da33fb59b56946b4043e092',
                    'plugins' => '387b918f593d4d8d6bfa647c07e108afbd7a6223',
                ],
                'referer' => '',
                'timezoneOffset' => -480,
            ],
            'riskSign' => $riskSign,
            'timestamp' => $ts,
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
     * @throws GuzzleException
     *                         财务二次授权
     */
    public function finances2Auth(string $verifyAuthToken = ''): void
    {
        $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
        $uri = self::$PDD_MERCHANT_HOST . '/uranus/api/ticket/getJumpUrl/mallFinanceSiteBalanceBill';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Anti-Content' => $at,
            'Content-Type' => 'application/json',
            'Verifyauthtoken' => $verifyAuthToken,
            'Referer' => 'https://mms.pinduoduo.com/cashier/finance/payment-bills',
            'Cookie' => $this->cookie,
        ];
        $body = [
            'clientSource' => 1,
        ];
        $res = $this->client->post($uri, ['headers' => $headers, 'json' => $body])->getBody()->getContents();
        $res = json_decode($res, true);

        // 滑块验证码异常
        if ($res['errorCode'] == self::$SLIDER_VERIFY) {
            $this->verifyAuthToken = $res['result']['verifyAuthToken'];
            throw new PddSliderVerifyException($res['errorMsg'] ?? 'Pdd finances2Auth Unknown Error');
        }

        if ($res['errorCode'] != self::$REQUEST_OK) {
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd finances2Auth Unknown Error');
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
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd finances2Auth Unknown Error');
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
     * @throws GuzzleException
     *                         获取财务月度数据汇总
     */
    public function queryMallBalanceMonthlySummary(string $startMonth, string $endMonth): array
    {
        $uri = 'https://cashier.pinduoduo.com/templar/api/bill/queryMallBalanceMonthlySummary?__app_code=113';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Verifyauthtoken' => $this->verifyAuthToken,
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
     * @throws GuzzleException
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
     * @throws GuzzleException
     *                         获取财务流水下载任务列表 配合createBillDownloadTask使用
     */
    public function queryBillDownloadTaskList(int $page = 1, int $pageSize = 10): array
    {
        $uri = 'https://cashier.pinduoduo.com/templar/api/bill/queryBillDownloadTaskList?__app_code=113';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Verifyauthtoken' => $this->verifyAuthToken,
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
     * @throws GuzzleException
     *                         创建订单导出任务
     */
    public function createOrderTask(int $startTime, int $endTime): bool
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
            'Verifyauthtoken' => $this->verifyAuthToken,
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
            'Verifyauthtoken' => $this->verifyAuthToken,
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
            'Verifyauthtoken' => $this->verifyAuthToken,
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
            'Verifyauthtoken' => $this->verifyAuthToken,
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
     * @param string $startDate 开始日期 YYYY-MMM-DD
     * @param string $endDate 结束日期 YYYY-MM-DD
     * @param int $storeId 店铺ID
     * @param int $page 页码
     * @param int $pageSize 页数
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws GuzzleException
     *                         获取全站推广数据
     */
    public function getSiteWidePromotionReport(string $startDate, string $endDate, int $storeId, int $page = 1, int $pageSize = 10): array
    {
        $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
        $uri = 'https://yingxiao.pinduoduo.com/mms-gateway/apollo/api/report/queryEntityReport';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Verifyauthtoken' => $this->verifyAuthToken,
            'Referer' => 'https://yingxiao.pinduoduo.com/goods/report/odin/overView',
            'Cookie' => $this->cookie,
            'Anti-Content' => $at,
            'Origin' => 'https://yingxiao.pinduoduo.com',
        ];
        $data = [
            'crawlerInfo' => $at,
            'entityDimensionType' => 0,
            'queryDimensionType' => 2,
            'scenesType' => 9,
            'query' => [
                'fieldToValue' => new stdClass(),
            ],
            'downLoadExternalFields' => [
                'goodsName',
                'goodsId',
                'isDeleted',
            ],
            'externalFields' => [
                'planId',
                'adId',
                'thumbUrl',
                'goodsName',
                'goodsId',
                'minOnSaleGroupPrice',
                'isDeleted',
                'planDeleted',
                'adDeleted',
                'bid',
                'targetRoi',
                'planStrategy',
                'scenesMode',
                'mallFavBid',
                'goodsFavBid',
                'inquiryBid',
                'groupName',
            ],
            'queryRange' => [
                'pageNumber' => $page,
                'pageSize' => $pageSize,
            ],
            'entityId' => $storeId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'returnTotalSumReport' => true,
            'isStandardPromotion' => 0,
        ];
        return $this->sendPost($uri, $headers, $data, $at);
    }

    /**
     * @param string $startDate 开始日期 YYYY-MMM-DD
     * @param string $endDate 结束日期 YYYY-MM-DD
     * @param int $storeId 店铺ID
     * @param int $page 页码
     * @param int $pageSize 页数
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws GuzzleException
     *                         获取标准推广数据
     */
    public function getStandardPromotionReport(string $startDate, string $endDate, int $storeId, int $page = 1, int $pageSize = 10): array
    {
        $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
        $uri = 'https://yingxiao.pinduoduo.com/mms-gateway/apollo/api/report/queryEntityReport';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://yingxiao.pinduoduo.com/goods/report/standard/overView',
            'Cookie' => $this->cookie,
        ];
        $data = [
            'crawlerInfo' => $at,
            'entityId' => $storeId,
            'entityDimensionType' => 0,
            'queryDimensionType' => 2,
            'query' => [
                'fieldToValue' => new stdClass(),
            ],
            'externalFields' => ['planName', 'productType', 'planStrategy', 'planId', 'bid', 'adId', 'adName', 'mallName', 'minOnSaleGroupPrice', 'mallLogoUrl', 'thumbUrl', 'goodsName', 'goodsId', 'mallId', 'scenesType', 'isStandardPromotion', 'isReservedStandardPromotion', 'isDeleted', 'planDeleted', 'adDeleted', 'groupName'],
            'orderBy' => null,
            'orderType' => 0,
            'queryRange' => ['pageSize' => $pageSize, 'pageNumber' => $page],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'isStandardPromotion' => 1,
            'returnTotalSumReport' => true,
        ];

        return $this->sendPost($uri, $headers, $data, $at);
    }

    /**
     * @param string $startDate 开始日期 YYYY-MMM-DD
     * @param string $endDate 结束日期 YYYY-MM-DD
     * @param int $storeId 店铺ID
     * @param int $page 页码
     * @param int $pageSize 页数
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws GuzzleException
     *                         获取直播推广数据
     */
    public function getLivePromotionReport(string $startDate, string $endDate, int $storeId, int $page = 1, int $pageSize = 10): array
    {
        $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
        $uri = 'https://yingxiao.pinduoduo.com/mms-gateway/apollo/api/report/queryEntityReport';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://yingxiao.pinduoduo.com/goods/report/standard/overView',
            'Cookie' => $this->cookie,
        ];
        $data = [
            'crawlerInfo' => $at,
            'entityId' => $storeId,
            'entityDimensionType' => 0,
            'queryDimensionType' => 2,
            'scenesType' => 5,
            'query' => [
                'fieldToValue' => new stdClass(),
            ],
            'externalFields' => ['planName', 'planId', 'adId', 'adName', 'adStatus', 'roomId', 'roomImageUrl', 'roomTitle', 'planStrategy', 'isDeleted', 'planDeleted', 'adDeleted'],
            'orderBy' => null,
            'orderType' => 0,
            'queryRange' => ['pageNumber' => $page, 'pageSize' => $pageSize],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'returnTotalSumReport' => true,
            'isStandardPromotion' => 0,
        ];

        return $this->sendPost($uri, $headers, $data, $at);
    }

    /**
     * @param string $startDate 开始日期 YYYY-MMM-DD
     * @param string $endDate 结束日期 YYYY-MM-DD
     * @param int $storeId 店铺ID
     * @param int $page 页码
     * @param int $pageSize 页数
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws GuzzleException
     *                         获取明星推广数据
     */
    public function getStarPromotionReport(string $startDate, string $endDate, int $storeId, int $page = 1, int $pageSize = 10): array
    {
        $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
        $uri = 'https://yingxiao.pinduoduo.com/mms-gateway/apollo/api/report/queryEntityReport';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://yingxiao.pinduoduo.com/goods/report/standard/overView',
            'Cookie' => $this->cookie,
        ];
        $data = [
            'crawlerInfo' => $at,
            'entityId' => $storeId,
            'entityDimensionType' => 0,
            'queryDimensionType' => 1,
            'scenesType' => 1,
            'query' => [
                'fieldToValue' => new stdClass(),
            ],
            'externalFields' => ['planName', 'planId', 'adId', 'adName', 'adStatus', 'roomId', 'roomImageUrl', 'roomTitle', 'planStrategy', 'isDeleted', 'planDeleted', 'adDeleted'],
            'orderBy' => null,
            'orderType' => 0,
            'queryRange' => ['pageNumber' => $page, 'pageSize' => $pageSize],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'returnTotalSumReport' => true,
            'isStandardPromotion' => 0,
        ];

        return $this->sendPost($uri, $headers, $data, $at);
    }

    /**
     * @throws PddMerchantException
     * @throws GuzzleException
     *                         拼多多推广二次授权
     */
    public function promotion2Auth(): void
    {
        $uri = self::$PDD_MERCHANT_HOST . '/janus/api/subSystem/generateAccessToken';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/home/',
            'Cookie' => $this->cookie,
            'Verifyauthtoken' => $this->verifyAuthToken,
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
        // 滑块验证码异常
        if ($res['errorCode'] == self::$SLIDER_VERIFY) {
            $this->verifyAuthToken = $res['result']['verifyAuthToken'];
            throw new PddSliderVerifyException($res['errorMsg'] ?? 'Pdd promotion2Auth Unknown Error');
        }
        if ($res['errorCode'] != self::$REQUEST_OK && $res['errorCode'] != 1000) {
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd promotion2Auth Unknown Error');
        }
        foreach ($respCookies as $cookie) {
            $this->cookie = $this->cookie . $cookie . '; ';
        }
    }

    /**
     * @throws PddMerchantException
     * @throws GuzzleException
     *                         拼多多视频二次授权
     */
    public function ddsp2Auth(): void
    {
        $uri = self::$PDD_MERCHANT_HOST . '/janus/api/subSystem/generateAccessToken';
        $headers = [
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
            'Referer' => 'https://mms.pinduoduo.com/home/',
            'Verifyauthtoken' => $this->verifyAuthToken,
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
        // 滑块验证码异常
        if ($res['error_code'] == self::$SLIDER_VERIFY) {
            $this->verifyAuthToken = $res['result']['verifyAuthToken'];
            throw new PddSliderVerifyException($res['error_msg'] ?? 'Pdd ddsp2Auth Unknown Error');
        }

        if ($res['success'] === false) {
            throw new PddMerchantException($res['error_msg'] ?? 'Pdd ddsp2Auth Unknown Error');
        }
        foreach ($respCookies as $cookie) {
            $this->cookie = $this->cookie . $cookie . '; ';
        }
    }

    /**
     * @param string $accountName 拼多多商家账号
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws GuzzleException
     *                         获取验证码
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

    public function getVerifyAuthToken(): string
    {
        return $this->verifyAuthToken;
    }

    public function setVerifyAuthToken(string $verifyAuthToken): PddMerchant
    {
        $this->verifyAuthToken = $verifyAuthToken;
        return $this;
    }

    protected function sendPost(string $uri, array $headers, array|stdClass $body, string $at = ''): array|bool|string
    {
        $headers['Verifyauthtoken'] = $this->verifyAuthToken;
        if ($at === '') {
            $at = $this->pddEncryptionRemoteApi->getAntiContent($this->userAgent);
            $headers['Anti-Content'] = $at;
        }
        $res = $this->client->post($uri, ['headers' => $headers, 'json' => $body])->getBody()->getContents();
        $res = json_decode($res, true);
        $errorField = 'errorCode';
        $errorMsgField = 'errorMsg';
        if (! isset($res[$errorField])) {
            $errorField = 'error_code';
        }
        if (! isset($res[$errorMsgField])) {
            $errorMsgField = 'error_msg';
        }

        // 滑块验证码异常
        if ($res[$errorField] == self::$SLIDER_VERIFY) {
            $this->verifyAuthToken = $res['result']['verifyAuthToken'];
            throw new PddSliderVerifyException($res[$errorMsgField] ?? 'Pdd sendPost Unknown Error');
        }

        if ($res[$errorField] != self::$REQUEST_OK && $res[$errorField] != 0 && $res[$errorField] != 1000) {
            throw new PddMerchantException($res[$errorMsgField] ?? 'Pdd sendPost Unknown Error');
        }
        return $res['result'];
    }
}
