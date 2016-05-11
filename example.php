<?php
/**
 * File: example.php
 * Copyright 2016 Anton Lempinen <bafoed@bafoed.ru>
 * This file is part of TorControl project.
 */

require_once(dirname(__FILE__) . '/vendor/autoload.php');

function tor_cURL($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_PROXY, 'localhost:9050');
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


$ipAddressInfo = tor_cURL('http://ifconfig.co/json');
echo $ipAddressInfo . PHP_EOL;

$control = new TorControl('localhost', 9051, '123456'); // connect to TorControl with password 123456

echo 'Sending change IP request...' . PHP_EOL;

$control->changeIP(); // make new identity request

echo 'Waiting for 5 seconds...' . PHP_EOL;
sleep(5);

$ipAddressInfo = tor_cURL('http://ifconfig.co/json');
echo $ipAddressInfo . PHP_EOL;
