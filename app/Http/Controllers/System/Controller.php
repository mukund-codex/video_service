<?php
namespace App\Http\Controllers\System;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function __construct() {}

    protected function validateRequest(Request $request, array $rules, array $messages = [])
    {
        // Perform Validation
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->messages();
            
            $errorMessages = array_merge($errorMessages, $messages);
            // create error message by using key and value
            foreach ($errorMessages as $key => $value) {
                $errorMessages[$key] = $value[0];
            }
            return $errorMessages;
        }
        return true;
    }
}
