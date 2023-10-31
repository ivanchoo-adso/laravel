<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class WhatsAppController extends Controller
{
    public function sendWhatsAppMessage($to, $message)
{
    $baseURL = "https://wa.me/";
    $encodedMessage = urlencode($message);
    $url = $baseURL . $to . "?text=" . $encodedMessage;

    $this->sendWhatsAppMessage('313', '¡Hola desde Laravel!');

    return redirect($url);



}

}
