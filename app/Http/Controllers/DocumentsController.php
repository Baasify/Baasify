<?php namespace App\Http\Controllers;

use App\Models\Data;
use App\Models\Document;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

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
        if (!$this->isCollection($name)) {
            $this->setResultError("Collection '{$name}' doesn't exist");
        } elseif (!$this->isAllowed($request, 'document', $id, 'read') && !$this->isModerator()) {
            $this->setResultError("Unauthorized access");
        } else {
            $document = Document::find($id);
            if (!$document) {
                $this->setResultError("Document is not found");
            } else {
                $this->setResultOk();
                $this->setDocumentData($document);
            }
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
