<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model {

	protected $table = 'users';

	protected $fillable = ['username', 'email', 'password'];

	protected $hidden = ['password', 'remember_token'];

    public function profile()
    {
        return $this->hasMany('App\Models\Profile');
    }

    public function documents()
    {
        return $this->hasMany('App\Models\Document');
    }

    public function session()
    {
        return $this->hasOne('App\Models\Session');
    }

    public function group()
    {
        return $this->belongsTo('App\Models\Group');
    }
}
