<?php


namespace Commissions;


use Carbon\Carbon;
use Exception;
use DateTime;

class Console
{

    protected function log($message, $time = true)
    {
        if (php_sapi_name() !== 'cli') return;

        if ($time) {
            $t = Carbon::now()->toDateTimeString();
            $message = "[{$t}] - {$message}";
        }

        echo $message . PHP_EOL;
    }

    protected function log_debug($message)
    {
        $this->log(print_r($message, true), false);
    }

    protected function throwIfInvalidDateFormat($date)
    {
        if (!DateTime::createFromFormat('Y-m-d', $date)) throw new Exception("Invalid date format. Use Y-m-d format for date.");
    }

    protected function getRealCarbonDateParameter($date)
    {
        if ($date === 'now') {

            $end_date = Carbon::now();

        } elseif ($date != null) {

            $this->throwIfInvalidDateFormat($date);

            $end_date = Carbon::createFromFormat('Y-m-d', $date);

        } else {

            $end_date = Carbon::yesterday();

        }

        return $end_date;
    }
}