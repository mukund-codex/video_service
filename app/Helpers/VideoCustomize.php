<?php

namespace App\Helpers\VideoCustomize;

class VideoCustomize {
    
    function __construct() {
        parent::__construct();
        $this->load->model('mdl_video_manipulate');
        $this->load->helper('upload_media');
        $this->config->load('s3');
        $this->load->library('s3');
        $this->load->library('generate');        
        $this->is_enabled_s3 = $this->config->item('enable_s3');
        $this->s3_base_url = $this->config->item('s3_base_url');
        $this->s3_bucket_name = $this->config->item('s3_bucket_name');
    }

    function index() {
        /** 
        * Check Cron In Progress
        */
        $cron_in_progress = $this->mdl_video_manipulate->get_records([],'video_cron_status',['status']);
        if($cron_in_progress[0]->status){
            exit;
        }
        /**
        * Update Cron Status to Active
        */
        $this->mdl_video_manipulate->_update(['c_status_id' => 1],['status' => 0],'video_cron_status');        
      
           $videos = $this->mdl_video_manipulate->get_videos();
            
            foreach($videos as $key => $video) {
           
            $doctor_id                      = $video->dr_id;
            $doctor_name                    = $video->doctor_name;
            $users_id                       = $video->mr_id;
            $doctor_video_id                = $video->dr_vid_id;
            $doctor_photo                   = $video->s3_img_url; 
            $doctor_mobile                  = $video->mobile_number; 
            //  local server upload language_videos folder for testing
            $video_url                      = 'uploads/Videos_templates/WorldHeart_Day_Final.mp4';
            $frame_url                      = 'uploads/Videos_templates/frame.png';
            
            $speciality                     = $this->mdl_video_manipulate->get_records(['speciality_id'=>$video->speciality],'speciality',['speciality_id','speciality_name']);

            $doctor_id_wid_spl             = $video->dr_id.'_'. strtolower(str_replace(' ', '_' , $speciality[0]->speciality_name));
            $speciality                    = $speciality[0]->speciality_name;

// echo $video_url;
// die();
            //get s3 language_videos bucket 
            // $video_url = `https://reddy-drl.s3.ap-south-1.amazonaws.com/language_videos/`.$video->video_file;
            //print_r($video_url);exit; 
          
        //    $doctor_id = $video->doctor_id;
          
           //local server upload doctor_images folder for testing
          // $doctor_photo = base_url().$video->doctor_photo;
           //get s3 language_videos bucket 
           
            
           $file_name = "doctor_resized_$doctor_id_wid_spl.jpg";
           $photo_frame = "doctor_photo_frame.jpg";
           $test_image_path = "uploads/$doctor_id_wid_spl/$photo_frame";
           $path_to_resize_img = "uploads/$doctor_id_wid_spl/$file_name";
           $template_path = "uploads/$doctor_id_wid_spl"; 
            //resize doctor photo 
           // print_r( $doctor_photo);exit;
            if(!file_exists($doctor_photo)){
                $log_file= APPPATH . 'logs/apilog' . date('Y-m-d') . ".log";
                $message = date('Y-m-d h:i:s') ." Message :: " . "Unable to save data file doest exist $doctor_photo" . PHP_EOL;
             
                error_log($message, 3, $log_file);
                continue;
            }
    
            $check_return=  $this->generate->resize($doctor_photo,$path_to_resize_img);

            $stamp = imagecreatefromjpeg($path_to_resize_img);  
          
            $image_width = 1920; //1280
            $image_height = 1080; // 720
            $im = imagecreatetruecolor($image_width, $image_height);
           
            // set background to white
            $white = imagecolorallocate($im, 255, 255, 255);
            imagefill($im, 0, 0, $white);
           
            imagejpeg($im, $test_image_path, 100);

            // Set the margins for the stamp and get the height/width of the stamp image
           
            $marge_right = 0;
            $marge_bottom = 0;
            $sx = imagesx($stamp);
            $sy = imagesy($stamp);
                                         //256 -img size x256 img size +300 dwon +240 up
                                         
            echo shell_exec("composite -geometry 646x636+640+175 \"$path_to_resize_img\" \"$test_image_path\" \"$test_image_path\"");
            // shell_exec("composite -geometry ImageHeightxImageWidth+ImageYcordinates+ImageXcordinates \"$pathTheResizeImg\" \"$templateImagePath\" \"$templateImagePath\""); 
            // This script runs to fix the resized image into the template.
            // Template can customized as we want $image_width = 1024; //1280 $image_height = 768; // 720 this two variables defined above helps us to set the height and with of the template.
            // Than you can set the color to the template.
            /*-----------New Code for text box----------------------*/
            $text = "Dr. " . $doctor_name;
            $docNameLen = strlen($text);
            
            /* if(in_array($docNameLen,['5','6','7','8'])){
               $xaxis = 855;
            }else if(in_array($docNameLen,['9','10','11','12','13'])){
                $xaxis = 820;
            }else if(in_array($docNameLen,['14','15','16'])){
               $xaxis = 800;
            }
            else if(in_array($docNameLen,['17','18','19','20'])){
               $xaxis = 750;
            }
            else if(in_array($docNameLen,['21','22','23','24','25'])){
                $xaxis = 700;
            } */


            // else if(in_array($docNameLen,['26','27','28'])){
            //     $xaxis = 620;
            // }
            // echo $docNameLen;
            // exit;

            
            if(!is_dir($template_path)) {
                mkdir($template_path, 0775);
            }

            $textFrame = "$template_path/doctor_text_frame.png";
            
            /* echo shell_exec("convert -font URW-Gothic-Demi-Oblique -fill blue -pointsize 60 -draw 'text $xaxis,875 \"$text\"' $frame_url \"$textFrame\"");
            echo shell_exec("convert -font URW-Gothic-Demi-Oblique -fill blue -pointsize 45 -draw 'text 830,960 \"$speciality\"' $textFrame \"$textFrame\"");
            echo shell_exec("convert -font URW-Gothic-Demi-Oblique -fill blue -pointsize 45 -draw 'text 835,1015 \"$doctor_mobile\"' $textFrame \"$textFrame\""); */
            
            
            
            // echo "sdfgsdgdsg";exit;
            
            
            // shell_exec("convert -font verdana -fill colorOfText -pointsize sizeOfText -draw 'text textYcordinates,textXcordinates \"$textThatHasTOBePlaced\"' $pathForFrameUrl \"$pathForSavingTheImage\""); 
            // This script runs and add text to the frame as we desire and create a new frame with text for us.
            // echo shell_exec("convert -font verdana -fill black -pointsize 150 -draw 'text 616,3000 \"$doctor_name\"'  -draw 'text 1544,3250 \"$clinicAddr\"' -draw 'text 1180,3500 \"$date\"' -draw 'text 672,3748 \"$day\"' -draw 'text 724,3980 \"$time\"' $imgSrc \"$textFrame\"");
            // You can add multiple text to the frame in single command.
            // echo "fsdgdgdf"; exit;     
            $bgFrameImage3 = imagecreatefrompng($frame_url);
            imagealphablending($bgFrameImage3, false);
            imagesavealpha($bgFrameImage3, true);
            $box = new Box($bgFrameImage3);
            // $box->enableDebug();
            $box->setFontFace('assets/fonts/verdana.ttf'); 
            $box->setFontColor(new Color(0, 0, 100));
            $box->setFontSize(60);
            $box->setBox(0, 850, 1920, 70);
            $box->setTextAlign('center', 'top');
            $box->draw($text);

            $box = new Box($bgFrameImage3);
            // $box->enableDebug();
            $box->setFontFace('assets/fonts/verdana.ttf'); 
            $box->setFontColor(new Color(0, 0, 100));
            $box->setFontSize(45);
            $box->setBox(0, 920, 1920, 50);
            $box->setTextAlign('center', 'top');
            $box->draw($speciality);

            $box = new Box($bgFrameImage3);
            // $box->enableDebug();
            $box->setFontFace('assets/fonts/verdana.ttf'); 
            $box->setFontColor(new Color(0, 0, 100));
            $box->setFontSize(45);
            $box->setBox(0, 990, 1920, 50);
            $box->setTextAlign('center', 'top');
            $box->draw($doctor_mobile);
// echo "dfgdfg";
// die();
            // header("Content-type: image/png");
            imagepng($bgFrameImage3, $textFrame); 
            // die();
           
            /*-----------New Code for text box ends here----------------------*/ 

            // Now we have to create an 

           // header('Content-Type: image/png');
            //imagepng($bgFrameImage3);exit;

            // Set the margins for the stamp and get the height/width of the stamp image
            $marge_right2 = 0;
            $marge_bottom2 = 0;
            $sx2 = imagesx($bgFrameImage3);
            $sy2 = imagesy($bgFrameImage3);
          
            $new_doc_img = imagecreatefromjpeg($test_image_path);
            
            // header('Content-Type: image/jpeg');
            // imagejpeg($new_doc_img);exit;

            // Copy the stamp image onto our photo using the margin offsets and the photo
            // width to calculate positioning of the stamp.
            imagecopy($new_doc_img, $bgFrameImage3, imagesx($new_doc_img) - $sx2 - $marge_right2, imagesy($new_doc_img) - $sy2 - $marge_bottom2, 0, 0, imagesx($bgFrameImage3), imagesy($bgFrameImage3));
                // echo "fdgvdfg";exit;
            //  header('Content-Type: image/jpeg');
            // imagejpeg($new_doc_img);exit;

            $templateName = "doctor"."_".$doctor_id_wid_spl.".jpg";
            $template_image_path = "uploads/$doctor_id_wid_spl/$templateName";
            
            // header("Content-type: image/jpg");
            // $new_doc_img = imagejpeg($new_doc_img); 

            imagejpeg($new_doc_img, $template_image_path, 100); 
            // die();
            // echo "fsdfsdf";exit;

            $dataDoctorTemplate = array(                    
                    'img_url'   => $template_image_path,
                ); 
            // $this->mdl_video_manipulate->_update(['dr_vid_id'=>$doctor_video_id],$dataDoctorTemplate,'dr_video');            
            
            //local server upload language_videos folder for testing
            // $video_urls =  "uploads/language_videos/".$video->video_file;
            //get s3 language_videos bucket 
            //$video_urls = `https://reddy-drl.s3.ap-south-1.amazonaws.com/language_videos/`.$video->video_file;s
            $frame_url = $template_image_path;
            // echo $frame_url;
            // die();
            $doc_vid_url_name       = strtolower(str_replace(' ', '_', $doctor_name));
            $speciality_s3          = strtolower(str_replace(' ', '_', $speciality));
            $s3_video_file_url      = "torrent/videos/$doctor_id_wid_spl/".$speciality_s3."/$doc_vid_url_name-".$speciality_s3.".mp4";
            $s3_image_file_url      ="torrent/videos/$doctor_id_wid_spl/".$speciality_s3."/doctor_raw.jpg";
            $output_video_file      = $template_path."/video.mp4";
            

            //$command = "ffmpeg -y -i  " . $video_urls . "  -loop 1 -framerate 25 -t 5 -i " . $frame_url . " -t 5 -f lavfi -i aevalsrc=0 -filter_complex \"[1:0]scale=1920:1080[curtain];[0:0][0:1][curtain] [2:0] concat=n=2:v=1:a=1\" " . $output_file;
            // chnage in uper command
            //$command = "ffmpeg -y -i  " . $video_urls . "  -loop 1 -framerate 25 -t 5 -i " . $frame_url . " -t 5 -filter_complex \"[0]scale=432:432,setsar=1[im];[1]scale=1920:1080,setsar=1[vid];[im][vid]concat=n=2:v=1:a=0\" " . $output_file;
            
            // Working first frame and the video manipulation
           // $ref_command = "ffmpeg -loop 1 -framerate 24 -t 5 -i $frame_url -i $video_url -filter_complex \"[0]scale=432:432,setsar=1[im];[1]scale=432:432,setsar=1[vid];[im][vid]concat=n=2:v=1:a=0\" uploads/video/out_ref.mp4";
           
            // new final
            $new_command = "ffmpeg -r 24 -loop 1 -t 10 -i $frame_url -i $video_url -filter_complex \"[0]scale=1920:1080,setsar=1[vid];[1]scale=1920:1080,setsar=1[im];[im][vid]concat=n=2:v=1:a=0\" -strict -2 ". $output_video_file;
            // $new_command = "ffmpeg -r 24 -loop 1 -t 3 -i $frame_url -i $video_url -c:v libx264 -crf 23 -profile:v baseline -level 3.0 -pix_fmt yuv420p \ -c:a aac -ac 2 -b:a 128k \ -movflags faststart \ " .$output_video_file;
            
            // $new_command = "ffmpeg -loop 1 -framerate 24 -t 5 -i $frame_url -i $video_url -pix_fmt yuv420p -vcodec libx264 -filter_complex \"[0]scale=1920:1080,setsar=1[im];[1]scale=1920:1080,setsar=1[vid];[im][vid]concat=n=2:v=1:a=0\" -strict -2 " . $output_video_file;
            
            // echo $new_command;exit;

            $command_output = shell_exec($new_command);
            // $output_video_audio_file                = $template_path."/video_and_audio.mp4";
            $output_converted_video_audio_file      = $template_path."/video_and_audio_converted.mp4";

            // $new_audio_command = "ffmpeg -i $output_video_file -i $audio_url -c copy -map 0:v -map 1:a $output_video_audio_file";

            // echo $new_audio_command;exit;
            // $command_output = shell_exec($new_audio_command);
            
            $new_convert_command = "ffmpeg -i $output_video_file -c:v libx264 -crf 23 -profile:v baseline -level 3.0 -pix_fmt yuv420p -c:a aac -ac 2 -b:a 128k -movflags faststart -strict -2 $output_converted_video_audio_file";
            // echo $new_convert_command;exit;

            $command_output = shell_exec($new_convert_command);

            // echo $ref_command; 
           // echo "=================<br/>\n=============\n";
          // echo $new_command;exit;
         // upload s3  
            
// var_dump($output_video_audio_file);exit;
         $upload_img_s3 = S3::putObjectFile(
            $doctor_photo,
            $this->s3_bucket_name,
            $s3_image_file_url,
            S3::ACL_PUBLIC_READ
        );
        $upload_video_s3 = S3::putObjectFile(
            $output_converted_video_audio_file,
            $this->s3_bucket_name,
            $s3_video_file_url,
            S3::ACL_PUBLIC_READ,
            array(),
            'video/mp4'
        );
        $dataDoctorImageVideo = array(                  
                    's3_img_url'        => $s3_image_file_url,
                    's3_vid_url'        => $s3_video_file_url,
                    'status'            => 'done'
                ); 
                
        if ($upload_img_s3 && $upload_video_s3) {

            if (is_dir($template_path)) {
                $objects = scandir($template_path);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($template_path . "/" . $object))
                            rrmdir($template_path . "/" . $object);
                        else
                            unlink($template_path . "/" . $object);
                    }
                }
                rmdir($template_path);
            }

            $last_vid_tem_id = $this->mdl_video_manipulate->_update(['dr_vid_id'=>$doctor_video_id],$dataDoctorImageVideo,'doctor_vid');

            if($last_vid_tem_id) {
                $get_user_registration_ids = $this->mdl_video_manipulate->get_unique_device_id($users_id);            

                if(empty($get_user_registration_ids)) {
                    continue;
                }

                $title = 'Video is Ready';
                $body = "Video for Dr.$doctor_name is ready. You can share with the doctors";
                $video_url = $this->s3_base_url.$s3_video_file_url;

                $request_notification_data = [];

                $request_notification_data['title'] = $title;
                $request_notification_data['type'] = 'video_complete';
                $request_notification_data['desc'] = $body;
                $request_notification_data['insert_id'] = $doctor_id;
                $request_notification_data['video_file'] = $video_url;
                $request_notification_data['dr_vid_id'] = $doctor_video_id;
                $get_last_request_device_id = $this->mdl_video_manipulate->_insert($request_notification_data, 'notification_request');

                if(! $get_last_request_device_id) {
                    continue;
                }

                $notification_request = [];

                foreach ($get_user_registration_ids as $key => $user_device) {

                    if(! in_array($user_device->device_type, ['android', 'ios'])) {
                        continue;
                    }

                    $user_registration_devices = [];
                    
                    $user_registration_devices['request_id'] = $get_last_request_device_id;
                    $user_registration_devices['user_id'] = $users_id;
                    $user_registration_devices['device_id'] = $user_device->device_id;
                    $user_registration_devices['device_type'] = $user_device->device_type;
                    $user_registration_devices['insert_dt'] = $user_registration_devices['update_dt'] = date('Y-m-d H:i:s');

                    array_push($notification_request, $user_registration_devices);            
                }

                if(count($notification_request)) {
                    $get_last_notification_request_devices = $this->mdl_video_manipulate->_insert_batch($notification_request, 'notification_request_devices');
                }                     
            }
        }    
    }


    $this->mdl_video_manipulate->_update(['c_status_id' => 1],['status' => 0],'video_cron_status');
    echo 'Success';

    }
}

?>