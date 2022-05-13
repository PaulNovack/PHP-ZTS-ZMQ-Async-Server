<?php
set_time_limit(0);
use parallel\{Channel, Runtime};

class WorkerBase
{
    public $maxThreads; //  Max threads to run at once
    public $messageName; // Name of child class
    public $inQueue;  // In Queue not sent to a worker thread;
    public $processing; // Sent to be processed in a thread
    public $threadGapMicroseconds; // automatic sleep put on thread when added to proccessing
    public $nanoTime; // get microttime incremented though to make unique keys
    public $lastNanoTime; // make sure nanotime increments
    public $queueDataDir; // the directory queued items are persisted by key name and contents payload
    public $processDataDir; // the directory in process items are persisted to disk by key name and contents pauload
    public $autoPersistQueueMilliseconds; // every 5 minutes when the queues are written to disk (persisted) automatically
    public $NDB; // Database class
    public $itemsDoneCount;
    function __construct($hIndex = null,$settings,$final = false,NDB $NDB)
    {
        $this->messageName = get_class($this);
        $baseDirectory = dirname(__FILE__,3);
        $DataDir = $baseDirectory . '/' . 'queueData';
        if (!file_exists($DataDir)) {
            mkdir($DataDir);
        }
        $outputDirectory = $baseDirectory . '/' . 'data';
        if (!file_exists($outputDirectory)) {
            mkdir($outputDirectory);
        }
        $classDir = $DataDir . '/' . $this->messageName;
        if (!file_exists($classDir)) {
            mkdir($classDir);
        }
        $this->queueDataDir = $classDir . '/' . 'queue';
        $this->processDataDir = $classDir . '/' . 'processing';
        if (!file_exists($this->queueDataDir)) {
            mkdir($this->queueDataDir);
        }
        if (!file_exists($this->processDataDir)) {
            mkdir($this->processDataDir);
        }
        $this->nanoTime = hrtime(true);
        $this->autoPersistQueueMilliseconds = 300000; // every 5 minutes
        $this->maxThreads = 5;
        $this->threadGapMicroseconds = 10 /* milliseconds X 10 to microseconds */ * 1000; // default 1/10 of second
        $this->inQueue = [];
        $this->processing = [];
        $this->NDB = $NDB;
        $this->itemsDoneCount = 0;
    }

    public function HandleMessage($request)
    {
        if($this->nanoTime <= $this->lastNanoTime){
            $this->nanoTime = $this->lastNanoTime = $this->lastNanoTime + 1;
        } else {
            $this->lastNanoTime = $this->nanoTime;
        }
        if (property_exists($request, $this->messageName)) {
            $this->processMessage($request);
            return '{"' . $this->messageName . '": true}';
        } else if (property_exists($request, $this->messageName . 'Queued')) {
            return $this->getQueued();
        } else if (property_exists($request, $this->messageName . 'Processing')) {
            return $this->getProcessing();
        } else if (property_exists($request, 'IPC_' . $this->messageName . 'ProcessingPop')) {
            $this->processingPop($request);
        } else if (property_exists($request, 'Shutdown')) {
            return $this->persistQueues();
        } else if (property_exists($request, 'PersistQueue')) {
            return $this->persistQueues();
        }
    }
    private function processingPop($request)
    {
        $unsetProcessKey = $request->{'IPC_' . $this->messageName . 'ProcessingPop'};
        if (count($this->processing) > $this->maxThreads) {
            //echo "Exceeds count before key check" . PHP_EOL;
        }
        if (array_key_exists($unsetProcessKey, $this->processing)) {
            unset($this->processing[(int)$unsetProcessKey]);
            $this->itemsDoneCount++;
            //echo "Count in Queue: " . count($this->inQueue) . " Count in Process "
            //    . count($this->processing) . " Items Done: " . $this->itemsDoneCount . PHP_EOL;
            if (count($this->inQueue) > 0) {
                reset($this->inQueue);
                $processidx = array_key_first($this->inQueue);
                $this->processing[(int)$processidx] = $this->inQueue[(int)$processidx];
                unset($this->inQueue[$processidx]);
                reset($this->inQueue);
                reset($this->processing);
                if (count($this->processing) > $this->maxThreads) {
                    file_put_contents("badQueue.json", json_encode($this->processing, JSON_PRETTY_PRINT));
                    file_put_contents("badQueue.json", $unsetProcessKey, FILE_APPEND);
                }
                $this->runThread($processidx, $this->messageName, $this->processing[(int)$processidx], $this->threadGapMicroseconds, $this->NDB);
            }
        } else {
            echo "ERROR IN: " . $unsetProcessKey . " Index" . PHP_EOL;
        }
    }

    private function processMessage($request)
    {
        $sleepMicroSeconds = 0;
        // Uncomment to show bug $this->nanoTime = floor(microtime(TRUE) * 1000);
        $this->nanoTime = hrtime(true);
        if($this->nanoTime <= $this->lastNanoTime){
            $this->nanoTime = $this->lastNanoTime = $this->lastNanoTime + 1;
        } else {
            $this->lastNanoTime = $this->nanoTime;
        }
        $processidx = $this->nanoTime;
        // Uncomment to show bug echo "ProcessIDX: " . $processidx . PHP_EOL;
        foreach ($request->{$this->messageName} as $payload) {
            if (count($this->processing) < $this->maxThreads) {
                //echo "Payload to run: " . $payload . PHP_EOL;
                $this->processing[(int)$processidx] = $payload;
                $sleepMicroSeconds += $this->threadGapMicroseconds;
                $this->runThread($processidx, $this->messageName, $payload, $sleepMicroSeconds,$this->NDB);
            } else {
                $this->inQueue[(int)$processidx] = $payload;
            }
            $processidx++;
        }
    }

    private function getQueued()
    {
        return '{"' . $this->messageName . 'Queued":' . json_encode(array_values($this->inQueue)) . "}";
    }

    private function getProcessing()
    {
        return '{"' . $this->messageName . 'Processing":' . json_encode(array_values($this->processing)) . "}";
    }

    public function loadQueues()
    {
        echo "loading persistant queues for $this->messageName . '....." . PHP_EOL;

        $processingItems = preg_grep('/^([^.])/', scandir($this->processDataDir));

        // need to put processing items back in through the Handler message not just load array
        foreach($processingItems as $filename){
            //echo $filename . PHP_EOL;
            $payload = file_get_contents($this->processDataDir . "/" . $filename);
            //echo $payload  . PHP_EOL;
            $message = '{"' . $this->messageName . '": [' . $payload . ']}';
            //echo $message . PHP_EOL;
            $this->HandleMessage(json_decode($message));
        }
        $queueItems = preg_grep('/^([^.])/', scandir($this->queueDataDir));
        foreach ($queueItems as $filename) {
            if(is_array($this->processing)  && is_array($this->maxThreads) && sizeof($this->processing < $this->maxThreads)){
                $payload = file_get_contents($this->queueDataDir . "/" . $filename);
                //echo $payload  . PHP_EOL;
                $message = '{"' . $this->messageName . '": [' . $payload . ']}';
                //echo $message . PHP_EOL;
                $this->HandleMessage(json_decode($message));
            } else {
                $this->inQueue[pathinfo($filename)['filename']] = json_decode(file_get_contents($this->queueDataDir . "/" . $filename));
            }
        }
        echo "queues loaded server is up." . PHP_EOL;
    }

    public function persistQueues()
    {
        // clear old data first
        $queueItems = preg_grep('/^([^.])/', scandir($this->queueDataDir));
        foreach ($queueItems as $filename) {
            unlink($this->queueDataDir . "/" . $filename);
        }
        $processingItems = preg_grep('/^([^.])/', scandir($this->processDataDir));
        foreach ($processingItems as $filename) {
            unlink($this->processDataDir . "/" . $filename);
        }
        // write current queues
        foreach ($this->inQueue as $idx => $payload) {
            if($payload !== null) {
                file_put_contents($this->queueDataDir . "/" . $idx . ".item", json_encode($payload));
            }
        }
        foreach ($this->processing as $idx => $payload) {
            if($payload !== null) {
                file_put_contents($this->processDataDir . "/" . $idx . ".item", json_encode($payload));
            }
        }
        return false;
    }
}