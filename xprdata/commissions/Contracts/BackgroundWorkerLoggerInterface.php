<?php


namespace Commissions\Contracts;


interface BackgroundWorkerLoggerInterface
{
    public function log($message = "          ");
    public function getFilePath();
    public function getContent();
}