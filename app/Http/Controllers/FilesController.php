<?php namespace App\Http\Controllers;

use App\Models\Group;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;
use App\Models\File;
use Validator;

class FilesController extends MainController
{

    /**
     * Upload New File
     *
     * @param Request $request
     * @return Response
     */

    public function postFile(Request $request)
    {
        return $this->postFileToDocument(null, $request);
    }

    /**
     * Upload New File and attach it to a document
     *
     * @param Int $document
     * @param Request $request
     * @return Response
     */

    public function postFileToDocument($document, Request $request)
    {
        $upload = array('file' => Input::file('file'));
        $rules = array('file' => 'required',);
        $validator = Validator::make($upload, $rules);

        if (!empty($document) && !$this->isDocument($document)) {
            $this->setResultError("Document not found");
        } elseif ($validator->fails()) {
            $this->setResultError($validator->messages());
        } elseif (!$this->setSessionUser($request)) {
            $this->setResultError("Log in first to upload files");
        } else {
            if (Input::file('file')->isValid()) {
                $destinationPath = '../uploads';
                $extension = Input::file('file')->getClientOriginalExtension();
                $fileName = uniqid() . '.' . $extension;

                $file = new File();
                $file->user_id = $this->user->id;
                $file->document_id = $document;
                $file->name = Input::file('file')->getClientOriginalName();
                $file->mime = Input::file('file')->getMimeType();
                $file->size = Input::file('file')->getSize();
                $file->path = $destinationPath . '/' . $fileName;
                Input::file('file')->move($destinationPath, $fileName);
                $file->save();

                $this->setAllowed(null, $file->id, $this->user->id, null, 'all');

                $this->setResultOk();
                $this->setFileData($file);
            } else {
                $this->setResultError('File is not valid');
            }
        }
        return $this->setResponse();
    }

    /**
     * Retrieve file as row data
     *
     * @param Int $id
     * @param Request $request
     * @return Response
     */

    public function getFile($id, Request $request)
    {
        $file = File::find($id);
        if (!$file) {
            $this->setResultError("File is not found");
        } elseif (!$file->public && !$this->isModerator() && !$this->isAllowed($request, 'file', $id, 'read')) {
            $this->setResultError("Unauthorized access");
        } else {
            $response = response(file_get_contents($file->path), 200)
                ->header('Content-Type', $file->mime)
                ->header('Content-Description', 'File Transfer')
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename=' . $file->name)
                ->header('Content-Transfer-Encoding', 'binary')
                ->header('Connection', 'Keep-Alive')
                ->header('Expires', '0')
                ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'public')
                ->header('Content-Length', $file->size);
            return $response;
        }
        return $this->setResponse();
    }

    /**
     * Retrieve file details and the attached document if any
     *
     * @param Int $id
     * @param Request $request
     * @return Response
     */

    public function getDetails($id, Request $request)
    {
        $file = File::find($id);
        if (!$file) {
            $this->setResultError("File is not found");
        } elseif (!$this->isAllowed($request, 'file', $id, 'read') && !$this->isModerator()) {
            $this->setResultError("Unauthorized access");
        } else {
            $this->setResultOk();
            $this->setFileData($file);
        }
        return $this->setResponse();
    }

    /**
     * Set File Public
     *
     * @param Int $id
     * @param Request $request
     * @return Response
     */

    public function putFilePublic($id, Request $request)
    {
        if (!$this->setSessionUser($request)) {
            $this->setResultError("Guests cannot edit files");
        } else {
            $file = File::find($id);
            $public = Input::get('public');
            if (!$file) {
                $this->setResultError("File is not found");
            } elseif (!$this->isAllowed($request, 'file', $id, 'update') && !$this->isModerator()) {
                $this->setResultError("Unauthorized access");
            } elseif ($public === null) {
                $this->setResultError("Bad Request");
            } else {
                $file->public = intval($public);
                $file->save();

                $this->setResultOk();
                $this->setFileData($file);
            }
        }
        return $this->setResponse();
    }

    /**
     * Delete a file
     *
     * @param Int $id
     * @param Request $request
     * @return Response
     */

    public function deleteFile($id, Request $request)
    {
        if (!$this->setSessionUser($request)) {
            $this->setResultError("Log in first to delete files");
        } else {
            $file = File::find($id);
            if (!$file) {
                $this->setResultError("File is not found");
            } elseif (!$this->isAllowed($request, 'file', $id, 'delete') && !$this->isModerator()) {
                $this->setResultError("Unauthorized access");
            } else {
                $file->delete();
                $this->setResultOk();
            }
        }
        return $this->setResponse();
    }

    /**
     * Grant Permission on a File for a User
     *
     * @param Int $id
     * @param String $access
     * @param Int $user
     * @param Request $request
     * @return Response
     */

    public function putUserPermission($id, $access, $user, Request $request)
    {
        $file = File::find($id);
        if (!$file) {
            $this->setResultError("File is not found");
        } elseif (!$this->isAllowed($request, 'file', $id, 'update') && !$this->isModerator()) {
            $this->setResultError("Unauthorized access");
        } elseif (!in_array($access, ['read', 'update', 'delete', 'all'])) {
            $this->setResultError("Unknown permission '{$access}'");
        } else {
            $this->setAllowed(null, $id, null, $user, $access);
            $this->setResultOk();
        }
        return $this->setResponse();
    }

    /**
     * Grant Permission on a File for a Group
     *
     * @param Int $id
     * @param String $access
     * @param Int $group
     * @param Request $request
     * @return Response
     */

    public function putGroupPermission($id, $access, $group, Request $request)
    {
        $file = File::find($id);
        if (!$file) {
            $this->setResultError("File is not found");
        } elseif (!$group) {
            $this->setResultError("Group name is not found");
        } elseif (!$this->isAllowed($request, 'file', $id, 'update') && !$this->isModerator()) {
            $this->setResultError("Unauthorized access");
        } elseif (!in_array($access, ['read', 'update', 'delete', 'all'])) {
            $this->setResultError("Unknown permission '{$access}'");
        } else {
            $this->setAllowed(null, $id, null, $group, $access);
            $this->setResultOk();
        }
        return $this->setResponse();
    }

    /**
     * Revoke Permission on a File for a User
     *
     * @param Int $id
     * @param String $access
     * @param Int $user
     * @param Request $request
     * @return Response
     */

    public function deleteUserPermission($id, $access, $user, Request $request)
    {
        $file = File::find($id);
        if (!$file) {
            $this->setResultError("File is not found");
        } elseif (!$this->isAllowed($request, 'file', $id, 'update') && !$this->isModerator()) {
            $this->setResultError("Unauthorized access");
        } elseif (!in_array($access, ['read', 'update', 'delete', 'all'])) {
            $this->setResultError("Unknown permission '{$access}'");
        } else {
            $this->setUnAllowed(null, $id, null, $user, $access);
            $this->setResultOk();
        }
        return $this->setResponse();
    }

    /**
     * Revoke Permission on a File for a User
     *
     * @param Int $id
     * @param String $access
     * @param Int $group
     * @param Request $request
     * @return Response
     */

    public function deleteGroupPermission($id, $access, $group, Request $request)
    {
        $file = File::find($id);
        if (!$file) {
            $this->setResultError("File is not found");
        } elseif (!$group) {
            $this->setResultError("Group name is not found");
        } elseif (!$this->isAllowed($request, 'file', $id, 'update') && !$this->isModerator()) {
            $this->setResultError("Unauthorized access");
        } elseif (!in_array($access, ['read', 'update', 'delete', 'all'])) {
            $this->setResultError("Unknown permission '{$access}'");
        } else {
            $this->setUnAllowed(null, $id, null, $group, $access);
            $this->setResultOk();
        }
        return $this->setResponse();
    }

}
