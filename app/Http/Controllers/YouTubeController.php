<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\YouTube;
use Illuminate\Http\Request;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;

class YouTubeController extends Controller
{
    public function redirectToGoogle()
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->addScope(YouTube::YOUTUBE_UPLOAD);
        $client->setAccessType('offline'); // Important for refresh token
        $client->setPrompt('consent'); // Force refresh token

        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        $token = $client->fetchAccessTokenWithAuthCode($request->code);

        // Store this somewhere safe (file/db for now)
        file_put_contents(storage_path('app/youtube_token.json'), json_encode($token));

        return "YouTube connected successfully 🔥";
    }

    public function upload()
    {
        $videoPath = storage_path('app/temp/Meeting tes33.mp4');

        \App\Jobs\UploadYouTubeVideo::dispatch($videoPath);

        return "Upload job dispatched 🚀";
    }


}
