<?php
require "vendor/autoload.php";
include_once "loops/MessageLoop.php";
include_once "loops/ProducerLoop.php";
include_once "loops/PersistQueuesLoop.php";



$producerSendChannel = parallel\Channel::make('SEND', 1);
$producerReplyChannel = parallel\Channel::make('RECV', 1);