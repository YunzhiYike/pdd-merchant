<?php

declare(strict_types=1);
namespace Yunzhiyike\PddMerchant;
/**
 * This file is part of Yunzhiyike
 */

use GuzzleHttp\Client;
use Yunzhiyike\PddMerchant\Exception\PddMerchantException;

class PddMerchantBase
{
    protected Client $client;

    protected PddEncryptionRemoteApi $pddEncryptionRemoteApi;

    protected string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36';

    protected static string $PDD_MERCHANT_HOST = 'https://mms.pinduoduo.com';

    public static int $REQUEST_OK = 1000000;

    public static int $IS_NOT_DAREN = 3000000;


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
        $res = $this->client->post($uri, ['headers' => $headers, 'body' => $body])->getBody()->getContents();
        $res = json_decode($res, true);
        var_dump($res);
        if ($res['errorCode'] != self::$REQUEST_OK) {
            throw new PddMerchantException($res['errorMsg'] ?? 'Pdd sendSmsCode Unknown Error');
        }

    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): PddMerchant
    {
        $this->userAgent = $userAgent;
        return $this;
    }
}
