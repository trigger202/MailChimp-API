<?php
/**
 * Created by PhpStorm.
 * User: Bozo
 * Date: 24/03/2018
 * Time: 4:51 PM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class SystemTracker extends Model
{

    protected $fillable =['name','isUpdated'];

    protected $table = "systemtracker";
}