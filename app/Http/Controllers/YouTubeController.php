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
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        // Load saved token
        $tokenPath = storage_path('app/youtube_token.json');
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);

        // Refresh token if expired
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        $youtube = new YouTube($client);

        // Path to your downloaded video
        $videoPath = storage_path('app/temp/Meeting tes33.mp4'); // change this

        $snippet = new VideoSnippet();
        $snippet->setTitle("Test Upload from Laravel 🔥");
        $snippet->setDescription("Uploaded automatically via Laravel script.");
        $snippet->setTags(["laravel", "automation"]);
        $snippet->setCategoryId("22"); // 22 = People & Blogs

        $status = new VideoStatus();
        $status->privacyStatus = "private"; // public / unlisted / private

        $video = new Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        $chunkSizeBytes = 1 * 1024 * 1024;

        $client->setDefer(true);

        $request = $youtube->videos->insert("snippet,status", $video);

        $media = new \Google\Http\MediaFileUpload(
            $client,
            $request,
            'video/*',
            null,
            true,
            $chunkSizeBytes
        );

        $media->setFileSize(filesize($videoPath));

        $handle = fopen($videoPath, "rb");

        while (!$status && !feof($handle)) {
            $chunk = fread($handle, $chunkSizeBytes);
            $status = $media->nextChunk($chunk);
        }

        fclose($handle);

        return "Video uploaded successfully 🚀";
    }

}
