<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model {

    protected $fillable = ['name'];

    public function documents()
    {
        return $this->hasMany('App\Models\Document');
    }
}
