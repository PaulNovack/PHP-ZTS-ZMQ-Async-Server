<?php
use parallel\{Channel, Runtime};

$persistQueues = function () {
    while(true){
        sleep(60);
        $context = new ZMQContext();
        $requester = new ZMQSocket($context, ZMQ::SOCKET_REQ);
        $requester->connect("tcp://localhost:5555");
        //echo "Persisted Queue" . PHP_EOL;
        $requester->send('{"PersistQueue":true}');
        $reply = $requester->recv();
        $decoded = json_decode($reply);
    }
};