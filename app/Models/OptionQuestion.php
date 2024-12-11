<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptionQuestion extends Model
{
    use SoftDeletes;
    protected $fillable = ['question_id','content','image','result'];



}
