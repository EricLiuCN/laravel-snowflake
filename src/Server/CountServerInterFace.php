<?php

namespace Ericliucn\LaravelSnowflake\Server;


interface CountServerInterFace
{
    public function getSequenceId($key);
}