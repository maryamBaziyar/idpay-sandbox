<?php

namespace App\Http\Controllers;

use App\Activity;
use App\order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\In;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class ActivityController extends Controller
{
    /*
     * show all step payment
     */
    public function show($id = 0)
    {
        $activity = [];
        $data = [];
        if ($id != 0) {
            $activity = order::with('get_Activity')->where('id', $id)->first();
        }

        $data['activity'] = $activity;

        return view('show', $data);
    }

    /*
     * get input data and insert in database and connect to idpay and create transaction
     */
    public function store(Request $request)
    {
        $params = [
            'API_KEY' => $request->values['APIKEY'],
            'sandbox' => ($request->values['sandbox']) ? 1 : 0,
            'name' => $request->values['name'],
            'phone' => $request->values['phone'],
            'mail' => $request->values['mail'],
            'amount' => $request->values['amount'],
            'reseller' => $request->values['reseller'],
        ];

        $params['order_id'] = order::insertGetId($params);
        $params['desc'] = 'توضیحات پرداخت کننده';
        $params['callback'] = 'http://127.0.0.1:8000/callback';
        $params['reseller'] = null;

        $_request['params'] = $params;
        $_request['url'] = 'POST: https://api.idpay.ir/v1.1/payment';
        $_request['header'] = [
            'Content-Type' => 'application/json',
            "X-API-KEY" => $params['API_KEY'],
            'X-SANDBOX' => $params['sandbox']
        ];

        $client = new Client();

        $res = $client->request('POST', 'https://api.idpay.ir/v1.1/payment',
            [
                'json' => $params,
                'headers' => $_request['header'],
                'http_errors' => false
            ]);

        $activity = [
            'order_id' => $params['order_id'],
            'step' => 'create',
            'request' => json_encode($_request),
            'response' => $res->getBody()
        ];
//
        Activity::insertGetId($activity);
        $data['response'] = $activity['response'];
        $data['request'] = $activity['request'];
        $data['step'] = $activity['step'];
        $data['status'] = $res->getStatusCode();


        return view('create_ajax', $data);

    }


    public function callback(Request $request)
    {

        $activity = array(
            'order_id' => $request['order_id'],
            'step' => 'redirect',
            'request' => json_encode(['url https://idpay.ir/p/ws-sandbox/' . $request['id'] . '/' . $request['order_id']]),
            'response' => ''
        );

        Activity::insertGetId($activity);


        $activity = array(
            'order_id' => $request['order_id'],
            'step' => 'return',
            'request' => '',
            'response' => json_encode($request->all())
        );
        Activity::insertGetId($activity);



        return redirect()->route('show', $request['order_id']);

    }

    public function get_status_description($status)
    {
        switch ($status) {
            case 1:
                return 'پرداخت انجام نشده است';
                break;
            case 2:
                return 'پرداخت ناموفق بوده است';
                break;
            case 3:
                return 'خطا رخ داده است';
                break;
            case 4:
                return 'بلوکه شده';
                break;
            case 5:
                return 'برگشت به پرداخت کننده';
                break;
            case 6:
                return 'برگشت خورده سیستمی';
                break;
            case 7:
                return 'انصراف از پرداخت';
                break;
            case 8:
                return 'به درگاه پرداخت منتقل شد';
                break;
            case 10:
                return 'در انتظار تایید پرداخت';
                break;
            case 100:
                return 'پرداخت تایید شده است';
                break;
            case 101:
                return 'پرداخت قبلا تایید شده است';
                break;

            case 200:
                return 'به دریافت کننده واریز شد';
                break;

        }

    }


    public function verify(Request $request)
    {

        $params = array(
            'id' => $request['id'],
            'order_id' => $request['order_id'],
        );
        $order = order::where('id', $request['order_id'])->first();

        $_request['params'] = $params;
        $_request['url'] = 'POST: https://api.idpay.ir/v1.1/payment/verify';
        $_request['header'] = [
            'Content-Type' => 'application/json',
            "X-API-KEY" => $order['API_KEY'],
            'X-SANDBOX' => $order['sandbox']
        ];

        $client = new Client();

        $res = $client->request('POST', 'https://api.idpay.ir/v1.1/payment/verify',
            [
                'json' => $params,
                'headers' => $_request['header']
            ]);


        $activity = [
            'order_id' => $request['order_id'],
            'step' => 'verify',
            'request' => json_encode($_request),
            'response' => $res->getBody()
        ];

        Activity::insertGetId($activity);
        $data['response'] = $activity['response'];
        $data['request'] = $activity['request'];
        $data['step'] = $activity['step'];
        return view('create_ajax', $data);
    }
}
