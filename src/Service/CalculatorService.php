<?php

namespace App\Service;

class CalculatorService
{
    protected $mainCurrency = "PLN";

    protected array $bank;

    protected $changed = false;

    public function __construct(protected ?OutputService $outputService = null)
    {
    }

    public function process(array $data): float|int
    {

        foreach ($data as $row) {
            $currency = $row[3];
            $toCurrency = $row[1];
            $toAmount = $row[2];
            $fromAmount = $row[4];
            $toCurrency !== $this->mainCurrency ? $this->buy($toCurrency, $toAmount, $fromAmount)
                : $this->sell($currency, $fromAmount, $toAmount);
        }

        return $this->bank[$this->mainCurrency];
    }

    protected function buy(string $currency, $toAmount, $fromAmount)
    {
        if (!isset($this->bank[$currency])) {
            $this->bank[$currency] = [];
        }
        if (!isset($this->bank[$this->mainCurrency])) {
            $this->bank[$this->mainCurrency] = 0;
        }
        $this->bank[$currency][] = [
            'native' => $toAmount,
            'main' => $fromAmount
        ];

        $this->output(sprintf("Bought %s of %s with %s\n", $toAmount, $currency, $fromAmount));

        $i = 3;
    }

    protected function output($output)
    {
        $this->outputService->output($output);
        $this->outputService->outputArray($this->bank);


        $this->outputService->output('');
    }

    protected function sell(string $currency, $fromAmount, $toAmount)
    {
        $count = count($this->bank[$currency]);
        $originalToAmount = $toAmount;
        $originalFromAmount = $fromAmount;
        for ($i = 0; $i < $count; $i++) {
            $record = $this->bank[$currency][$i];


            if ($fromAmount >= $record['native']) {
                $fromAmount -= $record['native'];
                $toAmount -= $record['main'];
                unset($this->bank[$currency][$i]);
                if ($fromAmount == 0) {
                    break;
                } else {
                    continue;
                }
            }
            if ($fromAmount < $record['native']) {
                $this->bank[$currency][$i]['native'] -= $fromAmount;
                $remainingValue = $this->bank[$currency][$i]['native'] / $record['native'] * $record['main'];
                $this->bank[$currency][$i]['main'] = $remainingValue;
                $result = $toAmount - $fromAmount / $record['native'] * $record['main'];
                $toAmount = $result;
                break;
            }

        }

        $this->bank[$this->mainCurrency] += $toAmount;

        $this->bank[$currency] = array_values($this->bank[$currency]);

        $this->output(sprintf("Sold %s of %s with %s and %s gain\n", $originalFromAmount, $currency, $originalToAmount, $toAmount));

        $i = 3;
    }

    /**
     * @param int $startAmount
     * @param string $dateDefinition
     * @param string $currency
     * @param int $backwardCheckDays
     * @return float
     */
    public function simulate(int $startAmount, string $dateDefinition, string $currency, int $backwardCheckDays): float
    {
        $currentDate = new \DateTime();
        $date = clone $currentDate;
        $date->sub(new \DateInterval(sprintf("P%s", $dateDefinition)));
        $preDate = clone $date;
        $preDate->sub(new \DateInterval(sprintf("P%dD", $backwardCheckDays)));
        $values = [];

        for ($tmpDate = $preDate; $tmpDate < $date; $tmpDate->add(new \DateInterval("P1D"))) {
            if ($this->valueBase->get($currency, $this->currency, $tmpDate) === null) {
                continue;
            }
            $value =  $this->valueBase->get($currency, $this->currency, $tmpDate);
            $a = $this->valueBase->get($currency, $this->currency, $tmpDate);
            $values[] = $value;
        }

        for ($tmpDate = $date; $tmpDate < $currentDate; $tmpDate->add(new \DateInterval("P1D"))) {
            if ($this->valueBase->get($currency, $this->currency, $tmpDate) === null) {
                continue;
            }
            $value =  $this->valueBase->get($currency, $this->currency, $tmpDate);
            $values[] = $value;
            if (!$this->changed) {
                $min = min($values);
                if ($value === $min) {
                    $startAmount = $startAmount / $value;
                    $this->changed = true;
                }
            } else {
                $max = max($values);
                if ($value === $max || $tmpDate->format("Y-m-d") == (new \DateTime())->format("Y-m-d")) {
                    $startAmount = $startAmount * $value;
                    $this->changed = false;
                }
            }
            array_shift($values);
        }

        return $startAmount;
    }
}
