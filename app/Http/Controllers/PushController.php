<?php namespace App\Http\Controllers;

use App\Libraries\AndroidPushNotifications;
use App\Libraries\iOSPushNotifications;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Hash;
use Validator;

class PushController extends MainController
{

    /**
     * Link new device to a user
     *
     * @param String $platform
     * @param Request $request
     * @return Response
     */

    public function putDevice($platform, Request $request)
    {
        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif(!$request->get('token')||!$request->get('udid')) {
            $this->setResultError("Token and UDID are required");
        } elseif(strtolower($platform)!='android' && strtolower($platform)!='ios') {
            $this->setResultError("Only iOS & Android are currently supported.");
        } elseif ($this->setSessionUser($request)) {

            $device = new Device();
            $device->platform = strtolower($platform);
            $device->token = $request->get('token');
            $device->udid = $request->get('udid');
            $device->environment = getenv(strtoupper($platform).'_PUSH_ENVIRONMENT');
            $device->user_id = $this->user->id;
            $device->save();

            $this->setResultOk();
        } else {
            $this->setResultError("Mismatched session token");
        }
        return $this->setResponse();
    }

    /**
     * Unlink device from a user
     *
     * @param String $udid
     * @param Request $request
     * @return Response
     */

    public function deleteDevice($udid, Request $request)
    {
        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->setSessionUser($request)) {

            Device::whereEnvironment()->whereUdid($udid)->whereUserId($this->user->id)->delete();
            $this->setResultOk();

        } else {
            $this->setResultError("Mismatched session token");
        }
        return $this->setResponse();
    }

    /**
     * Send push notifications
     *
     * @param Request $request
     * @return Response
     */

    public function postPush(Request $request)
    {
        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->setSessionUser($request)) {
            if (!$this->isModerator()) {
                $this->setResultError("Unauthorized action");
            } else {
                $validator = Validator::make($request->all(), [
                    'data' => 'array',
                    'data.message' => 'required',
                    'users' => 'required|array',
                    'users.0' => 'required|numeric',
                ]);

                if($validator->fails()){
                    $this->setResultError($validator->messages());
                }else{
                    $android_devices = Device::whereEnvironment(getenv('ANDROID_PUSH_ENVIRONMENT'))
                        ->wherePlatform('android')->whereIn('user_id', $request->get('users'));
                    $android_tokens = array();
                    foreach($android_devices as $android_device){
                        $android_tokens[] = $android_device->token;
                    }

                    $ios_devices = Device::whereEnvironment(getenv('IOS_PUSH_ENVIRONMENT'))
                        ->wherePlatform('ios')->whereIn('user_id', $request->get('users'));
                    $ios_tokens = array();
                    foreach($ios_devices as $ios_device){
                        $ios_tokens[$ios_device->token] = $ios_device->created_at;
                    }
                    $data = $request->get('data');
                    $message = $data['message'];
                    unset($data['message']);

                    $android = new AndroidPushNotifications();
                    $android->data = $data;
                    $android->message = $message;
                    $android->devices = $android_tokens;
                    $result['android'] = $android->push();


                    $ios = new iOSPushNotifications();
                    $ios->data = $data;
                    $ios->message = $message;
                    $ios->devices = $ios_tokens;
                    $result['ios'] = $ios->push();

                    $this->setResultOk($result);
                }
            }
        } else {
            $this->setResultError("Mismatched session token");
        }
        return $this->setResponse();
    }

}
