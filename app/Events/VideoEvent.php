<?php

namespace App\Events;

use Log;

class VideoEvent extends Event
{   

    public $requestId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($requestId)
    {
        //
        $this->requestId = $requestId;
        Log::info('Event - Request ID : '.$this->requestId);
    }
}
