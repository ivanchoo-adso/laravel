<?php

namespace App\Http\Controllers;

use App\Events\Webhook;
use App\Models\Message;
use Carbon\Carbon;
use App\Libraries\Whatsapp;
use Exception;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{

    public function index(){

        try{

            $messages = DB::table('messages as m')
            ->select('m.*') // Especifica las columnas que necesitas, o usa 'm.*' para seleccionar todas
            ->whereIn('m.id', function($query){
                $query->selectRaw('MAX(id)')
                      ->from('messages as m2')
                      ->groupBy('m2.wa_id');
            })
            ->orderByDesc('m.id')
            ->get();

            return response()->json([
                'success'=> true,
                'data'=> $messages,
            ],200);

        }catch(Exception $e){
            return response()->json([
                'success'=> false,
                'error'=>$e->getMessage(),
            ],500);

        }
    }

    public function show($waId, Request $request){

        try{

            $messages = DB::table('messages as m')
            ->where('wa_id',$waId)
            ->orderBy('created_at')
            ->get();
            return response()->json([
                'success'=> true,
                'data'=> $messages,
            ],200);

        }catch(Exception $e){
            return response()->json([
                'success'=> false,
                'error'=>$e->getMessage(),
            ],500);
        }

    }



    public function sendMessages(){
        try{
          /*  $token=env('WHATSAPP_API_TOKEN');
            $phoneId=env('WHATSAPP_API_PHONE_ID');
            $version='v17.0';
            $payload =[
                'messaging_product' => 'whatsapp',
                    'to'=> '573143424686',
                    'type'=> 'template',
                    "template"=> [
                        "name"=> "hello_world",
                        "language"=>[
                            "code"=> "en_US"
                        ]
                    ]

            ];
          $message= Http::withToken($token)->post('https://graph.facebook.com/' . $version . '/' . $phoneId . '/message' , $payload)->throw()->json();
  */

            $wp = new Whatsapp();
            $message = $wp->sendText('573143424686','QUE HACE');

            return response()->json([
                'success'=> true,
                'data'=> $message,
            ],200);
        }
        catch(Exception $e){

            return response()->json([
                'success'=> false,
                'error'=>$e->getMessage(),
            ],500);
        }}

        public function verifywebhook(Request $request)
        {
            try{
                $verifytoken='ivantabares123!';
                $query = $request->query();

                $mode = $query	['hub_mode'];
                $token = $query ['hub_verify_token'];
                $challenge = $query['hub_challenge'];


                if($mode && $token){
                    if($mode ==='subscribe' && $token == $verifytoken){

                        return response($challenge,200)->header('Content-Type', 'text/plain');
                    }

                }
                throw new Exception('Invalid request');
            }
            catch(Exception $e){
                return response()->json([
                    'success'=> false,
                    'error'=>$e->getMessage(),
                ],500);
            }
        }
        public function processWebhook(Request $request):JsonResponse
        {
            try{
                $bodyContent = json_decode($request->getContent(),true);
                $body='';

                $value = $bodyContent['entry'][0]['changes'][0]['value'];
                if (!empty($value['statuses'])){
                    $status=$value['statuses'][0]['status'];
                    $wam = Message::where('wam_id', $value['statuses'][0]['id'])->first();

                    if(!empty($wam->id)){
                        $wam->status=$status;
                        $wam->save();
                        Webhook::dispatch($wam, true);
                    }


                }elseif(!empty($value['messages'])){
                    $exist=Message::where('wam_id',$value['messages'][0]['id'])->first();

                    if(empty($exist->id)){
                        $mediaSupported = ['audio', 'document', 'image', 'video', 'sticker'];
                        if($value['messages'][0]['type'] == 'text'){
                        $message = $this->_saveMessage(
                            $value['messages'][0]['text']['body'],
                            'text',
                            $value['messages'][0]['from'],
                            $value['messages'][0]['id'],
                            $value['messages'][0]['timestamp']
                        );
                        Webhook::dispatch($message, false);
                    } elseif (in_array($value['messages'][0]['type'], $mediaSupported)) {
                        $mediaType = $value['messages'][0]['type'];
                        $mediaId = $value['messages'][0][$mediaType]['id'];
                        $wp = new Whatsapp();
                        $file = $wp->downloadMedia($mediaId);
                        $caption = null;

                        if (! empty($value['messages'][0][$mediaType]['caption'])) {
                            $caption = $value['messages'][0][$mediaType]['caption'];
                        }

                        if (! is_null($file)) {
                            $message = $this->_saveMessage(
                                'http://localhost:8000/storage/'.$file,
                                $mediaType,
                                $value['messages'][0]['from'],
                                $value['messages'][0]['id'],
                                $value['messages'][0]['timestamp'],
                                $caption
                            );
                        }
                    }else{
                        $type = $value['messages'][0]['type'];
                        if(!empty($value['messages'][0][$type])){
                            $message = $this->_saveMessage(
                                "($type): \n_" . serialize($value['messages'][0][$type]) . "_",
                                'other',
                                $value['messages'][0]['from'],
                                $value['messages'][0]['id'],
                                $value['messages'][0]['timestamp']
                            );
                        }
                        Webhook::dispatch($message, false);
                    }
                }
            }
                return response()->json([
                    'success'=> true,
                    'data'=> $bodyContent,
                ],200);
            }
            catch(Exception $e){
                return response()->json([
                    'success'=> false,
                    'error'=>$e->getMessage(),
                ],500);
            }
        }

        private function _saveMessage($message, $messageType, $waId, $wamId, $timestamp = null, $caption = null, $data='')
        {
            $wam = new Message();
            $wam->body = $message;
            $wam->outgoing = false;
            $wam->type = $messageType;
            $wam->wa_id = $waId;
            $wam->wam_id = $wamId;
            $wam->status = 'sent';
            $wam->caption = $caption;
            $wam->data = $data;


            $wam->save();

            return $wam;
        }
        public function store(Request $request){

            try{
                $request->validate([
                    'wa_id' => ['required', 'max:20'],
                    'body' => ['required', 'string'],
                ]);
                $input = $request->all();
                $wp = new Whatsapp();
                $response = $wp->sendText($input['wa_id'],$input['body']);

                $message= new Message();
                $message->wa_id = $input['wa_id'];
                $message->wam_id = $response["messages"][0]["id"];
                $message->type = 'text';
                $message->outgoing = true;
                $message->body = $input['body'];
                $message->status = 'sent';
                $message->caption = '';
                $message->data = '';
                $message->save();

                return response()->json([
                    'success'=> true,
                    'data'=> $message->only(['id', 'wa_id', 'wam_id', 'body', 'outgoing', 'status', 'created_at']),
                ],200);
            }catch(Exception $e){
                return response()->json([
                    'success'=> false,
                    'error'=>$e->getMessage(),
                ],500);

            }

        }
}
