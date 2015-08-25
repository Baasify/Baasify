<?php

class DataTest extends TestCase
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

	public function testDeletingDocuments()
	{
		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		$this->post('/collection/test',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->delete('/document')
			->seeJson(array(
				"result"=>"error",
				"error"=>"not found",
			));

		$this->delete('/document/test')
			->seeJson(array(
				"result"=>"error",
				"error"=>"cannot access /document/test using DELETE",
			));

		$this->delete('/document/test/1',[],['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Not logged in",
			));

		$this->post('/user',['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		$this->delete('/document/test2/1',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Collection 'test2' doesn't exist"
			));

		$this->delete('/document/test/1',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Document is not found",
			));

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->delete('/document/test/1',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));
		$this->notSeeInDatabase('documents',['id'=>1]);

		$this->delete('/document/test/2',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->put('/document/test/2/grant/delete/user/2',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->put('/document/test/2/grant/delete/user/2',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->delete('/document/test/2',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));
		$this->notSeeInDatabase('documents',['id'=>2]);

		$this->put('/document/test/3/grant/delete/group/3',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->put('/document/test/4/grant/delete/group/3',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->delete('/document/test/3',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));
		$this->notSeeInDatabase('documents',['id'=>3]);

		$this->delete('/document/test/4/revoke/delete/group/3',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->delete('/document/test/4',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));
		$this->seeInDatabase('documents',['id'=>4]);

		$this->delete('/document/test/5',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));
		$this->notSeeInDatabase('documents',['id'=>5]);
	}

	public function testUpdatingDocuments()
	{
		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		$this->post('/collection/test',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->put('/document')
			->seeJson(array(
				"result"=>"error",
				"error"=>"not found",
			));

		$this->put('/document/test')
			->seeJson(array(
				"result"=>"error",
				"error"=>"cannot access /document/test using PUT",
			));

		$this->put('/document/test/1',[],['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Not logged in",
			));

		$this->post('/user',['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		$this->put('/document/test2/1',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Collection 'test2' doesn't exist"
			));

		$this->put('/document/test/1',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Document is not found",
			));

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->put('/document/test/1',['title'=>'dump2'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->put('/document/test/1',['title'=>'dump2'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump2",
			));

		$this->put('/document/test/2',['title'=>'dump2'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump2",
			));

		$this->put('/document/test/1/grant/update/user/2',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->put('/document/test/1',['title'=>'dump3'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump3",
			));

		$this->delete('/document/test/1/revoke/update/user/2',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->put('/document/test/1',['title'=>'dump4'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->put('/document/test/1/grant/update/group/3',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->put('/document/test/1',['title'=>'dump5'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump5",
			));

		$this->delete('/document/test/1/revoke/update/group/3',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->put('/document/test/1',['title'=>'dump6'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));
	}

	public function testRetrievingDocuments()
	{
		$this->post('/user',['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Collection 'test' doesn't exist"
			));

		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		$this->post('/collection/test',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->get('/document/test/1',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->get('/document/test/2',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->get('/document/test/1',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->get('/document/test/2',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->get('/document/test',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$count = count(json_decode($this->response->getContent())->data);

		$this->assertTrue($count===2);

		$this->get('/document/test',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$count = count(json_decode($this->response->getContent())->data);

		$this->assertTrue($count===1);

		$this->put('/document/test/1/grant/read/user/2',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/document/test/1',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->get('/document/test',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$count = count(json_decode($this->response->getContent())->data);

		$this->assertTrue($count===2);

		$this->delete('/document/test/1/revoke/read/user/2',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/document/test/1',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->get('/document/test',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$count = count(json_decode($this->response->getContent())->data);

		$this->assertTrue($count===1);

		$this->put('/document/test/1/grant/read/group/3',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/document/test/1',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->get('/document/test',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$count = count(json_decode($this->response->getContent())->data);

		$this->assertTrue($count===2);

		$this->delete('/document/test/1/revoke/read/group/3',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/document/test/1',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->get('/document/test',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$count = count(json_decode($this->response->getContent())->data);

		$this->assertTrue($count===1);



		$this->put('/document/test/1/public',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/document/test/1',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump",
			));

		$this->get('/document/test',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$count = count(json_decode($this->response->getContent())->data);

		$this->assertTrue($count===2);

		$this->put('/document/test/1/private',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->get('/document/test/1',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->get('/document/test',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$count = count(json_decode($this->response->getContent())->data);

		$this->assertTrue($count===1);

		$this->put('/document/test/1/privat',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"not found",
			));
	}

	public function testAddingDocuments()
	{
		$this->post('/document')
			->seeJson(array(
				"result"=>"error",
				"error"=>"not found",
			));

		$this->post('/document/test',[],['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Not logged in",
			));

		$this->post('/user',['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Collection 'test' doesn't exist"
			));

		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		$this->post('/collection/test',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump"
			));

		$this->seeInDatabase('documents',['user_id' => 1]);

		$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"ok",
				"title"=>"dump"
			));

		$this->seeInDatabase('documents',['user_id' => 2]);
	}

	public function testCollections()
	{
		$this->post('/collection')
			->seeJson(array(
				"result"=>"error",
				"error"=>"not found",
			));

		$this->post('/collection/test',[],['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Not logged in",
			));

		$this->post('/user',['email' => 'test@baasify.org', 'username'=>'test', 'password'=>'123456'],
			['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "test@baasify.org",
				"username" => "test",
			));

		$user_hash = json_decode($this->response->getContent())->data->hash;

		$this->post('/collection/test',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action",
			));

		$this->post('/login',['email' => 'user@baasify.org', 'password'=>'baasify'], ['X-APP-KEY'=>getenv('APP_KEY')])
			->seeJson(array(
				"result"=>"ok",
				"email" => "user@baasify.org",
				"username" => "Baasify",
			));

		$admin_hash = json_decode($this->response->getContent())->data->hash;

		$this->assertTrue(!empty($admin_hash));

		$this->post('/collection/test',[],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));

		$this->seeInDatabase('collections',['name'=>'test']);

		for($i = 0; $i < 10; $i++){
			$this->post('/document/test',['title'=>'dump'],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
				->seeJson(array(
					"result"=>"ok",
				));
		}

		$this->get('/collection/test',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action"
			));

		$this->get('/collection/test',['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
				"data"=>["count"=>10,"name"=>"test"],
			));

		$this->delete('/collection/test', [],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$user_hash])
			->seeJson(array(
				"result"=>"error",
				"error"=>"Unauthorized action"
			));

		$this->delete('/collection/test', [],['X-APP-KEY'=>getenv('APP_KEY'),'X-SESSION-ID'=>$admin_hash])
			->seeJson(array(
				"result"=>"ok",
			));
	}

}
