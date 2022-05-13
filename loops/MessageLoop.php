<?php
use parallel\{Channel, Runtime};

$messageLoop = function ($producerSendChannel,$producerReplyChannel) {
    $context = new ZMQContext(1);
    $responder = new ZMQSocket($context, ZMQ::SOCKET_REP);
    $responder->bind("tcp://*:5555");
    while (true) {
        //  Wait for next request from client
        $request = $responder->recv();
        $producerSendChannel->send($request);
        $response = $producerReplyChannel->recv();
        $responder->send($response);
    }
};