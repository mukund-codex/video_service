<?php

namespace App\Helpers;

use DB;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
use Illuminate\Support\Facades\Log;

class Common{
    
    //
    public static function curl_request(string $callback, array $data){

        if(empty($callback)){
           return false;
        }

        if(sizeof($data) <= 0){
            return false;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $callback,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                // Set here requred headers
                "accept: */*",
                "accept-language: en-US,en;q=0.8",
                "content-type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        }else {
            print_r(json_decode($response));
        }

    }

    public static function processRequest($method ,$headers = [], $url, $requestBodyType ,$data = []){

        $client = new Client();

        $options = [];

        if($headers) {
            $options['headers'] = $headers;
        }

        if(! in_array(\strtoupper($method), ['GET', 'POST'])){
            return false;
        }

        switch ($requestBodyType) {
            case 'body':
                $options['body'] = json_encode($data);
                break;
            case 'form_params':
                $options['form_params'] = $data;
                break;
            
            default:
                break;
        }

        try {
            $response = $client->request($method, $url, $options);
            
            Log::info('Process Request Callback', [
                'callback' => $url,
                'body' => json_decode($response->getBody()),
                'callback_responce_code' => $response->getStatusCode(),
                'callback_response' => $response->getReasonPhrase(),
                'response_time' => date('Y-m-d H:i:s')
            ]);

            return json_decode($response->getBody());

        } catch (ClientException $e) {
            $client_request = Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $client_response = Psr7\str($e->getResponse());
            }

            Log::error('Process Request Callback Exception', [
                'callback' => $url,
                'callback_client_request' => $client_request,
                'callback_response' => ($client_response ?? ''),
                'response_time' => date('Y-m-d H:i:s')
            ]);
        }

    }

}

?>