<?php

use parallel\{Channel, Runtime};

$producerLoop = function ($producerSendChannel, $producerReplyChannel) {
    // List all Workers here that handle messages
    include_once dirname(__FILE__,2) . "/classes/NDB.php";
    include_once dirname(__FILE__,2) . "/classes/Settings.php";
    $settings =  new Settings(json_decode(file_get_contents(dirname(__FILE__,2) . "/.env.json")));
    $NDB = new NDB($settings);
    include_once dirname(__FILE__, 2) . "/rpcs/SQLUpdateAsync.php";
    $handlers = [];
    $idx = 0;
    $handlers[$idx] =  new SQLUpdateAsync($idx++,$settings,true, $NDB);
    while (true) {
        $IPCMessage = false;
        $response = '{"Error": true}';
        //  Wait for next request from client
        $message = $producerSendChannel->recv();
        if(strpos($message,"IPC_") !== false){
            $IPCMessage = true;
        }
        $request = json_decode($message);
        foreach($handlers as $handler){
            if($handlerResponse = $handler->HandleMessage($request)){
                $response = $handlerResponse;
                break;
            }
        }
        if(strpos($message,"PersistQueue") !== false){
            $producerReplyChannel->send('{"PersistQueue" : true}');
        } else if(strpos($message,"Shutdown") !== false){
            $producerReplyChannel->send('{"Shutdown" : true}');
            $pid = getmypid();
            system("kill -9 " . $pid);
        } else if(!($IPCMessage)){
            $producerReplyChannel->send($response);
        }
    }
};