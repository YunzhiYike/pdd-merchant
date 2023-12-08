<?php

declare(strict_types=1);
/**
 * This file is part of Yunzhiyike
 */
use GuzzleHttp\Client;

class PddMerchant
{
    protected Client $client;

    protected static string $PDD_MERCHANT_HOST = '';

    /**
     * @param int $requestTimeout 请求超时时间
     * @param string $pddServiceApi 远程拼多多API接口服务地址
     */
    public function __construct(int $requestTimeout, string $pddServiceApi)
    {
        $this->client = new Client(['timeout' => $requestTimeout]);
    }

    public function sendSmsCode(string $accountName): void
    {

        $this->client->get();
    }
}
