<?php
/**
 * Created by PhpStorm.
 * User: Bozo
 * Date: 31/03/2018
 * Time: 9:19 AM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class ListContact extends Model
{

    protected $table = "lists_contacts";
    protected $fillable =['list_id','company','address1', 'address2', 'city','state','zip','country', 'phone'];
    protected $hidden =['id','list_id','updated_at','created_at'];
    public function MailchimpList()
    {
        return $this->belongsTo(MailChimpList::class);
    }

}