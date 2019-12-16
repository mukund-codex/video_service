<?php

namespace App\Jobs;

use App\Models\VideoRequestModel;
use App\Models\VideoResponseModel;
use App\Helpers\{VideoFrameHelper, VideoLength, Common};
use Log;

class VideoJob extends Job
{   

    public $requestId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($requestId)
    {
        //
        $this->requestId = $requestId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        Log::info('Job - Request ID : '.$this->requestId);
        $requestRecord = VideoRequestModel::find(['id' => $this->requestId])->first();
        $requestData = json_decode($requestRecord->request, TRUE);
        $requestId = $requestRecord->request_id;
        $videoUrl = $requestData[0]['video_url'];

        $fileName = $this->downloadVideos($videoUrl, $requestId);
        if(empty($fileName)){
            \dd('Error in Video Download');
        }

        $textOverlay = [];
        $imageOverlay = [];

        //drawtext=fontfile=/path/to/font.ttf:text='Test Text': x=100: y=100: fontsize=30:fontcolor=Blue, drawtext=fontfile=/path/to/font.ttff:text='Focal Length':x=(w-text_w)/3: y=(h-text_h)/3:fontsize=18:fontcolor=Blue"

        if(!empty($requestData[0]['overlay'])){
            
            foreach ($requestData[0]['overlay'] as $overlay) {
                $textData = [];
                $imageData = [];
                if($overlay['type'] == 'text'){
                    $textData['text'] = $overlay['text'];
                    $textData['x'] = $overlay['x'];
                    $textData['y'] = $overlay['y'];
                    $textData['font'] = $overlay['font'];
                    $textData['font_size'] = $overlay['font_size'];
                    $textData['font_style'] = isset($overlay['font_style']) ? $overlay['font_style'] : '';
                    $textData['font_color'] = $overlay['font_color'];
                    if(!empty($overlay['css'])){
                        $textData['css'] = $overlay['css'];
                    }
                    
                    array_push($textOverlay, $textData);

                }

                if($overlay['type'] == 'image'){
                    $imageData['url'] = $overlay['url'];
                    $imageData['x'] = $overlay['x'];
                    $imageData['y'] = $overlay['y'];
                    $imageData['height'] = $overlay['height'];
                    $imageData['width'] = $overlay['width'];
                    $imageData['border'] = $overlay['border'];
                    if(!empty($overlay['css'])){
                        $imageData['css'] = $overlay['css'];
                    }

                    array_push($imageOverlay, $imageData);

                }
            }

        }

        $frameFirstData = [];
        $frameLastData = [];

        if(!empty($requestData['0']['frames'])){
            
            foreach($requestData[0]['frames'] as $frames){
                $framesFirst = [];
                $framesLast = [];
                if($frames['position'] == 'first'){
                    $framesFirst['position'] = $frames['position'];
                    $framesFirst['url'] = $frames['url'];
                    $framesFirst['duration'] = $frames['duration'];
                    array_push($frameFirstData, $framesFirst);
                }

                if($frames['position'] == 'last'){
                    $framesLast['position'] = $frames['position'];
                    $framesLast['url'] = $frames['url'];
                    $framesLast['duration'] = $frames['duration'];
                    array_push($frameLastData, $framesLast);
                }

            }

        }

        $frameFirstCommand = [];
        $frameLastCommand = [];
                
        $drawtextCommand = '';
        
        if(!empty($textOverlay)){
            foreach($textOverlay as $text){
                $drawtextCommand .= ",drawtext=";
                if($text['font']){
                    $drawtextCommand .= "fontfile=".$text['font'];
                }
                if($text['text']){
                    $drawtextCommand .= ":text='".$text['text']."'";
                }
                if($text['x']){
                    $drawtextCommand .= ":x=".$text['x'];
                }
                if($text['y']){
                    $drawtextCommand .= ":y=".$text['y'];
                }
                if($text['font_size']){
                    $drawtextCommand .= ":fontsize=".$text['font_size'];
                }
                if($text['font_color']){
                    $drawtextCommand .= ":fontcolor=".$text['font_color'];
                }
                /* if($text['font_style']){
                    $drawtextCommand .= ":style=".$text['font_style'];
                } */
            }
        }

        if(!empty($frameFirstData)){
            $frameFirstCommand['time'] = "-loop 1 -t ".$frameFirstData[0]['duration'];
            $frameFirstCommand['url'] = "-i ".$frameFirstData[0]['url'];
            $frameFirstCommand['filters'] = "[0]scale=1920:1080,setsar=1[im];";
        }

        if(!empty($frameLastData)){
            $frameLastCommand['time'] = "-loop 1 -t ".$frameLastData[0]['duration'];
            $frameLastCommand['url'] = "-i ".$frameLastData[0]['url'];
            if(empty($frameFirstCommand['filters'])){
                $frameLastCommand['filters'] = "[1]scale=1920:1080,setsar=1[im];";                
            }else{
                $frameLastCommand['filters'] = "[2]scale=1920:1080,setsar=1[im];";                
            }
        }

        if(empty($frameFirstCommand['filters'])){
            $setting = "[vid][im]concat=n=2:v=1:a=0";
            $videoSetting = $frameFirstCommand['filters']."[1]scale=1920:1080,setsar=1[vid];$setting";
        }else if(empty($frameFirstCommand['filters'])){
            $setting = "[im][vid]concat=n=2:v=1:a=0";
            $videoSetting = "[0]scale=1920:1080,setsar=1[vid];".$frameLastCommand['filters'].$setting;
        }else{
            $setting = "[im][vid][im]concat=n=3:v=1:a=0";
            $videoSetting = $frameFirstCommand['filters']."[1]scale=1920:1080,setsar=1[vid];".$frameLastCommand['filters'].$setting;
        }

        $outputFile = "/var/www/html/video-service/uploads/outputVideos/".$requestId.".mp4";

        $command = "ffmpeg -r 24 ".$frameFirstCommand['time']." ".$frameFirstCommand['url']." -i $videoUrl ".$frameLastCommand['time']." ".$frameLastCommand['url']." -filter_complex '$videoSetting'$drawtextCommand -strict -2 ".$outputFile;

        $command_output = shell_exec($command);

        //http://localhost/video-service/uploads/outputVideos/JX5422014163.mp4

        $video_url = url('video-service/uploads/outputVideos/'.$requestId.'.mp4');

        $response_request = (json_encode($requestData));
        
        $responseData = new VideoResponseModel();
        $responseData->request_id = $requestId;
        $responseData->response = \json_encode(['video_url' => $video_url]);

        $responseData->save();

        $request = Common::processRequest('POST', [], $callback, 'form_params', ['request_id' => $requestId, 'video_url' => $video_url, 'success' => true, 'status_code' => '200']);

        Log::info('Callback Response : '.(int) $request);
        
    }

    public function downloadVideos($videoUrl, $requestId){

        // Code to download Video to local machine
        $video = \file_get_contents($videoUrl);
        $fileName = '/var/www/html/video-service/uploads/videos/'.$requestId.'.mp4';
        $downloadVideo = \file_put_contents($fileName, $video);

        return $fileName;

    }
}
