<?php

namespace Invest;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;

class Invest implements InvestInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var ValueBase
     */
    protected $valueBase;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(ClientInterface $client, ValueBase $valueBase, string $url, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->valueBase = $valueBase;
        $this->url = $url;
        $this->logger = $logger;
    }

    public function fetchStockData(
        \DateTime $start,
        \DateTimeInterface $end,
        string $fromCurrency,
        array $toCurrencies
    ) {
        for ($i = $start; $i < $end; $i->add(new \DateInterval("P1D"))) {
            if ($this->valueBase->checkIfAlreadyFetched($toCurrencies, $fromCurrency, $i)) {
                continue;
            }
            $url = str_replace(
                ["%date%", "%from_currency%", "%to_currencies%"],
                [$i->format("Y-m-d"), $fromCurrency, implode(",", $toCurrencies)],
                $this->url
            );
            try {
                $response = $this->client->request("get", $url);
            } catch (ConnectException $e) {
                $response = $this->client->request("get", $url);
            }
            $contents = $response->getBody()->getContents();
            $data = json_decode($contents, true);
            $this->logger->info(sprintf("Fetched info for %s - %s.", $i->format("Y-m-d"), $fromCurrency));
            foreach ($toCurrencies as $currency) {
                $this->valueBase->save($currency, $fromCurrency, $i, $data['rates'][$currency]);
            }
        }

    }
}
