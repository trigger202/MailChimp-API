<?php
/**
 * Created by PhpStorm.
 * User: Bozo
 * Date: 23/03/2018
 * Time: 10:31 AM
 */


namespace  App;
use \Illuminate\Database\Eloquent\Model;
class MailChimpList extends  Model
{
    protected  $fillable = ['id', 'uniqueID','name'];
    protected $table = "mailchimplists";
    protected $hidden =['id','created_at','updated_at'];


}