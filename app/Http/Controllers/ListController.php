<?php
/**
 * Created by PhpStorm.
 * User: Bozo
 * Date: 23/03/2018
 * Time: 10:48 AM
 */


namespace App\Http\Controllers;

use App\APIClient;
use App\MailChimpList;
use App\MailChimpMember;
use App\SystemTracker;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\MembersManager;



class ListController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private  $apiClient ;
    private $membersManager;
    private $listsTracker;
    private $membersTracker;
    private $columnList = ['uniqueID', 'name'];
    private const TRACKERVAL = false;


    /**
     * ListController constructor.
     * @param $apiClient
     */
    public function __construct(APIClient $client)
    {
        $this->apiClient = $client;
        $this->listsTracker = systemtracker::where('name','lists')->first();;
        $this->membersTracker = systemtracker::where('name','members')->first();
        $this->synchronizeDBandMailchimp();
        $this->membersManager = new MembersManager($client);
    }

    private function synchronizeDBandMailchimp()
    {
        if($this->getListTracker())
        {   return;  }

        $mailChimpList = json_decode($this->apiClient->getLists(),true);
        $newList = array();
        if(isset($mailChimpList['lists']))
        {
            foreach ($mailChimpList['lists'] as $item)
            {
                $newList[] = array('uniqueID'=>$item['id'], 'name'=>$item['name']);
            }
            $this->saveList($newList);
        }
    }

    public function saveList($apiList)
    {
        MailChimpList::query()->truncate();
        DB::table('mailchimplists')->insert($apiList);
        $this->setListsTracker();
    }

    public function setListsTracker()
    {
        SystemTracker::where('id',$this->listsTracker->id)->update(['isUpdated'=>false]);
    }

    public function getListTracker()
    {
        return $this->listsTracker->isUpdated;
    }
    public function getMembersTracker()
    {
        return $this->membersTracker->isUpdated;
    }
    public function getLists()
    {
        return MailChimpList::get($this->columnList);
    }

    public function getList($listID)
    {
        $result = $this->listExists($listID);
        if($result)
            return response()->json($result, 200);
        return response()->json('does not exist', 404);

    }
    public function listExists($listID, $allColumns = false)
    {
        return MailChimpList::where('uniqueID',$listID)->first();


    }

    public function createList(Request $request)
    {

        $rules =[
            'name'=>'required',

            'contact.company'=>'required',
            'contact.address1'=>'required',
            'contact.address2'=>'nullable',
            'contact.city'=>'required',
            'contact.state'=>'required',
            'contact.zip'=>'required',
            'contact.country'=>'required',
            'contact.phone'=>'nullable|integer',

            'permission_reminder'=>'required',
            'use_archive_bar'=>     'nullable|boolean',

            'campaign_defaults.from_name'=>'required',
            'campaign_defaults.from_email'=>'required',
            'campaign_defaults.subject'=>'required',
            'campaign_defaults.language'=>'required',

            'notify_on_subscribe'=>'nullable|email',
            'email_type_option'=>'required|boolean'

        ];

        $this->validate($request, $rules);

        $requestData = json_encode($request->all());
        $apiResponse =  json_decode($this->apiClient->createListItem($requestData));

        if($apiResponse==false)
        {
            return  response()->json('something went wrong adding the item', 404);
        }

        $newListItem = new MailChimpList();
        $newListItem->uniqueID =$apiResponse->id;
        $newListItem->name = $apiResponse->name;
        $newListItem->save();
        return  response()->json($newListItem, 200);


    }
    public function updateList(Request $request, $id)
    {

        $result = $this->listExists($id);
        if($result==null)
        {
            return response()->json($id.' does not exist main', 404);
        }

        $rules =[
                'name'=>'required',

                'contact.company'=>'required',
                'contact.address1'=>'required',
                'contact.address2'=>'nullable',
                'contact.city'=>'required',
                'contact.state'=>'required',
                'contact.zip'=>'required',
                'contact.country'=>'required',
                'contact.phone'=>'nullable|integer',

                'permission_reminder'=>'required',
                'use_archive_bar'=>     'nullable|boolean',

                'campaign_defaults.from_name'=>'required',
                'campaign_defaults.from_email'=>'required',
                'campaign_defaults.subject'=>'required',
                'campaign_defaults.language'=>'required',

                'notify_on_subscribe'=>'nullable|email',
                'email_type_option'=>'required|boolean'

            ];

        $this->validate($request, $rules);
        $data = $request->all();
        $apiResult = $this->apiClient->updateList($id, $data);

        if($apiResult)
        {
            MailChimpList::where('uniqueID',$id)->update(['name'=>$request->name]);
            return response()->json(array('uniqueid'=>$id,'name'=>$request->name),'201');
        }

        return response()->json('could not update list id'.$id, 404 );


    }

    public function deleteList($listID)
    {
        $result = $this->listExists($listID);
        if($result==null)
        {
            return response()->json($listID.' does not exist main', 404);
        }
//        delete from db and mailchimp as well
        MailChimpList::findorfail($result->id)->delete();

        $apiResponse = $this->apiClient->deleteList($listID);
        if($apiResponse)
            return response()->json($listID.' deleted', 204);
        return response()->json($listID.' does not exist', 404);

    }

    public function membersIndex()
    {
       return $this->membersManager->membersList();

    }
}
