<?php

namespace App\Jobs;

use App\Models\VideoRequestModel;
use App\Models\VideoResponseModel;
use App\Helpers\{VideoFrameHelper, VideoLength, Common};
use Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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
        $callback = $requestData[0]['callback'];

       /*  $fileName = $this->downloadVideos($videoUrl, $requestId);
        if(empty($fileName)){
            \dd('Error in Video Download');
        } */

        $textOverlay = [];
        $imageOverlay = [];

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

        $processInput = $this->createVideoCommand($requestId, $videoUrl, $textOverlay, $imageOverlay, $frameFirstData, $frameLastData);        
        $process = new Process($processInput);
        $process->setTimeout(300);
         try {
            $process->mustRun();
        
            $result = $process->getOutput();
            //\dd($result);
            $result = true;
            if($result) {
                $video_url = url('Video_Service/uploads/outputVideos/'.$requestId.'.mp4');

                $responseData = new VideoResponseModel();
                $responseData->request_id = $requestId;
                $responseData->response = \json_encode(['video_url' => $video_url]);
        
                if($responseData->save()){
                    $request = Common::processRequest('POST', [], $callback, 'form_params', ['request_id' => $requestId, 'video_url' => $video_url, 'success' => true, 'status_code' => '200']);
        
                    Log::info('Callback Response : '.(int) $request);
                }
            }
            
            Log::info('Process Output Completed', [
                'request_id' => $requestId,
                'status' => (int) $result,
                'output' => $result,
                'response_time' => date('Y-m-d H:i:s')
            ]);

        } catch (ProcessFailedException $exception) {
            Log::error('Process Failed', [
                'request_id' => $requestId,
                'error' => $exception->getMessage(),
                'response_time' => date('Y-m-d H:i:s')
            ]);
        }   
        
    }

    public function downloadVideos($textOverlay, $imageOverlay, $frameFirstData, $frameLastData){

        // Code to download Video to local machine
        $video = \file_get_contents($videoUrl);
        $fileName = '/var/www/html/Video_Service/uploads/videos/'.$requestId.'.mp4';
        $downloadVideo = \file_put_contents($fileName, $video);

        return $fileName;

    }

    public function createVideoCommand($requestId, $videoUrl, $textOverlay, $imageOverlay, $frameFirstData, $frameLastData){

        $frameFirstCommand = [];
        $frameLastCommand = [];
                
        $drawtextCommand = '';
        
        $imageOverlayCommand = '';

        $imageOverlayFilter = '';

        if(!empty($imageOverlay)){
            foreach($imageOverlay as $image){
                $imageOverlayCommand .= " -i ".$image['url'];
                /* if(!empty($image['height']) && !empty($image['width'])){
                    $imageOverlayFilter .= ",scale:".$image['width'].":".$image['height'];
                } */
                if(!empty($image['x']) && !empty($image['y'])){
                    $imageOverlayFilter .= ",overlay=".$image['x'].":".$image['y'];
                }
            }
        }

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

        $filterCommand = $videoSetting.$imageOverlayFilter.$drawtextCommand;

        // ffmpeg -r 24 -loop 1 -t 5 -i https://www.sample-videos.com/img/Sample-png-image-1mb.png -i http://192.168.0.167/input2.mp4 -loop 1 -t 5 -i https://www.sample-videos.com/img/Sample-png-image-500kb.png -i https://bellard.org/bpg/2.png -filter_complex "[0]scale=1920:1080,setsar=1[im];[1]scale=1920:1080,setsar=1[vid];[2]scale=1920:1080,setsar=1[im];[im][vid][im]concat=n=3:v=1:a=0,overlay=25:25,drawtext=fontfile=/home/rakesh/Downloads/OpenSans-Bold.ttf:text='Test Text': x=100: y=100: fontsize=30:fontcolor=Blue, drawtext=fontfile=/home/rakesh/Downloads/OpenSans-Bold.ttf:text='Focal Length':x=(w-text_w)/3: y=(h-text_h)/3:fontsize=18:fontcolor=Blue" -strict -2 output.mp4

        $command = "ffmpeg -r 24 ".$frameLastCommand['time']." ".$frameLastCommand['url']." -i $videoUrl ".$frameFirstCommand['time']." ".$frameFirstCommand['url']." $imageOverlayCommand -filter_complex \"$filterCommand\" -strict -2 ".$outputFile;
        
        return $command;
    }
}
