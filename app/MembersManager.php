<?php
/**
 * Created by PhpStorm.
 * User: Bozo
 * Date: 25/03/2018
 * Time: 12:37 PM
 */

namespace App;

use App\MailChimpMember;
use Illuminate\Http\Request;

class MembersManager
{

    private $apiClient;
    private $membersTracker;

    /**
     * MembersManager constructor.
     * @param $apiClient
     */
    public function __construct(APIClient $apiClient)
    {
        $this->membersTracker = systemtracker::where('name','members')->first();
        $this->apiClient = $apiClient;
    }


    public function getListTracker()
    {
        return $this->membersTracker->isUpdated;
    }
    public function getMembersTracker()
    {
        return $this->membersTracker->isUpdated;
    }
    public function listExists($listID)
    {
        return MailChimpList::where('uniqueID',$listID)->first();
    }

    public function getMembersList()
    {
        if(!$this->membersTracker)
            return response()->json('No members exist');
       return response()->json(MailChimpMember::all(), '200');
    }

    public function addMember($listID, Request $request)
    {

    }

}