<?php

class Settings
{
  public $DBReaderHostName;
  public $DBReaderUserName;
  public $DBReaderPassword;
  public $DBReaderDatabaseName;
  public $DBWriterHostName;
  public $DBWriterUserName;
  public $DBWriterPassword;
  public $DBWriterDatabaseName;
  private $DBReaderDatbaseName;

    public function __construct($config){
      $this->DBReaderHostName = $config->DBReaderHostName;
      $this->DBReaderUserName = $config->DBReaderUserName;
      $this->DBReaderPassword = $config->DBReaderPassword;
      $this->DBReaderDatbaseName = $config->DBReaderDatabaseName;
      $this->DBWriterHostName = $config->DBWriterHostName;
      $this->DBWriterUserName = $config->DBWriterUserName;
      $this->DBWriterPassword = $config->DBWriterPassword;
      $this->DBWriterDatabaseName = $config->DBWriterDatabaseName;
  }
}