<?php

namespace App\nxm\models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class BackgroundWorkerMessages extends Eloquent
{
    protected $table = 'cm_background_worker_messages';

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message) {
        $this->message .= $message;
        return $this;
    }

    /**
     * @param string $worker_id
     * @return $this
     */
    public function setWorkerId($worker_id) {
        $this->worker_id = $worker_id;
        return $this;
    }
}