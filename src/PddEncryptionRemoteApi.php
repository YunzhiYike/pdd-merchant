<?php

declare(strict_types=1);
/**
 * This file is part of Yunzhiyike
 */

namespace Yunzhiyike\PddMerchant;

use GuzzleHttp\Client;
use Yunzhiyike\PddMerchant\Exception\PddEncryptionRemoteApiException;

class PddEncryptionRemoteApi
{
    protected string $pddServiceApi;

    protected string $token;

    protected static int $REQUEST_OK = 200;

    protected static int $REQUEST_ERROR = 500;

    protected Client $client;

    public function __construct(string $pddServiceApi, string $token)
    {
        $this->pddServiceApi = $pddServiceApi;
        $this->token = $token;
        $this->client = new Client(['timeout' => 60, 'headers' => ['content-type' => 'application/json']]);
    }

    /**
     * @param string $password 密码
     * @throws PddEncryptionRemoteApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *                                               获取拼多多加密后的密码
     */
    public function getEncryptionPassword(string $password): string
    {
        $uri = $this->pddServiceApi . '/api/v1/pdd/encryptionPassword?token=' . $this->token . '&pwd=' . $password;
        $res = $this->client->get($uri)->getBody()->getContents();
        $res = json_decode($res, true);
        if ($res['code'] != self::$REQUEST_OK) {
            throw new PddEncryptionRemoteApiException($res['message'] ?? 'PddEncryptionApi Unknown Error');
        }
        return $res['data'] ?? '';
    }

    /**
     * @param string $userAgent 请求头
     * @throws PddEncryptionRemoteApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *                                               获取拼多多请求头校验参数Anti-Content
     */
    public function getAntiContent(string $userAgent): string
    {
        $uri = $this->pddServiceApi . '/api/v1/pdd/antiContent?token=' . $this->token . '&ua=' . $userAgent;
        $res = $this->client->get($uri)->getBody()->getContents();
        $res = json_decode($res, true);
        if ($res['code'] != self::$REQUEST_OK) {
            throw new PddEncryptionRemoteApiException($res['message'] ?? 'PddEncryptionApi Unknown Error');
        }
        return $res['data']['antiContent'] ?? '';
    }
}
