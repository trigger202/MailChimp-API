<?php
/**
 * Created by PhpStorm.
 * User: Bozo
 * Date: 23/03/2018
 * Time: 10:43 AM
 */

namespace App;
use Illuminate\Database\Eloquent\Model;

class MailChimpMember extends Model
{
    protected $fillable =['email', 'id','list_id'];

}