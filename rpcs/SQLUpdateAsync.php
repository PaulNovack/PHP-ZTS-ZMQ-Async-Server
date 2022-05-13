<?php
set_time_limit(0);
use parallel\{Channel, Runtime};

include_once "WorkerBase.php";

class SQLUpdateAsync extends WorkerBase
{
    function __construct($hIndex = null,$settings,$final = false,NDB $NDB)
    {
        parent::__construct($hIndex,$settings,$final, $NDB);
        // BASED ON IMPLMENTATION UPDATE THESE 2 VALUES
        $this->maxThreads = 50;
        $second = 1000000; //microseconds in second
        $this->threadGapMicroseconds = (int)(1000000 / 50); // 10 per second need to have at least a small sleep.
        $this->loadQueues();
    }

    public function HandleMessage($request)
    {
        $result = parent::HandleMessage($request);
        /// add custom worker messages here if needed
        return $result;
    }

    public function runThread($processidx, $messageName, $payload, $sleepMicroSeconds,$NDB)
    {
        $threadFunc = function ($processidx, $messageName, $payload, $sleepMicroSeconds,$NDB){
            try {
                //echo "Payload in thread: " . $payload . PHP_EOL;
                usleep($sleepMicroSeconds);
                $producerSendChannel = parallel\Channel::open('SEND');
                $data = [];
                try{
                    $data = $NDB->wSQL($payload);
                } catch(Exception $e){
                    file_put_contents(dirname(__FILE__,3) . "/logs/exception.log", $e->getMessage(), FILE_APPEND);
                }
                $sendMessage = '{"IPC_' . $messageName . 'ProcessingPop": ' . '"' . $processidx . '"}';
                $producerSendChannel->send($sendMessage);
            } catch (Exception $e) {
                file_put_contents(dirname(__FILE__,3) . "/logs/exception.log", $e->getMessage(), FILE_APPEND);
            }

        };
        \parallel\run($threadFunc, [$processidx, $messageName, $payload, $sleepMicroSeconds,$NDB]);
    }
}