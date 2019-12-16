<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class VideoResponseModel extends Model
{
    //
    protected $primaryKey = 'id';
    protected $table = 'response';
    protected $fillable = ['request_id', 'response'];
}
