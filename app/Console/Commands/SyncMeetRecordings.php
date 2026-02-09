<?php

namespace App\Console\Commands;

use App\Models\Recording;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\GoogleDriveService;

class SyncMeetRecordings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-meet-recordings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(GoogleDriveService $drive)
    {
        $files = $drive->listMeetRecordings();

        foreach ($files->files as $file) {

            // Only process mp4 files
            if ($file->mimeType !== 'video/mp4') {
                continue;
            }

            $record = Recording::firstOrCreate(
                ['drive_file_id' => $file->id],
                [
                    'file_name' => $file->name,
                    'mime_type' => $file->mimeType ?? null,
                    'file_size' => $file->size ?? null,
                    'recorded_at' => isset($file->createdTime)
                        ? Carbon::parse($file->createdTime)
                        : null,
                ]
            );
            if (!$record->processed) {
                $localPath = $drive->downloadFile(...);
                $this->info('Downloading: ' . $file->name);
    
                $localPath = $drive->downloadFile($file->id, $file->name);
    
                $this->info('Saved at: ' . $localPath);

                $record->update([
                    'processed' => true
                ]);
            }


        }
    }

}
