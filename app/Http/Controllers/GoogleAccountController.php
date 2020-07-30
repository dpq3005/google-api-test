<?php

namespace App\Http\Controllers;

use App\GoogleAccount;
use App\Services\Google;
use Illuminate\Http\Request;
use Google_Service_PeopleService;

class GoogleAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the google accounts.
     */
    public function index()
    {
        return view('accounts', [
            'accounts' => auth()->user()->googleAccounts,
        ]);
    }

    /**
     * Handle the OAuth connection which leads to 
     * the creating of a new Google Account.
     */
    public function store(Request $request, Google $google)
    {
        if (! $request->has('code')) {
            return redirect($google->createAuthUrl());
        }

        $google->authenticate($request->get('code'));
        // $account = $google->service('Plus')->people->get('me');
        $account = $google->service('PeopleService')->people->get('people/me',array('personFields' => 'events,emailAddresses,names,coverPhotos,locales,addresses'));  
        auth()->user()->googleAccounts()->updateOrCreate(
            // [
            //     'google_id' => head($account->names)->value,
            // ],
            [
                'name' => head($account->emailAddresses)->value,
                'token' => $google->getAccessToken(),
            ]
        );

        return redirect()->route('google.index');
    }

    /**
     * Revoke the account's token and delete the it locally.
     */
    public function destroy(GoogleAccount $googleAccount, Google $google)
    {
        $googleAccount->calendars->each->delete();

        $googleAccount->delete();

        $google->revokeToken($googleAccount->token);

        return redirect()->back();
    }
}
