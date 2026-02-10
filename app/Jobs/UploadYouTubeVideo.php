<?php

namespace App\Jobs;

use Exception;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;
use Google\Http\MediaFileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadYouTubeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $videoPath;

    public $tries = 3;          // retry 3 times
    public $backoff = 60;       // wait 60s before retry

    public function __construct($videoPath)
    {
        $this->videoPath = $videoPath;
    }

    public function handle()
    {
        Log::info("Starting YouTube upload", ['file' => $this->videoPath]);

        if (!file_exists($this->videoPath)) {
            Log::error("File not found");
            return;
        }

        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        $tokenPath = storage_path('app/youtube_token.json');
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        $youtube = new YouTube($client);

        $fileSize = filesize($this->videoPath);
        $fileName = pathinfo($this->videoPath, PATHINFO_FILENAME);

        $snippet = new VideoSnippet();
        $snippet->setTitle($fileName);
        $snippet->setDescription("Meeting recording uploaded automatically.\n\nUploaded at: " . now());
        $snippet->setCategoryId("22");

        $status = new VideoStatus();
        $status->setPrivacyStatus("public");

        $video = new Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        try {

            // 🔥 AUTO SWITCH
            if ($fileSize < 100 * 1024 * 1024) {

                Log::info("Using multipart upload");

                $response = $youtube->videos->insert(
                    "snippet,status",
                    $video,
                    [
                        'data' => file_get_contents($this->videoPath),
                        'mimeType' => 'video/mp4',
                        'uploadType' => 'multipart'
                    ]
                );

                Log::info("Upload completed", ['video_id' => $response->id]);

            } else {

                Log::info("Using resumable upload");

                $chunkSizeBytes = 10 * 1024 * 1024;

                $client->setDefer(true);

                $request = $youtube->videos->insert("snippet,status", $video);

                $media = new MediaFileUpload(
                    $client,
                    $request,
                    'video/mp4',
                    null,
                    true,
                    $chunkSizeBytes
                );

                $media->setFileSize($fileSize);

                $statusUpload = false;
                $handle = fopen($this->videoPath, "rb");

                while (!$statusUpload && !feof($handle)) {
                    $chunk = fread($handle, $chunkSizeBytes);
                    $statusUpload = $media->nextChunk($chunk);
                }

                fclose($handle);
                $client->setDefer(false);

                if ($statusUpload) {
                    Log::info("Resumable upload completed", [
                        'video_id' => $statusUpload['id']
                    ]);
                } else {
                    Log::error("Upload did not finalize properly.");
                    throw new Exception("Upload did not finalize properly.");
                    
                }
            }

        } catch (Exception $e) {
            Log::error("Upload failed", [
                'error' => $e->getMessage()
            ]);
            throw $e; // triggers retry
        }
    }
}
