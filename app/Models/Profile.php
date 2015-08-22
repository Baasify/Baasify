<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model {

    protected $fillable = ['key','value','user_id'];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
