<?php

namespace App\Http\Controllers;

use App\Payment;
use Illuminate\Http\Request;
use App\Repositories\PaypalRepository;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function makePayment(Request $request)
    {
        $task = $request->all();
        $paypal = new PaypalRepository();
        $paypal->prepare($request, $task);
    }

    public function savePayment(Request $request)
    {
        $method = $request->method();
        $paypal = new PaypalRepository();
        if($method=='GET'){
            $payerId = $request->PayerID;
            $paymentId = $request->paymentId;
            if(!$paymentId ||  !$payerId){
                return redirect('/')->with('notice',["class"=>"error","message"=>"Payment was interrupted. Please try again later"]);
            }
            $paypal->request = $request;
            $response = $paypal->charge($payerId,$paymentId);
            $task_id = $response->transactions[0]->item_list->items[0]->sku;
            $usd_amount = $response->transactions[0]->amount->total;
            $currency = $response->transactions[0]->amount->currency;
            $task = Task::find($task_id);
            $task->paid =1;
            $task->update();
            return redirect('/')->with('notice',["class"=>"success","message"=>"Service payment succeeful"]);
        }
    }


    public function cancelPayment(Request $request)
    {
        return redirect('/')->with('notice', ["class" => "error", "message" => "Payment was interrupted. Please try again later"]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Payment $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Payment $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Payment $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
