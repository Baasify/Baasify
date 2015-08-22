<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{

    protected $fillable = ['user_id', 'group_id', 'document_id', 'file_id', 'access'];

    public $timestamps = false;

}
