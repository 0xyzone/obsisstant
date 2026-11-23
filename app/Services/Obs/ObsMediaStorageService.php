<?php

namespace App\Services\Obs;

use App\Models\ObsStudio;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ObsMediaStorageService
{
    public function resolveConfiguredDirectory(ObsStudio $studio, ?User $user = null): string
    {
        $owner = $user ?? $studio->user;
        $path = trim((string) ($studio->media_storage_path ?: ($owner?->media_storage_path ?: config('obs.media_storage_path', '~/Pictures/Obsisstant'))));

        return $path !== '' ? $path : '~/Pictures/Obsisstant';
    }

    public function expandLocalPath(string $configuredPath): ?string
    {
        $trimmed = trim($configuredPath);
        if ($trimmed === '') {
            return null;
        }

        if ($trimmed === '~' || str_starts_with($trimmed, '~/') || str_starts_with($trimmed, '~\\')) {
            $home = trim((string) (getenv('HOME') ?: ($_SERVER['HOME'] ?? '')));
            if ($home === '') {
                return null;
            }

            $suffix = substr($trimmed, 1);

            return rtrim($home, DIRECTORY_SEPARATOR).$suffix;
        }

        return $trimmed;
    }

    /**
     * Validate and stage an uploaded image, writing locally if possible when obs-agent is offline,
     * or issuing a one-time signed download token so obs-agent saves into native desktop/Mac storage.
     *
     * @return array{
     *     token: string,
     *     filename: string,
     *     mime: string,
     *     target_dir: string,
     *     storage_path: string,
     *     direct_saved_path: ?string
     * }
     */
    public function prepareUpload(UploadedFile $file, ObsStudio $studio, ?User $user = null): array
    {
        $originalBase = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slugBase = Str::slug($originalBase) ?: 'obs-image';
        $ext = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'png'));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $token = Str::random(40);
        $filename = "{$slugBase}-".Str::lower(Str::random(6)).".{$ext}";
        $targetDir = $this->resolveConfiguredDirectory($studio, $user);
        $mime = (string) ($file->getMimeType() ?: 'image/png');

        $stagingRelPath = "obs-media-staging/{$token}.{$ext}";
        Storage::disk('local')->put($stagingRelPath, $file->get());

        $directSavedPath = null;
        if (! $studio->isOnline()) {
            $directSavedPath = $this->tryDirectLocalWrite($targetDir, $filename, $file->get());
        }

        ObsCommandService::previewCacheStore()->put("obs.media.upload.{$token}", [
            'studio_id' => $studio->id,
            'storage_path' => $stagingRelPath,
            'filename' => $filename,
            'target_dir' => $targetDir,
            'mime' => $mime,
        ], now()->addMinutes(3));

        return [
            'token' => $token,
            'filename' => $filename,
            'mime' => $mime,
            'target_dir' => $targetDir,
            'storage_path' => $stagingRelPath,
            'direct_saved_path' => $directSavedPath,
        ];
    }

    private function tryDirectLocalWrite(string $targetDir, string $filename, string $bytes): ?string
    {
        $expanded = $this->expandLocalPath($targetDir);
        if ($expanded === null) {
            return null;
        }

        try {
            if (! File::isDirectory($expanded)) {
                File::makeDirectory($expanded, 0755, true);
            }

            if (! is_writable($expanded)) {
                return null;
            }

            $destination = rtrim($expanded, '/\\').DIRECTORY_SEPARATOR.basename($filename);
            if (File::put($destination, $bytes) !== false) {
                return $destination;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
