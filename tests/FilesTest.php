<?php

class FilesTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->artisan('migrate');
		$this->seed();

		$this->beforeApplicationDestroyed(function () {
			$this->artisan('migrate:rollback');
		});
	}

	public function testUpload()
	{
		$this->post('/file', [], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>["The file field is required."],
			));

		file_put_contents("UnitTestTempFile.txt", 'THIS IS JUST FOR TESTING');

		$uploadedFile = new Symfony\Component\HttpFoundation\File\UploadedFile(
			realpath('UnitTestTempFile.txt'), 'UnitTestTempFile.txt', 'text/plain', filesize('Readme.md'), null, true
		);

		$notValidUploadedFile = new Symfony\Component\HttpFoundation\File\UploadedFile(
			realpath('UnitTestTempFile.txt'), 'UnitTestTempFile.txt', 'text/plain', filesize('Readme.md'), 1, true
		);

		$this->call('POST', '/file', [], [], ['file'=>$uploadedFile], ['HTTP_X_APP_KEY'=>getenv('APP_KEY')]);
		$this->seeJson(array(
			"result"=>"error",
			"error"=>["Not logged in"],
		));

		$this->post('/user/login', ['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		$this->call('POST', '/file', [], [], ['file'=>$uploadedFile],
			['HTTP_X_APP_KEY'=>getenv('APP_KEY'), 'HTTP_X_SESSION_ID'=>$admin_hash]);
		$this->seeJson(array(
			"result"=>"ok",
			"name"=>"UnitTestTempFile.txt",
		));

		$this->call('POST', '/file', [], [], ['file'=>$notValidUploadedFile],
			['HTTP_X_APP_KEY'=>getenv('APP_KEY'), 'HTTP_X_SESSION_ID'=>$admin_hash]);
		$this->seeJson(array(
			"result"=>"error",
			"error"=>["File is not valid"],
		));


		$this->post('/collection/test', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->post('/document/test', ['title'=>'dump'], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump"
			));

		file_put_contents("UnitTestTempFile.txt", 'THIS IS JUST FOR TESTING');

		$uploadedFile = new Symfony\Component\HttpFoundation\File\UploadedFile(
			realpath('UnitTestTempFile.txt'), 'UnitTestTempFile.txt', 'text/plain', filesize('Readme.md'), null, true
		);

		$this->call('POST', '/file/2', [], [], ['file'=>$uploadedFile],
			['HTTP_X_APP_KEY'=>getenv('APP_KEY'), 'HTTP_X_SESSION_ID'=>$admin_hash]);

		$this->seeJson(array(
			"result"=>"error",
			"error"=>["Document not found"],
		));

		$this->call('POST', '/file/1', [], [], ['file'=>$uploadedFile],
			['HTTP_X_APP_KEY'=>getenv('APP_KEY'), 'HTTP_X_SESSION_ID'=>$admin_hash]);

		$this->seeJson(array(
			"result"=>"ok",
			"name"=>"UnitTestTempFile.txt",
			"title"=>"dump",
		));

		$this->post('/user', ['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		file_put_contents("UnitTestTempFile.txt", 'THIS IS JUST FOR TESTING');

		$uploadedFile = new Symfony\Component\HttpFoundation\File\UploadedFile(
			realpath('UnitTestTempFile.txt'), 'UnitTestTempFile.txt', 'text/plain', filesize('Readme.md'), null, true
		);

		$this->call('POST', '/file/1', [], [], ['file'=>$uploadedFile],
			['HTTP_X_APP_KEY'=>getenv('APP_KEY'), 'HTTP_X_SESSION_ID'=>$user_hash]);

		$this->seeJson(array(
			"result"=>"error",
			"error"=>["Unauthorized action"],
		));

	}

	public function testRetrieve()
	{
		$this->post('/user/login', ['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		file_put_contents("UnitTestTempFile.txt", 'THIS IS JUST FOR TESTING');

		$uploadedFile = new Symfony\Component\HttpFoundation\File\UploadedFile(
			realpath('UnitTestTempFile.txt'), 'UnitTestTempFile.txt', 'text/plain', filesize('Readme.md'), null, true
		);

		$this->call('POST', '/file', [], [], ['file'=>$uploadedFile],
			['HTTP_X_APP_KEY'=>getenv('APP_KEY'), 'HTTP_X_SESSION_ID'=>$admin_hash]);
		$this->seeJson(array(
			"result"=>"ok",
			"name"=>"UnitTestTempFile.txt",
		));

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->see('THIS IS JUST FOR TESTING');

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"name"=>"UnitTestTempFile.txt",
			));

		$this->post('/user', ['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->put('/file/1/read/user/2', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->see('THIS IS JUST FOR TESTING');

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"name"=>"UnitTestTempFile.txt",
			));

		$this->delete('/file/1/read/user/2', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->put('/file/1/read/group/3', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->see('THIS IS JUST FOR TESTING');

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"name"=>"UnitTestTempFile.txt",
			));

		$this->delete('/file/1/read/group/3', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));
	}

	public function testUpdating()
	{
		$this->post('/user/login', ['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		file_put_contents("UnitTestTempFile.txt", 'THIS IS JUST FOR TESTING');

		$uploadedFile = new Symfony\Component\HttpFoundation\File\UploadedFile(
			realpath('UnitTestTempFile.txt'), 'UnitTestTempFile.txt', 'text/plain', filesize('Readme.md'), null, true
		);

		$this->call('POST', '/file', [], [], ['file'=>$uploadedFile],
			['HTTP_X_APP_KEY'=>getenv('APP_KEY'), 'HTTP_X_SESSION_ID'=>$admin_hash]);
		$this->seeJson(array(
			"result"=>"ok",
			"name"=>"UnitTestTempFile.txt",
		));

		$this->post('/user', ['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		$this->put('/file/1/update/user/2', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->post('/user', ['email' => 'test2@baasify.org', 'username'=>'test2', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test2@baasify.org",
				"username" => "test2",
			));

		$user2_hash = json_decode($this->response->getContent())->data->hash;

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user2_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user2_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->put('/file/1/read/user/3', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user2_hash])
			->see('THIS IS JUST FOR TESTING');

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user2_hash])
			->seeJson(array(
				"result"=>"ok",
				"name"=>"UnitTestTempFile.txt",
			));

		$this->delete('/file/1/read/user/3', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user2_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user2_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->put('/file/1/public', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user2_hash])
			->see('THIS IS JUST FOR TESTING');

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user2_hash])
			->seeJson(array(
				"result"=>"ok",
				"name"=>"UnitTestTempFile.txt",
			));

		$this->put('/file/1/private', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/file/1', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user2_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->get('/file/1/details', ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user2_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));
	}

	public function testDeleting(){
		$this->post('/user/login', ['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		file_put_contents("UnitTestTempFile.txt", 'THIS IS JUST FOR TESTING');

		$uploadedFile = new Symfony\Component\HttpFoundation\File\UploadedFile(
			realpath('UnitTestTempFile.txt'), 'UnitTestTempFile.txt', 'text/plain', filesize('Readme.md'), null, true
		);

		$this->call('POST', '/file', [], [], ['file'=>$uploadedFile],
			['HTTP_X_APP_KEY'=>getenv('APP_KEY'), 'HTTP_X_SESSION_ID'=>$admin_hash]);
		$this->seeJson(array(
			"result"=>"ok",
			"name"=>"UnitTestTempFile.txt",
		));

		$this->delete('/file/1', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok"
			));

		$this->post('/user', ['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		file_put_contents("UnitTestTempFile.txt", 'THIS IS JUST FOR TESTING');

		$uploadedFile = new Symfony\Component\HttpFoundation\File\UploadedFile(
			realpath('UnitTestTempFile.txt'), 'UnitTestTempFile.txt', 'text/plain', filesize('Readme.md'), null, true
		);

		$this->call('POST', '/file', [], [], ['file'=>$uploadedFile],
			['HTTP_X_APP_KEY'=>getenv('APP_KEY'), 'HTTP_X_SESSION_ID'=>$admin_hash]);

		$this->seeJson(array(
			"result"=>"ok",
			"name"=>"UnitTestTempFile.txt",
		));

		$this->delete('/file/2', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>["Unauthorized action"],
			));

		$this->put('/file/2/delete/user/2', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->delete('/file/2', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		file_put_contents("UnitTestTempFile.txt", 'THIS IS JUST FOR TESTING');

		$uploadedFile = new Symfony\Component\HttpFoundation\File\UploadedFile(
			realpath('UnitTestTempFile.txt'), 'UnitTestTempFile.txt', 'text/plain', filesize('Readme.md'), null, true
		);

		$this->call('POST', '/file', [], [], ['file'=>$uploadedFile],
			['HTTP_X_APP_KEY'=>getenv('APP_KEY'), 'HTTP_X_SESSION_ID'=>$admin_hash]);

		$this->seeJson(array(
			"result"=>"ok",
			"name"=>"UnitTestTempFile.txt",
		));

		$this->notSeeInDatabase('permissions', ['access'=>'delete', 'file_id'=>'3', 'user_id'=>'2']);

		$this->put('/file/3/delete/user/2', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->seeInDatabase('permissions', ['access'=>'delete', 'file_id'=>'3', 'user_id'=>'2']);

		$this->delete('/file/3/delete/user/2', [], ['X-APP-KEY'=>getenv('APP_KEY'), 'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->notSeeInDatabase('permissions', ['access'=>'delete', 'file_id'=>'3', 'user_id'=>'2']);

	}

}
