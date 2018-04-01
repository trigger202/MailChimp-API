<?php
/**
 * Created by PhpStorm.
 * User: Bozo
 * Date: 23/03/2018
 * Time: 10:48 AM
 */


namespace App\Http\Controllers;

use App\APIClient;
use App\Campaign;
use App\ListContact;
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
    public function __construct()
    {
        $this->apiClient =  App('apiclient');
        $this->listsTracker = systemtracker::where('name','lists')->first();
        $this->membersTracker = systemtracker::where('name','members')->first();
        $this->apiClient->test++;
        if(!$this->listsTracker)
        {
            exit('seed tables');
        }

        $this->synchronizeDBandMailchimp();
        $this->membersManager = new MembersManager($this->apiClient);
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
        SystemTracker::where('id',$this->membersTracker->id)->update(['isUpdated'=>false]);
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
        $records = MailChimpList::get($this->columnList);
        if(count($records)==0)
        {
            return response()->json('no data found', 404);
        }
        return response()->json($records,200);
    }

    public function getList($listID)
    {
        $record = $this->listExists($listID);
        if($record==null)
            return response()->json('Not found',404);

        $contactInfo = ListContact::where('list_id',$listID)->first();
        if($contactInfo==null)
        {
            return response()->json('Error, something is wrong with '.$listID.' contact info. Please update contact data first', 400);
        }

        $campaignData = Campaign::where('list_id',$listID)->first();
        if(!$campaignData)
        {
            return response()->json('Error, something is wrong with '.$listID.' campaign info. Please update contact data first', 400);
        }
        /*        construct the final response object*/
        $final = array('id'=>$listID,'contact'=>$campaignData->toArray(),'campaign_defaults'=>$campaignData->toArray());
        return response()->json($final, 200);
    }

    public function listExists($listID)
    {
        return MailChimpList::where('uniqueID',$listID)->first();
    }

    public function createList(Request $request)
    {
        /*we generate random unique ID for each new item*/
        $newListID = uniqid();

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

        /*valid data, so go ahead and save the data*/
        $contactData = $request->contact;
        $campaignData = $request->campaign_defaults;
        $campaignData= array_add($campaignData, 'list_id',$newListID);
        $contactData= array_add($contactData, 'list_id',$newListID);


        $newListItem = new MailChimpList();
        $newListItem->uniqueID =$newListID;
        $newListItem->name = $request->name;
        $newListItem->save();

        $campaign = new Campaign($campaignData);
        $campaign->save();

        $contact = new ListContact($contactData);
        $contact->save();


        $data = $request->all();
        $data = array('id' => $newListID) + $data;


        return  response()->json($data, 200);
    }




    public function updateList(Request $request, $listID)
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

        $record = $this->listExists($listID);
        if($record==null)
        {
            return response()->json('Not found', 404);
        }
        /*valid data, so go ahead and save the data*/
        $contactData = $request->contact;
        $campaignData = $request->campaign_defaults;


        $this->validate($request, $rules);
        /*item exist and data is valid, so we can go ahead and update the record*/
        $record->update($request->all());
        return response()->json($record,'200');

    }

    public function deleteList($listID)
    {
        $record = $this->listExists($listID);
        if($record==null)
        {
            return response()->json('Not Found', 404);
        }
        $record->delete();
        return response()->json(NULL, 204);

    }


    public function addMember(Request $request,$listID)
    {
        $result = $this->listExists($listID);
        if($result==null)
        {
            return response()->json('listID '.$listID.' does not exist', 404);
        }
        /*dd373ace8118cd0da914ea708e9138f0-us12*/
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








































