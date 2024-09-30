<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleModel extends Model
{
    public $table = "module";
    protected $fillable = ["title"];


    const DASHBAORD = 1;
    CONST ENGINEERLIST = 2;

    CONST SEARCHENGINEERS = 3;

    CONST USERSLIST = 4;
}
