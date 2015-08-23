<?php namespace App\Http\Controllers;

use App\Models\Data;
use App\Models\Document;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
class DocumentsController extends MainController
{

    /**
     * Create New Document
     *
     * @param String $name
     * @param Request $request
     * @return Response
     */

    public function postDocument($name, Request $request)
    {
        if (!$this->setSessionUser($request)) {
            $this->setResultError("Log in first to create documents");
        } elseif (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } else {
            $document = new Document();
            $data = Input::all();
            $document->data = json_encode($data);
            $document->user_id = $this->user->id;
            $document->collection_id = $this->readCollection($name)->id;
            $document->save();

            foreach ($data as $key => $value) {
                Data::create(['document_id' => $document->id, 'key' => $key, 'value' => $value]);
            }

            $this->setAllowed($document->id, null, $this->user->id, null, 'all');
            $this->setResultOk();
            $this->setDocumentData($document);
        }
        return $this->setResponse();
    }

    /**
     * Retrieve Document
     *
     * @param String $name
     * @param Int $id
     * @param Request $request
     * @return Response
     */

    public function getDocument($name, $id, Request $request)
    {
        $this->setSessionUser($request);
        $document = Document::whereId($id)->first();
        if (!$document) {
            $this->setResultError("Document is not found");
        } elseif (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } elseif (!$document->public && !$this->isModerator() && !$this->isAllowed($request, 'document', $id, 'read')) {
            $this->setResultError("Unauthorized access");
        } else {
            $this->setResultOk();
            $this->setDocumentData($document);
        }
        return $this->setResponse();
    }

    /**
     * Retrieve a list of Documents
     *
     * @param String $name
     * @param Request $request
     * @return Response
     */

    public function listDocument($name, Request $request)
    {
        $this->setSessionUser($request);
        if (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } else {
            $perPage = intval(Input::get('perPage', 10));
            $start = intval((Input::get('page', 1)-1)*$perPage);
            $query = urldecode(Input::get('query', ''));
            $table = "";
            if(!empty($query)){
                parse_str($query,$where);
                $table = ", `data`";
                $condition = array();
                foreach($where as $key=>$value){
                    $condition[] = "(`data`.`key` = '".$key. "' and `data`.`value` LIKE '%".$value."%')";
                }
                $query = " and `data`.`document_id` = `documents`.`id` and (".implode(' OR ', $condition).") ";
            }
            $data = DB::select('SELECT `documents`.`id` FROM `permissions`, `users`, `documents`, `collections`'.$table.'
                where `collections`.`name` = :name and `documents`.`collection_id` = `collections`.`id`
                and ( `documents`.`public` = 1 OR
                (`users`.`id` = :id and `permissions`.`access` = \'read\' and permissions.document_id = documents.id and
                (permissions.user_id = users.id or permissions.group_id >= permissions.group_id))) '.$query.'
                group by documents.id ORDER BY documents.created_at DESC LIMIT :start, :limit',
                ['id' => $this->user->id, 'name'=>$name, 'start'=> $start, 'limit'=>$perPage]);

            $this->setResultOk();

            $documents = array();
            foreach($data as $document)
                $documents[] = Document::whereId($document->id)->first();
            $this->setDocumentListData($documents);
        }
        return $this->setResponse();
    }

    /**
     * Update Document
     *
     * @param String $name
     * @param Int $id
     * @param Request $request
     * @return Response
     */

    public function putDocument($name, $id, Request $request)
    {
        if (!$this->setSessionUser($request)) {
            $this->setResultError("Guests cannot edit documents");
        } elseif (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } else {
            $document = Document::find($id);
            if (!$document) {
                $this->setResultError("Document is not found");
            } elseif (!$this->isAllowed($request, 'document', $id, 'update') && !$this->isModerator()) {
                $this->setResultError("Unauthorized access");
            } else {
                $data = Input::all();
                $document->data = json_encode($data);
                $document->save();

                foreach (Data::whereDocumentId($document->id)->get() as $row) {
                    $row->delete();
                }

                foreach ($data as $key => $value) {
                    Data::create(['document_id' => $document->id, 'key' => $key, 'value' => $value]);
                }

                $this->setResultOk();
                $this->setDocumentData($document);
            }
        }
        return $this->setResponse();
    }

    /**
     * Set Document Public
     *
     * @param String $name
     * @param Int $id
     * @param Request $request
     * @return Response
     */

    public function putDocumentPublic($name, $id, Request $request)
    {
        if (!$this->setSessionUser($request)) {
            $this->setResultError("Guests cannot edit documents");
        } elseif (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } else {
            $document = Document::find($id);
            $public = Input::get('public');
            if (!$document) {
                $this->setResultError("Document is not found");
            } elseif (!$this->isAllowed($request, 'document', $id, 'update') && !$this->isModerator()) {
                $this->setResultError("Unauthorized access");
            } elseif ($public === null) {
                $this->setResultError("Bad Request");
            } else {
                $document->public = intval($public);
                $document->save();

                $this->setResultOk();
                $this->setDocumentData($document);
            }
        }
        return $this->setResponse();
    }

    /**
     * Delete Document
     *
     * @param String $name
     * @param Int $id
     * @param Request $request
     * @return Response
     */

    public function deleteDocument($name, $id, Request $request)
    {
        if (!$this->setSessionUser($request)) {
            $this->setResultError("Guests cannot edit documents");
        } elseif (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } else {
            $document = Document::find($id);
            if (!$document) {
                $this->setResultError("Document is not found");
            } elseif (!$this->isAllowed($request, 'document', $id, 'delete') && !$this->isModerator()) {
                $this->setResultError("Unauthorized access");
            } else {
                $document->delete();
                $this->setResultOk();
            }
        }
        return $this->setResponse();
    }

    /**
     * Grant Permission on a Document for a User
     *
     * @param String $name
     * @param Int $id
     * @param String $access
     * @param Int $user
     * @param Request $request
     * @return Response
     */

    public function putUserPermission($name, $id, $access, $user, Request $request)
    {
        if (!$this->setSessionUser($request)) {
            $this->setResultError("Guests cannot edit permissions");
        } elseif (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } elseif (!in_array($access, ['read', 'update', 'delete', 'all'])) {
            $this->setResultError("Unknown permission '{$access}'");
        } else {
            if (!$this->isAllowed($request, 'document', $id, 'update') && !$this->isModerator()) {
                $this->setResultError("Unauthorized access");
            } else {
                $this->setAllowed($id, null, $user, null, $access);
                $this->setResultOk();
            }
        }
        return $this->setResponse();
    }

    /**
     * Grant Permission on a Document for a Group
     *
     * @param String $name
     * @param Int $id
     * @param String $access
     * @param Int $group
     * @param Request $request
     * @return Response
     */

    public function putGroupPermission($name, $id, $access, $group, Request $request)
    {
        if (!$this->setSessionUser($request)) {
            $this->setResultError("Guests cannot edit permissions");
        } elseif (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } elseif (!in_array($access, ['read', 'update', 'delete', 'all'])) {
            $this->setResultError("Unknown permission '{$access}'");
        } else {
            if (!$this->isAllowed($request, 'document', $id, 'update') && !$this->isModerator()) {
                $this->setResultError("Unauthorized access");
            } else {
                $this->setAllowed($id, null, null, $group, $access);
                $this->setResultOk();
            }
        }
        return $this->setResponse();
    }

    /**
     * Revoke Permission on a Document for a User
     *
     * @param String $name
     * @param Int $id
     * @param String $access
     * @param Int $user
     * @param Request $request
     * @return Response
     */

    public function deleteUserPermission($name, $id, $access, $user, Request $request)
    {
        if (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } elseif (!in_array($access, ['read', 'update', 'delete', 'all'])) {
            $this->setResultError("Unknown permission '{$access}'");
        } else {
            if (!$this->isAllowed($request, 'document', $id, 'update') && !$this->isModerator()) {
                $this->setResultError("Unauthorized access");
            } else {
                $this->setUnAllowed($id, null, $user, null, $access);
                $this->setResultOk();
            }
        }
        return $this->setResponse();
    }

    /**
     * Revoke Permission on a Document for a Group
     *
     * @param String $name
     * @param Int $id
     * @param String $access
     * @param Int $group
     * @param Request $request
     * @return Response
     */

    public function deleteGroupPermission($name, $id, $access, $group, Request $request)
    {
        if (!$this->setSessionUser($request)) {
            $this->setResultError("Guests cannot edit documents");
        } elseif (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } elseif (!in_array($access, ['read', 'update', 'delete', 'all'])) {
            $this->setResultError("Unknown permission '{$access}'");
        } else {
            if (!$this->isAllowed($request, 'document', $id, 'update') && !$this->isModerator()) {
                $this->setResultError("Unauthorized access");
            } else {
                $this->setUnAllowed($id, null, null, $group, $access);
                $this->setResultOk();
            }
        }
        return $this->setResponse();
    }
}
