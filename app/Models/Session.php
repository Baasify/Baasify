<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model {

    protected $fillable = array('user_id', 'hash', 'permanent');

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
