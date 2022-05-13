<?php

include_once "Settings.php";

class NDB
{
    public $settings;
    private $readerConn;
    private $writerConn;
    private $readerConnected;
    private $writerConnected;

    function __construct($settings = null){
        if($settings == null){
            $settings =  new Settings(json_decode(file_get_contents(dirname(__FILE__,3) . "/.env.json")));
            $this->settings = $settings;
        }
        // No connect on create each thread will use its own connection
        $this->settings = $settings;
    }

    public static function q($var)
    {
        return (new self())->readerConn->quote($var);
    }

    public function wSQL($sql): bool
    {
        if (!$this->writerConnected) {
            $this->writerConnect();
        }
        $result = $this->writerConn->query($sql);
        if (!$result) {
            throw new Exception(mysqli_error($this->writerConn));
        }
        return true;
    }

    public function rSQL($sql): array
    {
        $data = [];
        if (!$this->readerConnected) {
            $this->readerConnect();
        }
        $result = $this->readerConn->query($sql);
        if (!$result) {
            echo mysqli_error($this->readerConn);
            throw new Exception(mysqli_error($this->readerConn));
        }
        while ($row = $result->fetch_object()) {
            array_push($data, $row);
        }
        return $data;
    }

    private function readerConnect(): bool
    {
        try{
            $this->readerConn = new mysqli($this->settings->DBReaderHostName
                , $this->settings->DBReaderUserName
                , $this->settings->DBReaderPassword
                , $this->settings->DBReaderDatabaseName);
            $this->readerConnected = true;
            $this->readerConn->query("use " . $this->settings->DBReaderDatabaseName);
            return true;
        } catch(Exception $e){
            file_put_contents(dirname(__FILE__,3) . "/logs/exception.log", $e->getMessage(), FILE_APPEND);
        }
        return true;
    }

    private function writerConnect(): bool
    {
        try{
            // if running any long processes may need to reconnect always sql timeout... probably 600 seconds
            $this->writerConn = new mysqli($this->settings->DBWriterHostName
                , $this->settings->DBWriterUserName
                , $this->settings->DBWriterPassword
                , $this->settings->DBWriterDatabaseName);
            $this->writerConnected = true;
            $this->writerConn->query("use " . $this->settings->DBWriterDatabaseName);
        } catch(Exception $e){
            file_put_contents(dirname(__FILE__,3) . "/logs/exception.log", $e->getMessage(), FILE_APPEND);
        }
        return true;
    }
}