<?php

namespace Amuz\XePlugin\ApplicationHelper;

class BaseObject{

    protected $errors = [];
    protected $message = 'success';
    protected $variables = [];

    public function __construct()
    {
    }

    public function setMessage($message){
        $this->message = $message;
    }

    public function addError($error, $message){
        $this->errors[] = $error;
        $this->message = $message;
    }

    public function get($key){
        return $this->variables[$key];
    }


    public function set($key,$val){
        if($val == null || $val == ''){
            unset($this->variables[$key]);
        }else {
            $this->variables[$key] = $val;
        }
    }

    public function output(){
        $data = [
            'errors' => $this->errors,
            'message' => $this->message,
            'variables' => $this->variables,
        ];

        if(count($data['errors']) < 1) unset($data['errors']);
        return response()->json($data);
    }

}
