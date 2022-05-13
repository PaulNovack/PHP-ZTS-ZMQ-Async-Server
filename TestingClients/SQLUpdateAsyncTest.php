<?php
$startTime = microtime(true); //get time in micro seconds(1 millionth)
$context = new ZMQContext();
echo "Connecting to server...\n";
$searches = [];
$requester = new ZMQSocket($context, ZMQ::SOCKET_REQ);
$requester->connect("tcp://localhost:5555");
$requestNumber = 50000;
$num = 1;
for($runs = 0; $runs < $requestNumber; $runs++){
    for ($request_nbr = 0; $request_nbr < 10; $request_nbr++) {
        // printf ("Sending request %d...\n", $request_nbr);
        $user_id =    rand(0,100000000);
        $name =    rand(0,100000000);
        $weight =  rand(1,100000000);
        $picture = rand(1,100000000);
        $data = new stdClass();
        $data->SQLUpdateAsync = array("INSERT INTO boxes.boxes
            (
            user_id,
            name,
            weight,
            picture,
            created_at)
            VALUES (
            '$user_id',
            '$name',
            $weight,
            '$picture',
            now()
            )");
        $requester->send(json_encode($data));
        $reply = $requester->recv();
    }
}
$endTime = microtime(true);
$ms = round(($endTime-$startTime)*1000);
$micros = ($endTime-$startTime) * 1000000;
echo "milliseconds to queue 500,000 SQL statements:". $ms . PHP_EOL;
echo "Microseconds per queue item : " .  floor($micros / ($requestNumber * 10)) . PHP_EOL;



