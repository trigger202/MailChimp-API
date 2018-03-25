<?php

namespace App;


use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class APIClient
{
    /*change this if your datacenter is different*/
    private $url = "https://us12.api.mailchimp.com/3.0";
    private $httpClient;
    private $api_key ;




    /**
     * APIClient constructor.
     * @param $httpClient
     *
     */

    /* pass the api key in the constructor  */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        $this->httpClient = new Client();
    }

    public function getTotalListCount()
    {
        $url = $this->url."/lists?fields=total_items";
        try
        {
            $response = $this->API_Request('GET',$url);
            if($response->getStatusCode()==200)
                return $response->getBody()->getContents();
            return false;
        }
        catch (GuzzleException $e)
        {
            $e->getMessage();
        }
    }


    /*gets all available lists*/
    public function getLists()
    {
        /*TODO -- change to pagination*/
        $countObj = json_decode($this->getTotalListCount());
        $count=$countObj->total_items;
        $url = $this->url."/lists?count=$count&fields= lists.id,lists.name,lists.stats.member_count,total_items";
//        $url = $this->url."/lists";

        try
        {
            $response = $this->API_Request('GET',$url);
            if($response->getStatusCode()==200)
                return $response->getBody()->getContents();
            return false;
        }
        catch (GuzzleException $e)
        {
            $e->getMessage();
        }
    }


    /*returns singe list if exists */
    public function getList($listID)
    {
        $url = $this->url.'/lists/'.$listID.'?fields= id,name,stats.member_count';

        try
        {
            $response = $this->API_Request('GET',$url);
            if($response->getStatusCode()==200)
                return $response->getBody()->getContents();
            return false;
        }
        catch (GuzzleException $e)
        {
            $e->getMessage();
        }
    }


    /*create a new list. */
    public function createListItem($jsonData)
    {
        $url = $this->url.'/lists/';
        try
        {
            $response = $this->API_Request('POST',$url,$jsonData);
            if ($response->getStatusCode() == 200)
            {
                return $response->getBody()->getContents();
            }
            return false;
        }
        catch (GuzzleException $e)
        {
            $e->getMessage();
        }
    }


    /*-- deletes a list item */
    /**
     * @param $listID
     * @return bool
     */
    public function deleteList($listID)
    {
        $url = $this->url.'/lists/'.$listID;
        try
        {
            $response = $this->API_Request('Delete', $url);
            if ($response->getStatusCode() == 204)
            {
                return true;
            }
            return false;
        }
        catch (GuzzleException $e)
        {
            $e->getMessage();
        }
    }

    /*update a specific list*/
    public function updateList($listID, $dataArray = null)
    {
        if($dataArray==null)
        {
            throw new Exception("Request body paramters cannot be empty. e.g name field must have a valude");
        }
        $url = $this->url.'/lists/'.$listID;
        try {
            $response = $this->API_Request('PATCH', $url,$dataArray);
            if ($response->getStatusCode() == 200) {
                return true;
            }
            return $response->getReasonPhrase();
        }
        catch (GuzzleException $e)
        {
            $e->getMessage();
        }
    }


    /*member functions*/
    public function subscriberHash($email)
    {
        return md5(strtolower($email));
    }


    /*	Add a member to list*/
    public function addMember($listID, $data)
    {
        $url = $this->url.'/lists/'.$listID.'/members';

        try {

            $response = $this->API_Request('POST', $url,$data);
            if ($response->getStatusCode() == 200) {
                return $response->getBody()->getContents();
            }
            return $response->getStatusCode();
        }
        catch (GuzzleException $e)
        {
            $e->getMessage();
        }


    }


    public function updateMember($listID, $email, $bodyParams =array())
    {
        $subscriber_hash =$this->subscriberHash($email);
        $url = $this->url.'/lists/'.$listID.'/members/'.$subscriber_hash;

        try
        {
            $response = $this->API_Request('PATCH', $url,$bodyParams);
            if ($response->getStatusCode() == 200) {
                /*newly created member data*/
                return $response->getBody()->getContents();

            }
            return $response->getStatusCode();
        }
        catch (GuzzleException $e)
        {
            echo "ERROR...Something went wrong updating member.";
            $e->getMessage();
        }
    }

    public function DeleteMember($listID, $email)
    {

        $subscriber_hash =$this->subscriberHash($email);
        $url = $this->url.'/lists/'.$listID.'/members/'.$subscriber_hash;
        try
        {
            $response = $this->API_Request('DELETE', $url);
            if ($response->getStatusCode() == 204)
            {
                return true;
            }
            return false;
        }
        catch (Exception $e)
        {
            echo "ERROR...Something went wrong updating member.";
            $e->getMessage();
        }

    }

    public function getMembers($listID)
    {

        $url = $this->url.'/lists/'.$listID.'/members/?fields=members.email_address,members.status';
        try
        {
            $response = $this->API_Request('GET', $url);
            if ($response->getStatusCode() == 200)
            {
                return $response->getBody()->getContents();
            }
            return false;
        }
        catch (Exception $e)
        {
            echo "ERROR...Something went wrong updating member.";
            $e->getMessage();
        }
    }

    /*
     * Main function that handles all the calls
     * returns http response from the api*/
    private function API_Request($method, $url, $args= null, $toArray = true )
    {

            try
            {
                if($args==null)
                {
                    return  $this->httpClient->request($method, $url, ['auth' => ['username', $this->api_key]] );
                }
                else
                {
                    if(is_array($args) && $toArray) {
                        $args = json_encode($args);
                    }
                    return $this->httpClient->request($method, $url,
                        [
                            'auth' => ['username', $this->api_key],
                            'body'=>$args
                        ]);
                }
            }
            catch (Exception $e)
            {
                echo "ERROR...Something went wrong.\n\n\n";
                print_r($e);
                exit();
            }

    }



}