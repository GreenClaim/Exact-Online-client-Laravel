<?php

namespace Yource\ExactOnlineClient\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use stdClass;
use Yource\ExactOnlineClient\ExactOnlineAuthorization;

class ExactOnlineConnectController extends Controller
{
    private $exactOnlineAuthorization;

    public function __construct(ExactOnlineAuthorization $exactOnlineAuthorization)
    {
        $this->exactOnlineAuthorization = $exactOnlineAuthorization;
    }

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
        return redirect()->to($this->exactOnlineAuthorization->getAuthUrl());
    }

    /**
     * Saves the authorisation and refresh tokens
     */
    public function callback(Request $request)
    {
        $credentials = $this->exactOnlineAuthorization->getCredentials();

        if (empty($credentials)) {
            $credentials = new stdClass;
        }

        $credentials->authorisationCode = $request->get('code');

        $this->exactOnlineAuthorization->setCredentials($credentials);

        abort_if(empty($credentials), 500, 'Credentials are empty');

        return view('exact-online-client::connected');
    }
}
