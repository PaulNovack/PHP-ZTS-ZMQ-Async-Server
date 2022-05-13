<?php

class DB extends NDB
{
    static public function select($sql){
        $obj = new DB();
        $data = $obj->rSQL($sql);
        return $data;
    }
    static public function raw($sql){
        return $sql;
    }
    static public function statement($sql){
        $obj = new DB();
        $data = $obj->wSQL($sql);
        return $data;
    }
    static public function Connection($sql){
        return (new self);
    }
}