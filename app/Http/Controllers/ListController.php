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
use App\SystemTracker;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation;

class ListController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private  $apiClient ;
    private $listsTracker;
    private $membersTracker;
    private $columnList = ['uniqueID', 'name'];

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

    public function getBasicListItemInfo($data)
    {

    }

    public function saveList($apiList)
    {
        MailChimpList::query()->truncate();
        DB::table('mailchimplists')->insert($apiList);
        $this->setListsTracker();
    }

    public function setListsTracker()
    {
        SystemTracker::where('id',$this->listsTracker->id)->update(['isUpdated'=>true]);
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
        if($allColumns)
            return MailChimpList::where('uniqueID',$listID)->first();
        else
            return MailChimpList::where('uniqueID',$listID)->first($this->columnList);
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

        $data = json_encode($request->all());

        $new =  json_decode($this->apiClient->createListItem($data));

        if($new==false)
        {
            return  response()->json('something went wrong adding the item', 404);
        }

        $newListItem = new MailChimpList();

        $newListItem->uniqueID =$new->id;
        $newListItem->name = $new->name;
        $newListItem->save();
        return  response()->json($newListItem, 200);

        return response()->json($request->all(), '201');

    }
    public function updateList(Request $request, $id)
    {
        if(!$this->listExists($id))
        {
            return response()->json($id.' does not exist', 404);
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


        if($this->apiClient->updateList($id, $data))
        {
            MailChimpList::where('uniqueID',$id)->update(['name'=>$request->name]);
            return response()->json(array('uniqueid'=>$id,'name'=>$request->name),'201');
        }

        return response()->json('could not update list id'.$id, 404 );


    }

    public function deleteList($listID)
    {
        $result = $this->listExists($listID,true);
        if(!$result)
        {
            return response()->json($listID.' does not exist', 404);
        }

        //delete from db and mailchimp as well
        MailChimpList::findorfail($result->id)->delete();

        if($this->apiClient->deleteList($listID))
            return response()->json($listID.' does not exist', 404);
        return response()->json($listID.' deleted', 204);


    }

    public function getCount()
    {
       $this->setListsTracker();
    }
}
