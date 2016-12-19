<?php

namespace Invest;

use Predis\ClientInterface;
use Psr\Log\LoggerInterface;

class ValueBase
{
    /**
     * @var \Predis\ClientInterface
     */
    protected $predis;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ValueBase constructor.
     * @param ClientInterface $predis
     */
    public function __construct(ClientInterface $predis)
    {
        $this->predis = $predis;
    }

    /**
     * @param string $currency
     * @param string $fromCurrency
     * @param \DateTime $date
     * @param string $value
     */
    public function save(string $currency, string $fromCurrency, \DateTime $date, string $value)
    {
        $this->predis->set(
            $this->getKey($currency, $fromCurrency, $date),
            $value
        );
    }

    public function checkIfAlreadyFetched(array $toCurrencies, string $fromCurrency, \DateTime $date): bool
    {
        foreach ($toCurrencies as $toCurrency) {
            if (!$this->get($toCurrency, $fromCurrency, $date)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $currency
     * @param string $fromCurrency
     * @param \DateTime $date
     * @return string
     */
    public function get(string $currency, string $fromCurrency, \DateTime $date): string
    {
        $value = $this->predis->get($this->getKey($currency, $fromCurrency, $date));

        if ($value === null) {
            $i = 4;
        }

        return $value;
    }

    /**
     * @param string $currency
     * @param string $fromCurrency
     * @param \DateTime $date
     * @return string
     */
    protected function getKey(string $currency, string $fromCurrency, \DateTime $date): string
    {
        return sprintf("%s_%s_%s", $currency, $fromCurrency, $date->format("Y-m-d"));
    }
}
