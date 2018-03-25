<?php
/**
 * Created by PhpStorm.
 * User: Bozo
 * Date: 25/03/2018
 * Time: 12:37 PM
 */

namespace App;

use App\MailChimpMember;

class MembersManager
{

    private $apiClient;

    /**
     * MembersManager constructor.
     * @param $apiClient
     */
    public function __construct(APIClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }


    public function membersList()
    {
       return MailChimpMember::all();
    }

}