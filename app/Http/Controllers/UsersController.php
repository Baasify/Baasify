<?php namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Hash;
use Validator;

class UsersController extends MainController
{

    /**
     * Login
     *
     * @param Request $request
     * @return Response
     */

    public function postLogin(Request $request)
    {
        $this->setResultOk();
        if (!$request->has('email') || !$request->has('password')) {
            $this->setResultError("email and password are both required");
        } else {
            $email = $request->get('email');
            $password = $request->get('password');

            $user = User::whereEmail($email)->first();

            if ($user && Hash::check($password, $user->password)) {
                $this->user = $user;
                $this->setSessionHash();
                $this->setUserData();
            } else {
                $this->setResultError("wrong email or password");
            }

        }

        return $this->setResponse();
    }

    /**
     * Logout
     *
     * @param Request $request
     * @return Response
     */

    public function postLogout(Request $request)
    {
        $this->setResultOk();

        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->destroySessionUser($request)) {
            $this->content['data'] = "user logged out";
        } else {
            $this->setResultError("Mismatched session token");
        }

        return $this->setResponse();
    }

    /**
     * Create new account
     *
     * @param Request $request
     * @return Response
     */

    public function postRegister(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users,username|min:4|max:32',
            'email' => 'required|unique:users,email|max:32|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            $this->setResultError($validator->messages());
        } else {
            $this->setResultOk();
            $user = $request->all();
            $user['password'] = Hash::make($user['password']);
            $this->user = User::create($user);
            $this->setSessionHash();
            $this->setUserData();

            unset($user['username'], $user['email'], $user['password']);
            foreach($user as $key=>$value)
                Profile::create(['key'=>$key,'value'=>$value,'user_id'=>$this->user->id]);
        }

        return $this->setResponse();
    }

    /**
     * Logged in user Profile
     *
     * @param Request $request
     * @return Response
     */

    public function getMe(Request $request)
    {
        $this->setResultOk();
        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->setSessionUser($request)) {
            $this->setUserData();
        } else {
            $this->setResultError("Mismatched session token");
        }
        return $this->setResponse();
    }

    /**
     * Get user profile by id
     *
     * @param Int $id
     * @param Request $request
     * @return Response
     */

    public function getUser($id ,Request $request)
    {
        $this->setResultOk();
        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->setSessionUser($request)) {
            $user = User::whereId($id)->first();
            if(!$this->isAdmin()){
                $this->setResultError("Unauthorized action");
            } elseif($user===null){
                $this->setResultError("User not found");
            } else {
                $this->setUserData($user);
            }
        } else {
            $this->setResultError("Mismatched session token");
        }
        return $this->setResponse();
    }

    /**
     * Change Logged in user password
     *
     * @param Request $request
     * @return Response
     */

    public function putPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        $this->setResultOk();
        if($validator->fails()){
            $this->setResultError($validator->messages());
        }elseif ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->setSessionUser($request)) {
            if (Hash::check($request['old_password'], $this->user->password)) {
                $this->user->password = Hash::make($request['new_password']);
                $this->user->save();
            } else {
                $this->setResultError("Mismatched password");
            }
        } else {
            $this->setResultError("Mismatched session token");
        }
        return $this->setResponse();
    }

    /**
     * Update User Data
     *
     * @param Request $request
     * @return Response
     */

    public function putUser(Request $request)
    {
        $this->setResultOk();
        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->setSessionUser($request)) {
            $validator = Validator::make($request->all(), [
                'username' => 'min:4|max:32|unique:users,username,'.$this->user->id,
                'email' => 'max:32|email|unique:users,email,'.$this->user->id,
            ]);

            if($validator->fails()){
                $this->setResultError($validator->messages());
            } else {
                $data = $request->all();
                if(!empty($data['email'])){
                    $this->user->email = $data['email'];
                }
                if(!empty($data['username'])){
                    $this->user->username = $data['username'];
                }
                $this->user->save();
                unset($data['email'],$data['username'],$data['password']);
                foreach($data as $key=>$value){
                    if(empty($value))
                        Profile::whereUserId($this->user->id)->whereKey($key)->first()->delete();
                    else
                        Profile::updateOrCreate(['key'=>$key,'user_id'=>$this->user->id],['value'=>$value]);
                }
            }
        } else {
            $this->setResultError("Mismatched session token");
        }
        return $this->setResponse();
    }

    /**
     * Update User Data
     *
     * @param Int $id
     * @param Request $request
     * @return Response
     */

    public function putUserById($id, Request $request)
    {
        $this->setResultOk();
        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->setSessionUser($request)) {
            $user = User::whereId($id)->first();
            if(!$this->isAdmin()){
                $this->setResultError("Unauthorized action");
            }
            elseif($user===null){
                $this->setResultError("User not found");
            }
            else {
                $validator = Validator::make($request->all(), [
                    'username' => 'min:4|max:32|unique:users,username,' . $user->id,
                    'email' => 'max:32|email|unique:users,email,' . $user->id,
                ]);

                if ($validator->fails()) {
                    $this->setResultError($validator->messages());
                } else {
                    $data = $request->all();
                    if (!empty($data['email'])) {
                        $user->email = $data['email'];
                    }
                    if (!empty($data['username'])) {
                        $user->username = $data['username'];
                    }
                    $user->save();
                    unset($data['email'], $data['username'], $data['password']);
                    foreach ($data as $key => $value) {
                        if (empty($value))
                            Profile::whereUserId($user->id)->whereKey($key)->first()->delete();
                        else
                            Profile::updateOrCreate(['key' => $key, 'user_id' => $user->id], ['value' => $value]);
                    }
                }
            }
        } else {
            $this->setResultError("Mismatched session token");
        }
        return $this->setResponse();
    }

    /**
     * Update User Group
     *
     * @param Int $id
     * @param Int $group
     * @param Request $request
     * @return Response
     */

    public function putGroup($id, $group, Request $request)
    {
        $this->setResultOk();
        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->setSessionUser($request)) {
            $user = User::whereId($id)->first();
            if(!$this->isAdmin()){
                $this->setResultError("Unauthorized action");
            }
            elseif($user===null){
                $this->setResultError("User not found");
            }
            elseif($group<1 || $group>3){
                $this->setResultError("Group not found");
            }
            else {
                $user->group_id = $group;
                $user->save();
            }
        } else {
            $this->setResultError("Mismatched session token");
        }
        return $this->setResponse();
    }

}
