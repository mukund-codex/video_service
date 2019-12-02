<?php
namespace App\Http\Controllers\Process;
use App\Http\Controllers\System\Controller;
use Illuminate\Http\Request;
use Log;
// https://www.web-technology-experts-notes.in/2018/10/how-to-add-formated-text-on-video-using-php-ffmpeg.html

class VideoController extends Controller {

    public function __construct() {}

    public function index() {
    }

    public function store(Request $request) {

        $temp = $request->input('video');
        Log::info('Request Initiated:: ', [
            'Request' => $request->all(), 
            'Request Time' => date('Y-m-d H:i:s')
        ]);

        $video = json_decode($temp);
        $errors = [];

        if(!count($video)) {
            $errors = ['data' => 'Data cannot be empty!'];
            $this->throwError($errors);
        }

        $video = $video[0];
        $overlay = $frames = [];

        if(!isset($video->video_url)) {
            $errors = ['video_url' => 'Video URL is Required!'];
            $this->throwError($errors);
        }

        if(isset($video->overlay)) {
            if(count($video->overlay)) {
                $overlay = $video->overlay;
            }
        }

        if(isset($video->frames)) {
            if(count($video->frames)) {
                $frames = $video->frames;
            }
        }

        foreach($overlay as $overlay_row) {
            if(!isset($overlay_row->type)) {
                $errors = ['overlay_type' => 'Overlay Type is Required!'];
                $this->throwError($errors);
            }

            $overlay_row->type = strtolower($overlay_row->type);
            if(!in_array($overlay_row->type, ['text', 'image'])) {
                $errors = ['overlay_type' => 'Overlay Type Must be image or text!'];
                $this->throwError($errors);
            }

            if($overlay_row->type == "text") {
                if(!isset($overlay_row->x)) {
                    $errors = ['overlay_x' => 'Overlay X is Required!'];
                    $this->throwError($errors);
                }
                $text_x = $overlay_row->x;

                if(!isset($overlay_row->y)) {
                    $errors = ['overlay_y' => 'Overlay Y is Required!'];
                    $this->throwError($errors);
                }
                $text_y = $overlay_row->y;

                $font_size = (int)isset($overlay_row->font_size) ? $overlay_row->font_size : config('video.text.FONT_SIZE');
                $font_color = isset($overlay_row->font_color) ? $overlay_row->font_color : config('video.text.FONT_COLOR');
                $font_style = isset($overlay_row->font_style) ? $overlay_row->font_style : config('video.text.FONT_STYLE');
                $font_family = isset($overlay_row->font_family) ? $overlay_row->font_family : config('video.text.FONT_FAMILY');
            }

            if($overlay_row->type == "image") {
                if(!isset($overlay_row->url)) {
                    $errors = ['image_url' => 'Image URL is Required!'];
                    $this->throwError($errors);
                }
                $url = $overlay_row->url;

                if(!isset($overlay_row->x)) {
                    $errors = ['image_x' => 'Image X is Required!'];
                    $this->throwError($errors);
                }
                $image_x = $overlay_row->x;

                if(!isset($overlay_row->y)) {
                    $errors = ['image_y' => 'Image Y is Required!'];
                    $this->throwError($errors);
                }
                $image_y = $overlay_row->y;

                if(!isset($overlay_row->height)) {
                    $errors = ['image_height' => 'Image Height is Required!'];
                    $this->throwError($errors);
                }
                $image_height = $overlay_row->height;

                if(!isset($overlay_row->width)) {
                    $errors = ['image_width' => 'Image Width is Required!'];
                    $this->throwError($errors);
                }
                $image_width = $overlay_row->width;
                $image_border = "";

                if(isset($overlay_row->border)) {
                    $image_border = $overlay_row->border;
                }
            }
        }

        foreach($frames as $frame) {
            if(!isset($frame->url)) {
                $errors = ['frame_url' => 'Frame URL is Required!'];
                $this->throwError($errors);
            }
            $frame_url = $frame->url;

            if(!(int)isset($frame->duration)) {
                $errors = ['frame_url' => 'Frame Duration is required!'];
                $this->throwError($errors);
            }
            $frame_duration = $frame->duration;

            if(!(int)isset($frame->position)) {
                $errors = ['frame_url' => 'Frame Position is required!'];
                $this->throwError($errors);
            }
            $frame_position = $frame->position;
        }

        if(!$this->does_url_exists($video->video_url)) {
            $errors = ['frame_url' => 'Invalid File URL!'];
            $this->throwError($errors);
        }

        
    }

    public function throwError($errors) {
        echo "<pre>";print_r($errors);exit;
    }

    function does_url_exists($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $status = ($code == 200) ? TRUE : FALSE;
        curl_close($ch);
        return $status;
    }
}
