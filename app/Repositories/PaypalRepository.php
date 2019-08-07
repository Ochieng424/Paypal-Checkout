<?php
/**
 * Created by PhpStorm.
 * User: CollinsMeroka
 * Date: 2017/06/20
 * Time: 12:38 AM
 */

namespace App\Repositories;
use App\Task;
use App\Payout;
use Auth;
use App\User;
use Illuminate\Http\Request;
use App\Assign;

class PaypalRepository
{
    public $request;
    protected $user;

    //sandbox credentials
    protected $client_id = "AVTXdApyXb6ApwQ9piiJvVaNmFftuo5yES6a2l81Aa2DzNqV6hDscxIxvYfg5NoA2svFwz-zZbjd0TSY";
    protected $secret = "EAb7nLslKrhFZqm0ebOc04zsifJ4qQgxBZ-FF7mxKlLEi7tUaLprOhqg-D4rEIeZXkZDgBR1g5SGr4iB";
    protected $endpoint = "https://api.paypal.com";

    protected $return_url;
    protected  $cancel_url;
    public function __construct()
    {
        $this->return_url =  action('PaymentController@savePayment');
        $this->cancel_url =  action('PaymentController@cancelPayment');
    }

    public function prepare(Request $request,$task){
        $this->request = $request;
//        $payWriter = $task->payWriter;
        $payWriter = $request->amount;
        $shipping = 3.00;
        $sum = $shipping + $payWriter;
        $abbrev = 'USD';
        $payWriter = number_format($payWriter,2);
        $data = '{
             "intent":"sale",
             "redirect_urls":{
                "return_url":"'.$this->return_url.'",
                "cancel_url":"'.$this->cancel_url.'"
             },
            "payer":{
                 "payment_method":"paypal"
            },
            "transactions":[
                {
             "amount":{
             "total":"'.$sum.'",
             "currency":"'.$abbrev.'",
             "details": {
                    "subtotal": "'.$payWriter.'",
                    "tax": "0.00",
                    "shipping": "'.$shipping.'"
                }
             },
            "description":"'.$request->name.'",
            "item_list": {
                "items":[
                    {
                        "quantity":"1",
                        "name":"'.$request->name.'",
                        "price":"'.$payWriter.'",
                        "sku":"'.$payWriter.'",
                        "currency":"'.$abbrev.'"
                    }
                ]
            }
          }
         ]
    }';
        $data = stripslashes($data);
       // dd($data);

        $header = array(
            'Content-Type:application/json',
            'Authorization: Bearer '.$this->getAccessToken(),
        );
        $url = $this->endpoint."/v1/payments/payment";
        $response = $this->getCurl($url,$data,"POST",$header);
        foreach($response->links as $link){
            if($link->rel=='approval_url'){
                ?>
                <p style="color: green">Please wait...</p>
                <meta http-equiv="refresh" content="0;url=<?php echo $link->href; ?>">
                <?php
                die();
            }
        }
    }
    public function payWriter(User $user,$amount,$payment_for,$subject){
        $profile = $user->profile;
        $this->return_url =  action('ClientController@tipSuccess');
        $this->cancel_url =  action('ClientController@cancelTip');
//        $user = $user->find(1);
        $data = '{
    "sender_batch_header": {
        "email_subject": "'.$subject.'"
      },
      "items": [
        {
          "recipient_type": "EMAIL",
          "amount": {
            "value": '.$amount.',
            "currency": "USD"
          },
          "receiver": "'.$user->email.'",
          "note": "Order Payments",
          "sender_item_id": "'.$user->id.'"
        }
      ]
    }';
        $data = stripslashes($data);
        $header = array(
            'Content-Type:application/json',
            'Authorization: Bearer '.$this->getAccessToken(),
        );
        $url = $this->endpoint."/v1/payments/payouts?sync_mode=true";
        $response = (object)$this->getCurl($url,$data,"POST",$header)->items[0];

        $user->payments()->create([
            'payer_id'=>Auth::User()->id,
            'transaction_reference'=>$response->transaction_id,
            'amount'=>$response->payout_item->amount->value,
            'state'=>$response->transaction_status,
            'method'=>'paypal',
            'payment_for'=>$payment_for,
        ]);
        return $response->transaction_status;


    }
    public function tipWriter(Assign $assign,$amount,$currency, Request $request){
        $this->request = $request;
        $this->return_url =  action('ClientController@tipSuccess');
        $this->cancel_url =  action('ClientController@cancelTip');
        $order = $assign->order;
        $writer = $assign->user;
        $data = '{
             "intent":"sale",
             "redirect_urls":{
                "return_url":"'.$this->return_url.'",
                "cancel_url":"'.$this->cancel_url.'"
             },
            "payer":{
                 "payment_method":"paypal"
            },
            "transactions":[
                {
             "amount":{
             "total":"'.$amount.'",
             "currency":"'.$currency.'",
             "details": {
                    "subtotal": "'.$amount.'",
                    "tax": "0.00",
                    "shipping": "0.00"
                }
             },
            "description":"Tip writer#'.$writer->id.' For job well Done",
            "item_list": {
                "items":[
                    {
                        "quantity":"1",
                        "name":"Tip for Writer#'.$writer->id.'",
                        "price":"'.$amount.'",
                        "sku":"'.$assign->id.'",
                        "currency":"'.$currency.'"
                    }
                ]
            }
          }
         ]
    }';
        $data = stripslashes($data);
        $header = array(
            'Content-Type:application/json',
            'Authorization: Bearer '.$this->getAccessToken(),
        );
        $url = $this->endpoint."/v1/payments/payment";
        $response = $this->getCurl($url,$data,"POST",$header);
        foreach($response->links as $link){
            if($link->rel=='approval_url'){
                ?>
                <p style="color: green">Redirecting. Please wait...</p>
                <meta http-equiv="refresh" content="0;url=<?php echo $link->href; ?>">
                <?php
                die();
            }
        }
    }
    public function charge($payerID,$paymentID){
        $header = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->getAccessToken()
        );
        $data = '{ "payer_id" : "'.$payerID.'" }';
        $url = $this->endpoint.'/v1/payments/payment/'.$paymentID.'/execute/';
        // $url = 'https://api.paypal.com/v1/payments/payment/'.$paymentID.'/execute/';
        $response = $this->getCurl($url,$data,"POST",$header);
        return $response;
    }
    public function getAccessToken(){
        $request = $this->request;
        if($request->session()->has('access_token')){
            
            $access_token = $request->session()->get('access_token');
           // dd($access_token);
        }else{
          //$url = "https://$this->client_id:$this->secret@api.sandbox.paypal.com/v1/oauth2/token?grant_type=client_credentials";
        $url = "https://$this->client_id:$this->secret@api.paypal.com/v1/oauth2/token?grant_type=client_credentials";
            $creds = $this->getCurl($url);
            $access_token = $creds->access_token;
            $request->session()->put(['access_token'=>$access_token]);
        }
        return $access_token;



    }
    protected function getCurl($url,$data=null, $method="POST", $header=null){
        if(!$header){
            $header = array(
                'Accept: application/json',
                'Accept-Language: en_US',
            );
        }
//        var_dump($url);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        $content = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $json_response = null;
        if($status==200 || $status==201){
            $json_response = json_decode($content);
        }else{
            dd($content,$status);
        }
        return $json_response;
    }
//    protected function getCurl($url,$data=null, $method="POST", $header=null){
//        if(!$header){
//            $header = array(
//                'Accept: application/json',
//                'Accept-Language: en_US',
//            );
//        }
//        //download cert from https://www.paypal-knowledge.com/infocenter/index?page=content&widgetview=true&id=FAQ1516&viewlocale=en_US
//        $ch = curl_init();
//curl_setopt($ch, CURLOPT_URL, $url);
//curl_setopt($ch, CURLOPT_VERBOSE, 1);
//
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
//curl_setopt($ch, CURLOPT_SSLCERT, getcwd() . "\api.paypal.com_SHA-1_12132016.pem");
// curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_POST, 1);
//
//
//
////$requeststring has refund specific fields
//
//curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//
//// Get response from the server.
//$content = curl_exec($ch);
//$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        $json_response = null;
//        if($status==200 || $status==201){
//            $json_response = json_decode($content);
//        }else{
//            dd($header,$data,$url,$content,$status);
//        }
//        return $json_response;
//
//
//    }
}