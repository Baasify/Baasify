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
	    if(!$this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    $validator = Validator::make($request->all(), [
		    'email' => 'required|max:32|email',
		    'password' => 'required|min:6',
	    ]);

	    if ($validator->fails()) {
		    $this->setResultError($validator->messages());
	    } else {
            $email = $request->get('email');
            $password = $request->get('password');
            $user = User::whereEmail($email)->first();

            if ($user && Hash::check($password, $user->password)) {
	            $this->setResultOk();
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
	    if(!$this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->destroySessionUser($request)) {
	        $this->setResultOk("user logged out");
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
	    if(!$this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users,username|min:4|max:32',
            'email' => 'required|unique:users,email|max:32|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            $this->setResultError($validator->messages());
        } else {
            $user = $request->all();
            $user['password'] = Hash::make($user['password']);
            $this->user = User::create($user);

	        unset($user['username'], $user['email'], $user['password']);
	        foreach($user as $key=>$value)
		        Profile::create(['key'=>$key,'value'=>$value,'user_id'=>$this->user->id]);

	        $this->setResultOk();
            $this->setSessionHash();
            $this->setUserData();
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
	    if(!$this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->setSessionUser($request)) {
	        $this->setResultOk();
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
	    if(!$this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

        if ($this->isSessionEmpty($request)) {
            $this->setResultError("Session token is missing");
        } elseif ($this->setSessionUser($request)) {
            $user = User::whereId($id)->first();
            if(!$this->isAdmin()){
                $this->setResultError("Unauthorized action");
            } elseif($user===null){
                $this->setResultError("User not found", 404);
            } else {
	            $this->setResultOk();
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
	    if(!$this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    if ($this->isSessionEmpty($request)) {
		    $this->setResultError("Session token is missing");
	    } elseif ($this->setSessionUser($request)) {

		    $validator = Validator::make($request->all(), [
			    'old_password' => 'required|min:6',
			    'new_password' => 'required|min:6',
		    ]);

		    if($validator->fails()){
			    $this->setResultError($validator->messages());
		    } elseif (Hash::check($request['old_password'], $this->user->password)) {
			    $this->setResultOk();
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
	    if(!$this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

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
            } elseif(empty($request->all())){
	            $this->setResultError("Empty request");
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
	    if(!$this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

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

	            if($validator->fails()){
		            $this->setResultError($validator->messages());
	            } elseif(empty($request->all())){
		            $this->setResultError("Empty request");
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
	    if(!$this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

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
