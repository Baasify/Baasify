<?php

class UsersTest extends TestCase
{

	public function setUp(){
		parent::setUp();

		$this->artisan('migrate');
		$this->seed();

		$this->beforeApplicationDestroyed(function () {
			$this->artisan('migrate:rollback');
		});
	}

	public function testLogin()
	{

		$this->post('/login', ['email' => 'baasify', 'password'=>'baasify'])
			->seeJson(array(
				"result"=>"error",
				'error' => 'No app key provided',
			));

		$this->post('/login',['email' => 'baasify', 'password'=>'baasify'], ['X-APP-KEY'=>'WrongAppKey'])
			->seeJson(array(
				"result"=>"error",
				"error" => "Mismatched app key",
			));

		$this->post('/login',['email' => 'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"email"=>["The email must be a valid email address."],
				"password"=>["The password field is required."]
			));

		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'123'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"password"=>["The password must be at least 6 characters."]
			));

		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'123456'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error" => "wrong email or password",
			));

		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($admin_hash));
	}

	public function testRegister()
	{
		$this->post('/user',[], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"email"=>["The email field is required."],
				"username"=>["The username field is required."],
				"password"=>["The password field is required."],
			));

		$this->post('/user',['email' => 'user'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"email"=>["The email must be a valid email address."],
				"username"=>["The username field is required."],
				"password"=>["The password field is required."],
			));

		$this->post('/user',['email' => 'user@baasify.org', 'username'=>'baasify', 'password'=>'123'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"email"=>["The email has already been taken."],
				"username"=>["The username has already been taken."],
				"password"=>["The password must be at least 6 characters."],
			));

		$this->post('/user',['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($hash));
	}

	public function testRetrieve(){

		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		$this->post('/user',['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		$this->get('/me', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$this->get('/me', ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error" => "Session token is missing",
			));

		$this->get('/me', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>'WRONG SESSION TOKEN'])
			->seeJson(array(
				"result"=>"error",
				"error" => "Mismatched session token",
			));

		$this->post('/user',['email' => 'test2@baasify.org', 'username'=>'test2', 'password'=>'123456', 'custom_data'=>'data'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test2@baasify.org",
				"username" => "test2",
				"profile" => ["custom_data" => "data"],
			));

		$this->get('/user/3', ['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action"
			));

		$this->get('/user/4', ['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action"
			));

		$this->get('/user/3', ['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"email"=>"test2@baasify.org",
				"profile" => ["custom_data" => "data"],
			));

		$this->get('/user/4', ['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"User not found",
			));
	}

	public function testLogout()
	{
		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($hash));

		$this->post('/logout',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->post('/logout',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Mismatched session token",
			));

		$this->post('/logout',[],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Session token is missing"
			));
	}

	public function testChangePassword()
	{
		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($hash));

		$this->put('/password',[],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Session token is missing"
			));

		$this->put('/password',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>"WrongSessionID"])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Mismatched session token",
			));

		$this->put('/password',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$hash])
			->seeJson(array(
				"result"=>"error",
				"old_password"=>["The old password field is required."],
				"new_password"=>["The new password field is required."],
			));

		$this->put('/password',["old_password"=>'123',"new_password"=>"123"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$hash])
			->seeJson(array(
				"result"=>"error",
				"old_password"=>["The old password must be at least 6 characters."],
				"new_password"=>["The new password must be at least 6 characters."],
			));

		$this->put('/password',["old_password"=>'baasify',"new_password"=>"123456"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$hash])
			->seeJson(array(
				"result"=>"ok",
			));

	}

	public function testUpdateUser()
	{
		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($hash));

		$this->post('/user',['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$this->put('/user',[],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Session token is missing"
			));

		$this->put('/user',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>"WrongSessionID"])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Mismatched session token",
			));

		$this->put('/user',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Empty request",
			));

		$this->put('/user',["username"=>"test","email"=>"test@baasify.org"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$hash])
			->seeJson(array(
				"result"=>"error",
				"username"=>["The username has already been taken."],
				"email"=>["The email has already been taken."],
			));

		$this->put('/user',["username"=>'admin',"extra_data"=>"123"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->seeInDatabase("profiles",["user_id"=>1,"key"=>"extra_data","value"=>"123"]);

		$this->put('/user',["extra_data"=>"1234"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->notSeeInDatabase("profiles",["user_id"=>1,"key"=>"extra_data","value"=>"123"]);
		$this->seeInDatabase("profiles",["user_id"=>1,"key"=>"extra_data","value"=>"1234"]);
	}
	public function testUpdateUserById()
	{
		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($admin_hash));

		$this->post('/user',['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($user_hash));

		$this->post('/user',['email' => 'test2@baasify.org', 'username'=>'test2', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test2@baasify.org",
				"username" => "test2",
			));

		$user2_hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($user2_hash));

		$this->put('/user/3',[],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Session token is missing"
			));

		$this->put('/user/3',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>"WrongSessionID"])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Mismatched session token",
			));

		$this->put('/user/3',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Empty request",
			));

		$this->put('/user/3',["username"=>"test","email"=>"test@baasify.org"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"error",
				"email"=>["The email has already been taken."],
				"username"=>["The username has already been taken."],
			));

		$this->put('/user/3',["username"=>'test3',"extra_data"=>"123"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->seeInDatabase("profiles",["user_id"=>3,"key"=>"extra_data","value"=>"123"]);

		$this->put('/user/3',["extra_data"=>"1234"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->notSeeInDatabase("profiles",["user_id"=>3,"key"=>"extra_data","value"=>"123"]);
		$this->seeInDatabase("profiles",["user_id"=>3,"key"=>"extra_data","value"=>"1234"]);

		$this->put('/user/3',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->put('/user/3',["username"=>"test","email"=>"test@baasify.org"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->put('/user/3',["username"=>'test3',"extra_data"=>"123"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->put('/user/3',["extra_data"=>"1234"],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));
	}

	public function testUpdateUserGroup()
	{
		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($admin_hash));

		$this->post('/user',['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($user_hash));

		$this->post('/user',['email' => 'test2@baasify.org', 'username'=>'test2', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test2@baasify.org",
				"username" => "test2",
			));

		$user2_hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($user2_hash));

		$this->put('/group/3/1',[],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Session token is missing"
			));

		$this->put('/group/3/1',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>"WrongSessionID"])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Mismatched session token",
			));

		$this->put('/group/3/1',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->put('/group/2/1',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->put('/group/3/2',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->seeInDatabase('users',['id'=>2, 'group_id'=>1]);
		$this->seeInDatabase('users',['id'=>3, 'group_id'=>2]);

		$this->put('/group/3/1',[],
			['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user2_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));
	}

}
