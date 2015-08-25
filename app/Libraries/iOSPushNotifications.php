<?php
namespace App\Libraries;

use App\Models\Device;
use Wrep\Notificato\Notificato;

class iOSPushNotifications {

    public $devices = array();
    public $data = array();
    public $message;


    private $gateway;

    private $results = array();
    const UNKNOWN = -1;
    const INVALID = 0;
    const VALID = 1;

    function add($status, $token, $update = null){
        if($update)
            $this->results[$status] = array($token=>$update);
        else
            $this->results[$status] = $token;
    }

    function push(){
        $this->gateway = new Notificato(
            getenv('IOS_PUSH_CERTIFICATE'),
            getenv('IOS_PUSH_PASSPHRASE'),
            false,
            getenv('IOS_PUSH_ENVIRONMENT'));

        $this->send();

        $this->clean();

        return [
            'sent'      =>  count($this->results[static::VALID]),
            'invalid'   =>  count($this->results[static::INVALID]),
            'unknown'   =>  count($this->results[static::UNKNOWN])
        ];
    }

    function send(){

        $messageEnvelopes = array();

        $builder = $this->gateway->messageBuilder()
            ->setExpiresAt(new \DateTime('+1 day'))
            ->setAlert($this->message)
            ->setSound('default')
            ->setPayload($this->data);

        foreach ($this->tokens as $token=>$created_at)
        {
            $builder->setBadge(1)->setDeviceToken($token);

            $messageEnvelopes[] = $this->gateway->queue($builder->build());
        }

        $this->gateway->flush();

        foreach ($messageEnvelopes as $messageEnvelope)
        {
            $token = $messageEnvelope->getMessage()->getDeviceToken();
            $status = $messageEnvelope->getStatus();
            if($status===0){
                $this->add(static::VALID, $token);
            }else{
                $this->add(static::UNKNOWN, $token);
            }
        }
    }

    function clean(){
        $notificato = new Notificato(
            getenv('IOS_PUSH_CERTIFICATE'),
            getenv('IOS_PUSH_PASSPHRASE'),
            false,
            getenv('IOS_PUSH_ENVIRONMENT'));

        $tuples = $notificato->receiveFeedback();

        foreach ($tuples as $tuple)
        {
            $InvalidatedAt = $tuple->getInvalidatedAt()->getTimestamp();
            $token = $tuple->getDeviceToken();
            if($InvalidatedAt > strtotime($this->tokens[$token])){
                if (($key = array_search($token, $this->results[static::UNKNOWN])) !== false)
                    unset($this->results[static::UNKNOWN][$key]);
                $this->add(static::INVALID, $token);
            }
        }
        $this->cleanDB();
    }

    function cleanDB(){
        if(!empty($this->results[static::INVALID])){
            Device::wherePlatform('ios')->whereEnvironment(getenv('IOS_PUSH_ENVIRONMENT'))
            ->whereIn('token', $this->results[static::INVALID])->delete();
        }
    }
}
