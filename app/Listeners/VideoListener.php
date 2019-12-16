<?php

namespace App\Listeners;

use App\Events\VideoEvent;
use App\Jobs\VideoJob;
use Log;

class VideoListener
{   
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  VideoEvent  $event
     * @return void
     */
    public function handle(VideoEvent $event)
    {
        //
        $requestId = $event->requestId;
        Log::info('Listener - Request ID : '.$requestId);
        if(!empty($requestId)){
            \dispatch(new VideoJob($requestId));
        }
    }
}
