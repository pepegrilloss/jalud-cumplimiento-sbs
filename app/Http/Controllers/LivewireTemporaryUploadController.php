<?php

namespace App\Http\Controllers;

use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

class LivewireTemporaryUploadController extends FileUploadController
{
    public function handle()
    {
        abort_unless(request()->hasValidSignature(false), 401);

        $disk = FileUploadConfiguration::disk();

        $filePaths = $this->validateAndStore(request('files'), $disk);

        return ['paths' => $filePaths];
    }
}
