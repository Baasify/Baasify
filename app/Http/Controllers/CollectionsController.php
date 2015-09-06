<?php namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Document;
use Illuminate\Http\Request;

class CollectionsController extends MainController
{

	/**
	 * Create New Collection
	 *
	 * @param String $name
	 * @param Request $request
	 * @return Response
	 */
	public function postCollection($name, Request $request)
	{
		if(! $this->appKeyAvailable($request))
			return $this->notAuthorized($request);

		if (! $this->setSessionUser($request))
		{
			$this->setResultError("Not logged in", 401);
		}
		elseif (! $this->isAdmin())
		{
			$this->setResultError("Unauthorized action", 403);
		}
		elseif ($this->isCollection($name))
		{
			$this->setResultError("Collection name already exists", 409);
		}
		else
		{
			Collection::create(["name" => $name]);
			$this->setResultOk();
		}
		return $this->setResponse();
	}

	/**
	 * Delete Collection and all its documents
	 *
	 * @param String $name
	 * @param Request $request
	 * @return Response
	 */
	public function deleteCollection($name, Request $request)
	{
		if(! $this->appKeyAvailable($request))
			return $this->notAuthorized($request);

		$collection = $this->readCollection($name);
		if (! $this->setSessionUser($request))
		{
			$this->setResultError("Not logged in", 401);
		}
		elseif (! $this->isAdmin()) {
			$this->setResultError("Unauthorized action", 403);
		}
		elseif (! $collection)
		{
			$this->setResultError("Collection name does not exist", 404);
		}
		else
		{
			Collection::destroy($collection->id);
			$this->setResultOk();
		}
		return $this->setResponse();
	}

	/**
	 * Retrieve Collection
	 *
	 * @param String $name
	 * @param Request $request
	 * @return Response
	 */
	public function getCollection($name, Request $request)
	{
		if(! $this->appKeyAvailable($request))
			return $this->notAuthorized($request);

		$collection = $this->readCollection($name);
		if (! $collection)
		{
			$this->setResultError("Collection '{$name}' doesn't exist", 404);
		}
		elseif (! $this->setSessionUser($request))
		{
			$this->setResultError("Not logged in", 401);
		}
		elseif (! $this->isModerator())
		{
			$this->setResultError("Unauthorized action", 403);
		}
		else
		{
			$count = Document::whereCollectionId($collection->id)->count();
			$this->setResultOk(['count' => $count, 'name'=>$collection->name]);
		}
		return $this->setResponse();
	}
}
