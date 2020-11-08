<?php

namespace Yource\ExactOnlineClient\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Yource\ExactOnlineClient\ExactOnlineAuthorization;

class ExactOnlineConnectController extends Controller
{
    /**
     * Connect Exact Online app
     */
    public function connect()
    {
        return view('exact-online-client::connect');
    }

    /**
     * Sends an oAuth request to the Exact Online App to get tokens
     */
    public function authorize()
    {
        $authUrl = (new ExactOnlineAuthorization())->getAuthUrl();
        return Redirect::to($authUrl);
    }

    /**
     * Saves the authorisation and refresh tokens
     */
    public function callback(Request $request)
    {
        $authorization = (new ExactOnlineAuthorization());

        $credentials = '{}';
        if (Storage::exists($authorization->getCredentialFilePath())) {
            $credentials = Storage::get(
                $authorization->getCredentialFilePath()
            );
        }

        $credentials = (object) json_decode($credentials, false);
        $credentials->authorisationCode = $request->get('code');

        Storage::put($authorization->getCredentialFilePath(), json_encode($credentials));

        return view('exact-online-client::connected', ['connection' => $authorization]);
    }
}
