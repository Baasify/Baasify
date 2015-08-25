<?php
namespace App\Libraries;

use App\Models\Device;

class AndroidPushNotifications {

    public $devices = array();
    public $data = array();
    public $message;


    private $results = array();
    const UNKNOWN = -1;
    const INVALID = 0;
    const VALID = 1;
    const UPDATED = 2;

    function add($status, $token, $update = null){
        if($update)
            $this->results[$status] = array($token=>$update);
        else
            $this->results[$status] = $token;
    }

    private function clean(){
        foreach($this->results[static::INVALID] as $token){
            Device::wherePlatform('android')->whereToken($token)->delete();
        }

        foreach($this->results[static::UPDATED] as $token=>$new_token){
            $device = Device::wherePlatform('android')->whereToken($token)->first();
            $device->token = $new_token;
            $device->save();
        }
    }

    function push(){
        $chunks = array_chunk($this->devices, 1000);

        foreach($chunks as $chunk) {
            $this->process($chunk);
        }

        $this->clean();

        return [
            'sent'=>count($this->results[static::VALID])+count($this->results[static::UPDATED]),
            'invalid'=>count($this->results[static::INVALID]),
            'unknown'=>count($this->results[static::UNKNOWN])
        ];

    }

    private function process($devices){
        $fields = array
        (
            'registration_ids' => $devices,
            'data' => $this->data
        );

        $result = $this->connect($fields);

        foreach($result as $key=>$row){
            $token = $devices[$key];
            if(isset($row->error)){
                if($row->error=='InvalidRegistration'||$row->error=='NotRegistered'){
                    $this->add(static::INVALID, $token);
                }elseif($row->error=='Unavailable'){
                    $this->add(static::UNKNOWN, $token);
                }
            }
            elseif(isset($row->registration_id)) {
                $this->add(static::UPDATED, $token, $row->registration_id);
            }
            else{
                $this->add(static::VALID, $token);
            }
        }
    }

    private function connect($fields){
        $headers = array
        (
            'Authorization: key=' . getenv('ANDROID_API_ACCESS_KEY'),
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://gcm-http.googleapis.com/gcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result)->results;
    }
}
