<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;

class TwilioController extends Controller
{
    public function callComes(Request $request): Response
    {
        $sipUser = 'MVP-RSVP-KALFA';

        $twiml = '
<Response>
    <Dial>
        <Sip>sip:'.$sipUser.'@mvp-rsvp-kalfa.sip.twilio.com</Sip>
    </Dial>
</Response>';

        return response($twiml)
            ->header('Content-Type', 'text/xml');
    }
}
