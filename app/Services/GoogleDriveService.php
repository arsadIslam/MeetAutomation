<?php
namespace App\Services;
use Google\Client;
use Google\Service\Drive;

class GoogleDriveService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/google-credentials.json'));
        $this->client->addScope(Drive::DRIVE);

        $this->service = new Drive($this->client);
    }

    public function listMeetRecordings()
    {
        return $this->service->files->listFiles([
            'q' => "name contains 'Meet' and mimeType != 'application/vnd.google-apps.folder'",
            'fields' => 'files(id, name, mimeType, size, createdTime)'
        ]);
    }
    public function downloadFile($fileId, $fileName)
    {
        $response = $this->service->files->get($fileId, [
            'alt' => 'media'
        ]);

        $content = $response->getBody()->getContents();

        $tempDirectory = storage_path('app/temp');

        if (!file_exists($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        $filePath = $tempDirectory . '/' . $fileName;

        file_put_contents($filePath, $content);

        return $filePath;
    }

}
