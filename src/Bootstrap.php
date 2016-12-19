<?php

namespace Invest;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Predis\Client;

class Bootstrap
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config['parameters'];
    }

    public function getInvest(): Invest
    {
        $client = new \GuzzleHttp\Client();

        $logger = new Logger("app");
        $logger->pushHandler(new StreamHandler(__DIR__."/../logs/invest.log"));

        $invest = new Invest($client, $this->getValueBase(), $this->config['url'], $logger);

        return $invest;
    }

    public function getCalculator(): Calculator
    {
        return new Calculator($this->getValueBase());
    }

    protected function getValueBase(): ValueBase
    {
        $predis = new Client([
            'host' => $this->config['redis_host']
        ]);
        $valueBase = new ValueBase($predis);

        return $valueBase;
    }
}
