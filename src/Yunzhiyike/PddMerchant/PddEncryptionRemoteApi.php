<?php

declare(strict_types=1);
namespace Yunzhiyike\PddMerchant;
/**
 * This file is part of Yunzhiyike
 */

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
        $this->client = new Client(['timeout' => 60]);
    }

    public function getEncryptionPassword(string $password): string {
        $uri = $this->pddServiceApi.'/api/v1/pdd/antiContent?token='.$this->token.'&password='.$password;
        return '';
    }

    public function getAntiContent(string  $userAgent): string {
        $uri = $this->pddServiceApi.'/api/v1/pdd/antiContent?token='.$this->token.'&ua='.$userAgent;
        $res = $this->client->get($uri)->getBody()->getContents();
        $res = json_decode($res, true);
        if ($res['code'] != self::$REQUEST_OK) {
            throw new pddEncryptionRemoteApiException($res['message'] ?? 'PddEncryptionApi Unknown Error');
        }
        return $res['data']['antiContent'] ?? '';
    }
}
