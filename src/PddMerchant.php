<?php

declare(strict_types=1);
/**
 * This file is part of Yunzhiyike
 */

namespace Yunzhiyike\PddMerchant;

use GuzzleHttp\Client;
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
        var_dump($res);
        if ($res['errorCode'] != self::$REQUEST_OK) {
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd sendSmsCode Unknown Error');
        }
        return $res['result'];
    }

    /**
     * @throws Exception\PddEncryptionRemoteApiException
     * @throws PddMerchantException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *                                               获取验证码
     */
    public function finances2Auth(): void
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
}
