<?php

use parallel\{Channel, Runtime};

$producerLoop = null;
$messageLoop = null;
$persistQueues = null;
$producerSendChannel = null;
$producerReplyChannel = null;
require "bootstrap.php";

// if another instance is running shut it down
//$result = shell_exec('sudo kill -9 `sudo lsof -i -P -n | grep LISTEN | grep 5555 | cut -c 11-16`');

\parallel\bootstrap('parallelBootstrap.php');
//$pid = system("sudo lsof -i -P -n | grep LISTEN | grep 5555 | cut -c 11-16");
//system('sudo kill -9 ' . $pid);
// The Loop for IPC communication between threads
\parallel\run($producerLoop, [$producerSendChannel,$producerReplyChannel]);

// The loop to receive messages
\parallel\run($messageLoop, [$producerSendChannel,$producerReplyChannel]);

// The loop to  autosave queues to disk
\parallel\run($persistQueues, []);


