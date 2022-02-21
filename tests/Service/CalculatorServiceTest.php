<?php

namespace App\Tests\Service;

use App\Service\CalculatorService;
use App\Service\OutputService;
use PHPUnit\Framework\TestCase;

class CalculatorServiceTest extends TestCase
{
    public function testProcess()
    {
        $outputService = $this->createMock(OutputService::class);

        $calculatorService = new CalculatorService($outputService);
        $data = [
            ['', 'EUR', 1000, 'PLN', '4310'],
            ['', 'PLN', 4000, 'EUR', '1000'],
        ];

        $result = $calculatorService->process($data);
        $this->assertEquals(expected: -310, actual: $result);
    }
}
