## Baasify

Baasify is an open source backend-as-a-service in its early stage. Built on [Lumen](http://lumen.laravel.com/).
 
#### Current Functions
 
* Content Management.
* File Management.
* Users Management.

#### Planned Functions and Features

* Push Notifications.
* Database Management.
* Analytics.
* SDKs for both iOS and Android.

## How to build Baasify

1. Rename .env.example to .env and configure your database settings.
2. Set your app key.
3. Go to your Baasify directory and type:
 
```bash
composer update
php artisan migrate
php artisan db:seed
```

## How to use Baasify

#### Login

`POST /login`

Example:

```
 curl -X POST http://localhost:8000/login \
     -d '{"email" : "user@baasify.org", "password" : "baasify"}' \
     -H Content-type:application/json \
     -H X-APP-KEY:1234567890
```

#### Signup

`POST /user`

Example:

```
 curl -X POST http://localhost:8000/user \
     -d '{"email" : "user@baasify.org", "username" : "baasify", "password" : "baasify"}' \
     -H Content-type:application/json \
     -H X-APP-KEY:1234567890
```

#### Logout

`POST /logout`

Example:

```
 curl -X POST http://localhost:8000/logout \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Profile

`GET /me`

Example:

```
 curl http://localhost:8000/me \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

**Requires Moderator Privilege**

`GET /user/{user-id}`

Example:

```
 curl http://localhost:8000/user/2 \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Change Password

`PUT /password`

Change current logged in user password:

```
curl -X PUT http://localhost:8000/password \
    -d '{"old_password" : "baasify", "new_password" : "123456"}' \
    -H Content-type:application/json \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

#### Update User

`PUT /user`

Update current logged in user data:

```
curl -X PUT http://localhost:8000/user \
    -d '{"username" : "user", "email" : "new_email@baasify.org", "extra_data" : "my data"}' \
    -H Content-type:application/json \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

**Requires Administrator Privilege**

`PUT /user/{user-id}`

Update user data:

```
curl -X PUT http://localhost:8000/user/3 \
    -d '{"username" : "user", "email" : "new_email@baasify.org", "extra_data" : "my data"}' \
    -H Content-type:application/json \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

#### Change User Group

**Requires Administrator Privilege**

`PUT /group/{user-id}/{group-id}`

Update user group:

```
curl -X PUT http://localhost:8000/group/1/3 \
    -H Content-type:application/json \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

#### Create Collection

**Requires Administrator Privilege**

`POST /collection/{collection-name}`

Collections are the containers of the documents. To create a new collection:

```
 curl -X POST http://localhost:8000/collection/myposts \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Retrieve Collection

**Requires Moderator Privilege**

`GET /collection/{collection-name}`

Retrieve name and number of documents under this collection:

```
 curl http://localhost:8000/collection/myposts \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Delete Collection

**Requires Administrator Privilege**

`GET /collection/{collection-name}`

Delete collection and all its documents:

```
 curl -X DELETE http://localhost:8000/collection/myposts \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Create Document

`POST /document/{collection-name}`

Documents are used to store data:

```
 curl -X POST http://localhost:8000/document/myposts \
    -d '{"title" : "My Title", "content" : "My Amazing Content"}' \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Retrieve Document

`GET /document/{collection-name}/{document-id}`

Retrieve data stored in the document requested with any attached files:

```
 curl http://localhost:8000/document/myposts/1 \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Update Document

`PUT /document/{collection-name}/{document-id}`

Overwrite data stored in the document:

```
curl -X PUT http://localhost:8000/document/myposts/1 \
    -d '{"title" : "My Title", "content" : "My Updated Amazing Content"}' \
    -H Content-type:application/json \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

#### Delete Document

`DELETE /document/{collection-name}/{document-id}`

Delete document and all its files:

```
 curl -X DELETE http://localhost:8000/document/myposts/1 \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Grant Permissions on a Document

`PUT /document/{collection-name}/{document-id}/grant/{access}/user/{user-id}`

`PUT /document/{collection-name}/{document-id}/grant/{access}/group/{group-id}`

Grant permission on a document (One of: `read`,`update`,`delete`,`all`):

```
curl -X PUT http://localhost:8000/document/myposts/1/grant/read/user/1 \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

```
curl -X PUT http://localhost:8000/document/myposts/1/grant/read/group/3 \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

#### Revoke Permissions on a Document

`DELETE /document/{collection-name}/{document-id}/revoke/{access}/user/{user-id}`

`DELETE /document/{collection-name}/{document-id}/revoke/{access}/group/{group-id}`

Revoke permission on a document (One of: `read`,`update`,`delete`,`all`):

```
curl -X DELETE http://localhost:8000/document/myposts/1/revoke/read/user/1 \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

```
curl -X DELETE http://localhost:8000/document/myposts/1/revoke/read/user/1 \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

#### Upload File

`POST /file`

Create and upload file:

```
 curl -X POST http://localhost:8000/file \
     -F file=@image.jpg \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

`POST /file/{document-id}`

Create and upload file and attach it to a Document:

```
 curl -X POST http://localhost:8000/file/1 \
     -F file=@image.jpg \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Retrieve File

`GET /file/{file-id}`

Retrieve file as row data:

```
 curl http://localhost:8000/file/1 \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Retrieve File Details

`GET /file/{file-id}`

Retrieve file details and the attached document if any:

```
 curl http://localhost:8000/file/1 \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Delete File

`DELETE /file/{file-id}`

Delete a file:

```
 curl -X DELETE http://localhost:8000/file/1 \
     -H X-SESSION-ID:1234567890 \
     -H X-APP-KEY:1234567890
```

#### Grant Permissions on a File

`PUT /file/{file-id}/grant/{access}/user/{user-id}`

`PUT /file/{file-id}/grant/{access}/group/{group-id}`

Grant permission on a document (One of: `read`,`update`,`delete`,`all`):

```
curl -X PUT http://localhost:8000/file/1/grant/read/user/1 \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

```
curl -X PUT http://localhost:8000/file/1/grant/all/group/3 \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

#### Revoke Permissions on a File

`DELETE /file/{file-id}/revoke/{access}/user/{user-id}`

`DELETE /file/{file-id}/revoke/{access}/group/{group-id}`

Revoke permission on a document (One of: `read`,`update`,`delete`,`all`):

```
curl -X DELETE http://localhost:8000/file/1/revoke/update/user/1 \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

```
curl -X DELETE http://localhost:8000/file/1/revoke/delete/user/1 \
    -H X-SESSION-ID:1234567890 \
    -H X-APP-KEY:1234567890
```

## License

Baasify is an open source software licensed under the [MIT license](http://opensource.org/licenses/MIT)
