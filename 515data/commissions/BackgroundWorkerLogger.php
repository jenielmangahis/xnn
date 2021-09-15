<?php


namespace Commissions;


use Commissions\Contracts\BackgroundWorkerLoggerInterface;

class BackgroundWorkerLogger implements BackgroundWorkerLoggerInterface
{
    protected $background_worker_id;
    protected $background_worker_process_id;
    protected $path;
    protected $counter = 0;

    public function __construct($path, $background_worker_id, $background_worker_process_id = null)
    {
        $this->background_worker_id = $background_worker_id;
        $this->background_worker_process_id = $background_worker_process_id;
        $this->path = $path;
    }

    public function getFilePath()
    {
        $name = str_pad($this->background_worker_id, 21, "0", STR_PAD_LEFT);
        return "{$this->path}/$name.log";
    }

    public function log($message = "          ")
    {
        $prefix = "";

        if($message != "          ") {
            $this->counter++;

            $process_id = +$this->background_worker_process_id;

            $prefix = "[{$process_id}-{$this->counter}] ";
        }

        echo $prefix . $message . PHP_EOL;
    }

    public function getContent($seek = 0)
    {
        if ($seek != null) {

            $lines = [];
            $handle = fopen($this->getFilePath(), 'rb');

            if ($seek > 0) {
                fseek($handle, $seek);
            }

            while (($line = fgets($handle, 4096)) !== false) {
                $lines[] = $line;
            }
            $seek = ftell($handle);

            return ['seek' => $seek, 'lines' => $lines];
        }
    }
}