<?php

require_once __DIR__."/vendor/autoload.php";

$config = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__.'/config.yml'));

$bootstrap = new \Invest\Bootstrap($config);

$calculator = $bootstrap->getCalculator();

$start = 5000;
foreach (["GBP", "EUR", "USD"] as $currency) {
    for ($i = 90; $i < 120; $i += 10) {
        $value = $calculator->simulate($start, "32M", $currency, $i);

        echo sprintf("%d: %f<br />", $i, $value - $start);
    }

    echo "OK!<br />";
}
