<?php

require_once __DIR__."/vendor/autoload.php";

$config = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__.'/config.yml'));

$bootstrap = new \Invest\Bootstrap($config);

$invest = $bootstrap->getInvest();

$invest->fetchStockData(new \DateTime("-3 year"), new \DateTime(), "PLN", ["USD", "EUR", "GBP"]);

echo "OK!";
