<?php namespace App\Http\Controllers;

use App\Models\Document;
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
	    if(! $this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

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
	    if(! $this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    $upload = array('file' => $request->file('file'));
        $rules = array('file' => 'required');
        $validator = Validator::make($upload, $rules);

        if (! empty($document) && ! $this->isDocument($document))
        {
            $this->setResultError("Document not found", 404);
        }
        elseif ($validator->fails())
        {
            $this->setResultError($validator->messages(), 400);
        }
        elseif (! $this->setSessionUser($request))
        {
	        $this->setResultError("Not logged in", 401);
        }
        elseif ($document !== null && Document::find($document)->user_id != $this->user->id && ! $this->isModerator())
        {
	        $this->setResultError("Unauthorized action", 403);
        }
        else
        {
            if ($request->file('file')->isValid())
            {
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
            }
            else
            {
                $this->setResultError('File is not valid', 400);
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
	    if(! $this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    $file = File::find($id);
        if (! $file)
        {
            $this->setResultError("File is not found", 404);
        }
        elseif (! $file->public && !$this->isModerator() && ! $this->isAllowed($request, 'file', $id, 'read'))
        {
            $this->setResultError("Unauthorized action", 403);
        }
        else
        {
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
	    if(! $this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    $file = File::find($id);
        if (! $file)
        {
            $this->setResultError("File is not found", 404);
        }
        elseif (! $file->public && ! $this->isAllowed($request, 'file', $id, 'read') && ! $this->isModerator())
        {
            $this->setResultError("Unauthorized action", 403);
        }
        else
        {
            $this->setResultOk();
            $this->setFileData($file);
        }
        return $this->setResponse();
    }

    /**
     * Set File Public
     *
     * @param Int $id
     * @param String $access
     * @param Request $request
     * @return Response
     */
    public function putFilePublic($id, $access, Request $request)
    {
	    if(! $this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    if (! $this->setSessionUser($request))
	    {
		    $this->setResultError("Not logged in", 401);
	    }
	    elseif( $access != 'public' && $access != 'private' )
	    {
		    $this->setResultError("Invalid access type", 400);
	    }
	    else
	    {
		    $file = File::find($id);
		    $public = $access=='public'?1:0;
		    if (! $file)
		    {
			    $this->setResultError("File is not found", 404);
		    }
		    elseif (! $this->isAllowed($request, 'file', $id, 'update') && ! $this->isModerator())
		    {
			    $this->setResultError("Unauthorized action", 403);
		    }
		    else
		    {
			    $file->public = $public;
			    $file->save();
			    $this->setResultOk();
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
	    if(! $this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    if (! $this->setSessionUser($request))
        {
            $this->setResultError("Not logged in", 401);
        }
        else
        {
            $file = File::find($id);
            if (! $file)
            {
                $this->setResultError("File is not found", 404);
            }
            elseif (! $this->isAllowed($request, 'file', $id, 'delete') && ! $this->isModerator())
            {
                $this->setResultError("Unauthorized action", 403);
            }
            else
            {
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
	    if(! $this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    $file = File::find($id);
	    if (! $this->setSessionUser($request))
	    {
		    $this->setResultError("Not logged in", 401);
	    }
	    elseif (! $file)
        {
            $this->setResultError("File is not found", 404);
        }
        elseif (! $this->isAllowed($request, 'file', $id, 'update') && ! $this->isModerator())
        {
            $this->setResultError("Unauthorized action", 403);
        }
        elseif (! in_array($access, ['read', 'update', 'delete', 'all']))
        {
            $this->setResultError("Unknown permission '{$access}'", 400);
        }
        else
        {
            $this->setAllowed(null, $id, $user, null, $access);
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
	    if(! $this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    $file = File::find($id);
	    if (! $this->setSessionUser($request))
	    {
		    $this->setResultError("Not logged in", 401);
	    }
	    elseif (! $file)
        {
            $this->setResultError("File is not found", 404);
        }
        elseif (! $this->isAllowed($request, 'file', $id, 'update') && ! $this->isModerator())
        {
            $this->setResultError("Unauthorized action", 403);
        }
        elseif (! in_array($access, ['read', 'update', 'delete', 'all']))
        {
            $this->setResultError("Unknown permission '{$access}'", 400);
        }
        else
        {
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
	    if(! $this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    $file = File::find($id);
	    if (! $this->setSessionUser($request))
	    {
		    $this->setResultError("Not logged in", 401);
	    }
	    elseif (! $file)
        {
            $this->setResultError("File is not found", 404);
        }
        elseif (! $this->isAllowed($request, 'file', $id, 'update') && ! $this->isModerator())
        {
            $this->setResultError("Unauthorized action", 403);
        }
        elseif (! in_array($access, ['read', 'update', 'delete', 'all']))
        {
            $this->setResultError("Unknown permission '{$access}'", 400);
        }
        else
        {
            $this->setUnAllowed(null, $id, $user, null, $access);
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
	    if(! $this->appKeyAvailable($request))
		    return $this->notAuthorized($request);

	    $file = File::find($id);
	    if (! $this->setSessionUser($request))
	    {
		    $this->setResultError("Not logged in", 401);
	    }
	    elseif (! $file)
        {
            $this->setResultError("File is not found", 404);
        }
        elseif (! $this->isAllowed($request, 'file', $id, 'update') && ! $this->isModerator())
        {
            $this->setResultError("Unauthorized action", 403);
        }
        elseif (! in_array($access, ['read', 'update', 'delete', 'all']))
        {
            $this->setResultError("Unknown permission '{$access}'", 400);
        }
        else
        {
            $this->setUnAllowed(null, $id, null, $group, $access);
            $this->setResultOk();
        }
        return $this->setResponse();
    }

}
