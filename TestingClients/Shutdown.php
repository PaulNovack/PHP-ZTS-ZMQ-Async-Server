<?php
$startTime = microtime(true); //get time in micro seconds(1 millionth)



$context = new ZMQContext();



$requester = new ZMQSocket($context, ZMQ::SOCKET_REQ);
$requester->connect("tcp://localhost:5555");

$requester->send('{"Shutdown":true}');
$reply = $requester->recv();
$decoded = json_decode($reply);
var_dump($decoded);

$endTime = microtime(true);
$ms = round(($endTime-$startTime)*1000);
$micros = ($endTime-$startTime) * 1000000;

echo "Milliseconds for request: " .  floor($ms) . PHP_EOL;
echo "Microseconds for request: " .  floor($micros) . PHP_EOL;
