<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model {

    protected $table = "devices";

    protected $fillable = ['platform','environment','udid','token','user_id'];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
