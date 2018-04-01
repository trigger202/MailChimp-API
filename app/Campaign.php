<?php
/**
 * Created by PhpStorm.
 * User: Bozo
 * Date: 31/03/2018
 * Time: 9:22 AM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{

    protected $fillable =['list_id','from_name','from_email', 'subject','language'];
    protected $table = "campaign_defaults";
    protected $hidden =['id','list_id','updated_at','created_at'];



    public function MailchimpList()
    {
        return $this->belongsTo(MailChimpList::class);
    }
}