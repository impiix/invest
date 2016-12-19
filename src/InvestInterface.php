<?php

namespace Invest;

interface InvestInterface
{
    public function fetchStockData(
        \DateTime $start,
        \DateTimeInterface $end,
        string $fromCurrency,
        array $toCurrencies
    );
}
