<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model {

    protected $fillable = ['name','path','mime','size','user_id','document_id','public'];

    public function document()
    {
        return $this->belongsTo('App\Models\Document');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
