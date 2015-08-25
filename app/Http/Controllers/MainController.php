<?php namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Permission;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Session;
use App\Models\Collection;
use App\Models\User;
use App\Models\File;

use Laravel\Lumen\Routing\Controller as BaseController;

class MainController extends BaseController
{

    protected $hash;
    protected $appID;
    protected $user;
    protected $content = array();

	/**
	 * Check if there is any problem with the app key
	 *
	 * @param Request $request
	 * @return Response
	 */
	protected function appKeyAvailable($request)
	{
		$app_key = getenv('APP_KEY');

		if (empty($app_key)) {
			return false;
		}
		if (empty($request->server()['HTTP_X_APP_KEY'])) {
			return false;
		}
		if ($app_key != $request->server()['HTTP_X_APP_KEY']) {
			return false;
		}
		return true;
	}

	/**
	 * Kill the request and return the app key error
	 *
	 * @return Response
	 */
	protected function notAuthorized($request)
	{
		$app_key = getenv('APP_KEY');

		if (empty($app_key)) {
			$this->setResultError('Set app key first');
		}elseif (empty($request->server()['HTTP_X_APP_KEY'])) {
			$this->setResultError('No app key provided');
		}elseif ($app_key != $request->server()['HTTP_X_APP_KEY']) {
			$this->setResultError('Mismatched app key');
		}
		return $this->setResponse();
	}

	/**
	 * Preparing Response
	 *
	 * @return Response
	 */
	protected function setResponse()
	{
		$response = new Response();
		$response->setContent($this->content);
		return $response;
	}

    /**
     * Prepare ok response
     *
     * @param String $data
     * @return Void
     */

    protected function setResultOk($data = "")
    {
        $this->content['result'] = "ok";
        $this->content['http_code'] = 200;
        $this->content['data'] = $data;
    }

    /**
     * Prepare error response
     *
     * @param String $error
     * @return Void
     */

    protected function setResultError($error, $code = 200)
    {
	    $this->content['http_code'] = $code;
        $this->content['result'] = "error";
        $this->content['error'] = $error;
    }

    /**
     * Check permissions for users and groups
     *
     * @param Request $request
     * @param String $type
     * @param Int $type_id
     * @param String $access
     * @return Bool
     */

    protected function isAllowed(Request $request, $type, $type_id, $access)
    {
        $type = 'where' . ucfirst($type) . 'Id';

        if (!$this->setSessionUser($request)) {
            // NOT LOGGED IN -- CHECK PUBLIC ACCESS
            $permission = Permission::$type($type_id)
                ->whereGroupId(null)
                ->whereUserId(null)
                ->whereAccess($access)
                ->first();
        } else {
            $user = $this->user;
            $permission = Permission::$type($type_id)
                ->whereUserId($user->id)
                ->whereAccess($access)
                ->first();
            if(!$permission)
                $permission = Permission::$type($type_id)
                    ->whereGroupId($user->group_id)
                    ->whereAccess($access)
                    ->first();
        }
        return $permission != null;
    }

    /**
     * Grant permissions for users and groups to read and update and delete documents and files
     *
     * @param Int $document_id
     * @param Int $file_id
     * @param Int $user_id
     * @param Int $group_id
     * @param String $access
     * @return Void
     */

    protected function setAllowed($document_id = null, $file_id = null, $user_id = null, $group_id = null, $access = null)
    {
        if ($access == 'all') {
            $access = ['read', 'update', 'delete'];
            foreach ($access as $row) {
                $permission = new Permission();
                $permission->document_id = $document_id;
                $permission->file_id = $file_id;
                $permission->user_id = $user_id;
                $permission->group_id = $group_id;
                $permission->access = $row;
                $permission->save();
            }

        } else {
            $permission = new Permission();
            $permission->document_id = $document_id;
            $permission->file_id = $file_id;
            $permission->user_id = $user_id;
            $permission->group_id = $group_id;
            $permission->access = $access;
            $permission->save();
        }
    }

    /**
     * Revoke permissions for users and groups to read and update and delete documents and files
     *
     * @param Int $document_id
     * @param Int $file_id
     * @param Int $user_id
     * @param Int $group_id
     * @param String $access
     * @return Void
     */

    protected function setUnAllowed($document_id = null, $file_id = null, $user_id = null, $group_id = null, $access = null)
    {
        if ($access == 'all') {
            $access = ['read', 'update', 'delete'];
            foreach ($access as $row) {
                Permission::whereDocumentId($document_id)
                    ->whereFileId($file_id)
                    ->whereGroupId($group_id)
                    ->whereUserId($user_id)
                    ->whereAccess($row)
                    ->first()->delete();
            }
        } else {
            Permission::whereDocumentId($document_id)
                ->whereFileId($file_id)
                ->whereGroupId($group_id)
                ->whereUserId($user_id)
                ->whereAccess($access)
                ->first()->delete();
        }
    }

    /**
     * Check if the Document is available
     *
     * @param Int $id
     * @return Bool
     */

    protected function isDocument($id)
    {
        return Document::whereId($id)->first() !== NULL;
    }

    /**
     * Add file data to response data
     *
     * @param File $file
     * @return Void
     */

    protected function setFileData(File $file)
    {
        $this->content['data'] = $this->prepareFileData($file);
        if ($file->document) {
            $this->content['data']['document'] = $this->prepareDocumentData($file->document);
        }
    }

    /**
     * Add file data to response data
     *
     * @param File $file
     * @return Array
     */

    protected function prepareFileData(File $file)
    {
        $data = [
            '@id' => $file->id,
            '_created_at' => $file->created_at,
            '_author' => $file->user->username,
            'name' => $file->name,
            'mime' => $file->mime,
            'size' => $file->size,
        ];
        return $data;
    }

    /**
     * Check if the Collection is available
     *
     * @param String $name
     * @return Bool
     */

    protected function isCollection($name)
    {
        return Collection::whereName($name)->first() !== NULL;
    }

    /**
     * Retrieve Collection By Name
     *
     * @param String $name
     * @return Collection
     */

    protected function readCollection($name)
    {
        return Collection::whereName($name)->first();
    }

    /**
     * Add document data to response data
     *
     * @param Document $document
     * @return Void
     */

    protected function setDocumentData(Document $document)
    {
        $this->content['data'] = $this->prepareDocumentData($document);
        foreach ($document->files as $file) {
            $this->content['data']['files'][] = $this->prepareFileData($file);
        }
    }

    /**
     * Add list of document data to response data
     *
     * @param Array $documents
     * @return Void
     */

    protected function setDocumentListData(Array $documents)
    {
        foreach($documents as $document) {
            $data = $this->prepareDocumentData($document);
            foreach ($document->files as $file) {
                $data['files'][] = $this->prepareFileData($file);
            }
            $this->content['data'][] = $data;
        }
    }

    /**
     * Prepare error response
     *
     * @param Document $document
     * @return Array
     */

    protected function prepareDocumentData(Document $document)
    {
        $data = [
            '@id' => intval($document->id),
            '@class' => $document->collection->name,
            '_created_at' => $document->created_at,
            '_updated_at' => $document->updated_at,
            '_author' => $document->user->username,
        ];
        $document->data = json_decode($document->data);
        foreach ($document->data as $key => $value) {
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * Check if user has admin privilege
     *
     * @return Bool
     */

    protected function isAdmin()
    {
        if(!$this->user) return false;
        return $this->user->group->id == 1;
    }

    /**
     * Check if user has moderator privilege
     *
     * @return Bool
     */

    protected function isModerator()
    {
        if(!$this->user) return false;
        return  $this->user->group->id < 3;
    }

    /**
     * Check if user is logged in
     *
     * @param Request $request
     * @return Bool
     */

    protected function isSessionEmpty(Request $request)
    {
        $hash = $request->header('X-SESSION-ID');
        return empty($hash);
    }

    /**
     * Create session hash for the logged in user
     *
     * @return Void
     */

    protected function setSessionHash()
    {
        $hash = uniqid();
        Session::create([
            'user_id' => $this->user->id,
            'hash' => $hash
        ]);
        $this->hash = $hash;
    }

    /**
     * Destroy session hash for the logged in user
     *
     * @param Request $request
     * @return Bool
     */

    protected function destroySessionUser(Request $request)
    {
        $hash = $request->header('X-SESSION-ID');
        $session = Session::whereHash($hash)->first();

        if (!$session) return false;

        return Session::destroy($session->id) > 0;
    }

    /**
     * Check if the hash is valid to get the user linked to the hash
     *
     * @param Request $request
     * @return Bool
     */

    protected function setSessionUser(Request $request)
    {
        $hash = $request->header('X-SESSION-ID');
        $session = Session::whereHash($hash)->first();
        if (!$session) return false;
        $this->hash = $hash;
        $this->user = $session->user;
        return true;
    }

    /**
     * Add user data to response data
     *
     * @return Void
     */

    protected function setUserData(User $user = null)
    {
        if($user === null)
            $user = $this->user;
        $this->content['data'] = [
            'username' => $user->username,
            'email' => $user->email,
            'created_at' => $user->created_at
        ];
        if($user !== null)
            $this->content['data']['hash'] = $this->hash;

        foreach ($user->profile as $row) {
            $this->content['data']['profile'][$row->key] = $row->value;
        }
    }

}
