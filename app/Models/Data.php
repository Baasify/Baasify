<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Data extends Model {

    protected $fillable = ['key','value','document_id'];

    public $timestamps = false;

    public function document()
    {
        return $this->belongsTo('App\Models\Document');
    }

}
