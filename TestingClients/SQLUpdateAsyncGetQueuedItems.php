<?php
$startTime = microtime(true); //get time in micro seconds(1 millionth)
$context = new ZMQContext();
$requester = new ZMQSocket($context, ZMQ::SOCKET_REQ);
$requester->connect("tcp://localhost:5555");
// Get items in queue
$requester->send('{"SQLUpdateAsyncQueued":true}');
$reply = $requester->recv();
//printf ("Received reply:%s\n", $reply);
//echo "Reply was: " . $reply;
$decoded = json_decode($reply);
//var_dump($decoded);
//echo "Reply 1" . PHP_EOL;
//echo $reply;
echo "items in Queue: " . count($decoded->SQLUpdateAsyncQueued) . PHP_EOL;


// get items in process
$requester->send('{"SQLUpdateAsyncProcessing":true}');
$reply = $requester->recv();
$decoded = json_decode($reply);
//echo "Reply 2" . PHP_EOL;
//echo $reply;
//var_dump($decoded->FileTestProcessing);
echo "items in process: " . count($decoded->SQLUpdateAsyncProcessing) . PHP_EOL;
$decoded = json_decode($reply);


$endTime = microtime(true);
$ms = round(($endTime-$startTime)*1000);
$micros = ($endTime-$startTime) * 1000000;

echo "Milliseconds for requests: " .  floor($ms) . PHP_EOL;
echo "Microseconds for requests: " .  floor($micros) . PHP_EOL;



