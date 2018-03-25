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



class MailChimpController extends Controller
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
    PUBLIC CONST VALID_LANGUAGES = '"en","ar","af","be","bg","ca","zh","hr","cs","da","nl","et","fa","fi",
"fr","fr_CA","de","el","he","hi","hu","is","id","ga","it","ja","km","ko",
"lv","lt","mt","ms","mk","no","pl","pt","pt_PT","ro","ru","sr","sk","sl",
"es","es_ES","sw","sv","ta","th","tr","uk", "v"';


    /**
     * ListController constructor.
     * @param $apiClient
     */
    public function __construct(APIClient $client)
    {
        $this->apiClient = $client;
        $this->listsTracker = systemtracker::where('name','lists')->first();
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
                if($item['stats']['member_count'])
                {
                    $this->getMembers($item['id']);
                }
            }
            $this->saveList($newList);
            $this->setMembersTracker();
        }
    }

    public function getMembers($listID)
    {
        $response = json_decode($this->apiClient->getMembers($listID));
        if($response==false)
            return ;

        $memberList = $response->members;
        foreach($memberList as $member)
        {
            $newMembers= array(
                'list_id'=>$listID,
                'email'=>$member->email_address,
                'status'=>$member->status,
            );
            $this->saveMember($newMembers);
        }

    }

    public function saveMember($memberArray)
    {
        $exist = MailChimpMember::where([

            ['list_id','=',$memberArray['list_id']],
            [ 'email','=', $memberArray['email']]

        ])->first();

        if($exist)
        {
            return; /*cannot subscribe to the same list twice*/
        }
        $memberModel = new MailChimpMember();
        $memberModel->email = $memberArray['email'];
        $memberModel->list_id = $memberArray['list_id'];
        $memberModel->status = $memberArray['status'];
        $memberModel->save();
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

    public function setMembersTracker()
    {
        SystemTracker::where('id',$this->membersTracker->id)->update(['isUpdated'=>true]);
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
//        $apiResult = $this->apiClient->updateList($id, $data);

        MailChimpList::where('uniqueID',$id)->update(['name'=>$request->name]);
        return response()->json(array('uniqueid'=>$id,'name'=>$request->name),'201');



    }

    public function deleteList($listID)
    {
        $result = $this->listExists($listID);
        if($result==null)
        {
            return response()->json($listID.' does not exist', 404);
        }
//        delete from db and mailchimp as well
        MailChimpList::findorfail($result->id)->delete();

        $apiResponse = $this->apiClient->deleteList($listID);
        if($apiResponse)
            return response()->json($listID.' deleted', 204);
        return response()->json($listID.' does not exist', 404);

    }


    public function addMember(Request $request,$listID)
    {
        $result = $this->listExists($listID);
        if($result==null)
        {
            return response()->json('listID '.$listID.' does not exist', 404);
        }


        $rules =[
            'email_address'=>'required|email',
            'email_type'=>'nullable',
            'status'=> 'required|in:subscribed,unsubscribed,clean,pending',
            'merge_fields.FNAME'=>'nullable',
            'merge_fields.LNAME'=>'nullable',
            'interests.*'=>'nullable',
            'language'=>'nullable',
            'vip'=>'nullable|boolean',
            'location.latitude'=>'nullable|Integer',
            'location.longitude'=>'nullable|Integer',
            'ip_signup'=>'nullable',
            'timestamp_signup'=>'nullable',
            'ip_opt'=>'nullable',
            'timestamp_opt'=>'nullable'
        ];
        $this->validate($request, $rules);

        $data = $request->all();

        $data['email_address'] = strtolower($data['email_address']);


        $memberExists = MailChimpMember::where('email',$request->email_address)->first();
        /*check if the member is alrdy exists*/
        if($memberExists)
            return response()->json("Conflict  - member alredy exists.", 409 );

        /*update on the Mailchimp*/
//        $this->apiClient->addMember($listID,$data);
        $member = new MailChimpMember();

        $member->list_id =$listID;
        $member->email = $request->email_address;
        $member->status = $request->status;
        $member->save();

        return response()->json( $member, '200');

    }

    public function updateMember(Request $request,$listID)
    {
        $result = $this->listExists($listID);
        if($result==null)
        {
            return response()->json('listID '.$listID.' not found', 404);
        }

        $rules =[
            'email_address'=>'email',
            'email_type'=>'nullable',
            'status'=> 'in:subscribed,unsubscribed,clean,pending',
            'merge_fields.FNAME'=>'nullable',
            'merge_fields.LNAME'=>'nullable',
            'interests.*'=>'nullable',
            'language'=>'nullable',
            'vip'=>'nullable|boolean',
            'location.latitude'=>'nullable|Integer',
            'location.longitude'=>'nullable|Integer',
            'ip_signup'=>'nullable',
            'timestamp_signup'=>'nullable',
            'ip_opt'=>'nullable',
            'timestamp_opt'=>'nullable'
        ];
        $this->validate($request, $rules);
        $data = $request->all();


        $memberExists = MailChimpMember::where('email',$request->email_address)->first();
        /*check if the member is alrdy exists*/
        if($memberExists==null)
            return response()->json("member not found ", 404 );

        /*update on the Mailchimp*/
//        $this->apiClient->updateMember($listID,$request->email,$data);

            /*The only ting you can update is email others are ignored on the local side */

        MailChimpMember::where('id',$memberExists->id)->update(['status'=>$request->status]);



        return response()->json( 'updated', '201');

    }


    public function members($listID)
    {
       return MailChimpMember::where('list_id', $listID)->get(['email','status']);
    }

    private function memberExists($listID,$email)
    {
        $email = strtolower($email);
        return MailChimpMember::where([
            ['email',$email],
            ['list_id',$listID]
        ])->first();


    }

    public function deleteMember(Request $request,$listID)
    {
        $result = $this->listExists($listID);
        if($result==null)
        {
            return response()->json('listID '.$listID.' not found', 404);
        }
        $this->validate($request, ['email'=>'required|email']);
        $email = strtolower($request->email);

        $match = $this->memberExists($listID,$email);
        if(!$match)
        {
              return response()->json('member '.$request->email.' not found', 404);
        }

//        $this->apiClient->deleteMember($listID,$email); /*Delete from Mailchimp*/
        if(MailChimpMember::find($match->id)->delete())
            return response()->json('deleted '.$request->email ,200);
        return response()->json('Error...count not delete '.$request->email ,200);
    }
}
