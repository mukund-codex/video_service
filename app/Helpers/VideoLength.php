<?php

namespace App\Helpers;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

class VideoLength {

    public static function getDuration($videoUrl){

        $ffprobe = FFProbe::create();
        $ffprobe
            ->format('/path/to/video/mp4') // extracts file informations
            ->get('duration'); 

    }

}

?>