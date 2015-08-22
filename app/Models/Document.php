<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model {

    protected $fillable = ['data','user_id','collection_id','private'];

    public function collection()
    {
        return $this->belongsTo('App\Models\Collection');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function data()
    {
        return $this->hasMany('App\Models\Data');
    }

    public function files()
    {
        return $this->hasMany('App\Models\File');
    }

}
