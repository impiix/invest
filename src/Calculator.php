<?php


namespace Invest;


class Calculator
{
    protected $currency = "PLN";

    protected $changed = false;

    /**
     * @var ValueBase
     */
    protected $valueBase;

    public function __construct(ValueBase $valueBase)
    {
        $this->valueBase = $valueBase;
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
            $value = 1 / $this->valueBase->get($currency, $this->currency, $tmpDate);
            $values[] = $value;
        }

        for ($tmpDate = $date; $tmpDate < $currentDate; $tmpDate->add(new \DateInterval("P1D"))) {
            $value = 1/ $this->valueBase->get($currency, $this->currency, $tmpDate);
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
