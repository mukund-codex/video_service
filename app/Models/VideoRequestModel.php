<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class VideoRequestModel extends Model
{
    //
    protected $primaryKey = 'id';
    protected $table = 'request';
    protected $fillable = ['request_id', 'request', 'response'];
}
