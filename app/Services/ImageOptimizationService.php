<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Throwable;

class ImageOptimizationService
{
    /**
     * The ImageManager instance is created lazily – only when image processing
     * is actually needed (i.e. when optimizeAndStore / optimizeMultiple are called).
     * This ensures injecting this service into controllers does NOT throw an
     * extension-missing exception when the admin simply views a form page.
     */
    protected ?ImageManager $manager;

    public function __construct(?ImageManager $manager = null)
    {
        // Store an explicitly-injected manager (e.g. from tests), or null so the
        // real driver is resolved on first use.
        $this->manager = $manager;
    }

    /**
     * Resolve (and cache) the ImageManager, selecting the best available driver.
     */
    protected function getManager(): ImageManager
    {
        if ($this->manager !== null) {
            return $this->manager;
        }

        $driverName = config('image_optimization.driver', 'gd');

        // Try the configured driver first, then fall back to the other.
        // We catch DriverException from Intervention instead of relying on
        // extension_loaded(), because the built-in PHP web server can sometimes
        // report extensions as unavailable even when they are loaded.
        try {
            if ($driverName === 'imagick') {
                $driver = new ImagickDriver();
            } else {
                $driver = new GdDriver();
            }
        } catch (\Throwable $primary) {
            // Primary driver failed — try the other one.
            try {
                if ($driverName === 'imagick') {
                    $driver = new GdDriver();
                } else {
                    $driver = new ImagickDriver();
                }
            } catch (\Throwable $fallback) {
                throw new \RuntimeException(
                    'No suitable image processing driver could be loaded. '
                    . 'Primary error: ' . $primary->getMessage()
                );
            }
        }

        $this->manager = new ImageManager($driver);

        return $this->manager;
    }

    /**
     * Validate, resize proportionally, convert to WebP, and store the uploaded image.
     *
     * @param  UploadedFile|string  $file  Uploaded file instance or file path string.
     * @param  string  $directory  Target directory (e.g. 'products', 'categories').
     * @param  string  $preset     Preset name from config ('product', 'category', 'banner', etc.).
     * @param  int|null  $quality  WebP quality (1-100), or null to use config default.
     * @param  string|null  $disk  Storage disk name, or null to use config default.
     * @param  string  $fieldKey   Field name for validation error reporting.
     * @return string Relative stored path (e.g. 'products/40chars.webp').
     *
     * @throws ValidationException
     */
    public function optimizeAndStore(
        UploadedFile|string $file,
        string $directory,
        string $preset = 'default',
        ?int $quality = null,
        ?string $disk = null,
        string $fieldKey = 'image'
    ): string {
        $diskName = $disk ?? config('image_optimization.disk', 'public');
        $quality = $quality ?? config('image_optimization.quality', 80);
        $format = strtolower(config('image_optimization.format', 'webp'));

        // 1. Validate if it's an UploadedFile
        if ($file instanceof UploadedFile) {
            $this->validateUploadedFile($file, $fieldKey);
            $sourcePath = $file->getRealPath() ?: $file->getPathname();
        } else {
            $sourcePath = $file;
            if (! file_exists($sourcePath)) {
                throw ValidationException::withMessages([
                    $fieldKey => 'The specified source image file does not exist.',
                ]);
            }
        }

        // 2. Read and decode the image securely
        try {
            $image = $this->getManager()->read($sourcePath);
        } catch (\RuntimeException $e) {
            // RuntimeException means a driver/server configuration problem (e.g. GD not enabled
            // in the web server), NOT a bad uploaded file. Re-throw so it surfaces as a 500
            // with a clear message rather than a silent user-facing validation error.
            Log::critical('ImageOptimizationService: Image driver not available.', [
                'error'     => $e->getMessage(),
                'directory' => $directory,
                'preset'    => $preset,
            ]);

            throw $e;
        } catch (Throwable $e) {
            Log::error('ImageOptimizationService: Failed to read/decode image file.', [
                'error'     => $e->getMessage(),
                'directory' => $directory,
                'preset' => $preset,
            ]);

            throw ValidationException::withMessages([
                $fieldKey => 'The uploaded file is not a valid or supported image.',
            ]);
        }

        // 3. Proportional resizing without distortion, cropping, or upscaling small images
        $presetLimits = config("image_optimization.presets.{$preset}")
            ?? config('image_optimization.presets.default', ['max_width' => 1200, 'max_height' => 1200]);

        $maxWidth = (int) ($presetLimits['max_width'] ?? 1200);
        $maxHeight = (int) ($presetLimits['max_height'] ?? 1200);

        if ($maxWidth > 0 && $maxHeight > 0) {
            $image->scaleDown(width: $maxWidth, height: $maxHeight);
        }

        // 4. Encode to target format (WebP by default)
        try {
            $encoded = match ($format) {
                'webp' => $image->toWebp($quality),
                'jpeg', 'jpg' => $image->toJpeg($quality),
                'png' => $image->toPng(),
                default => $image->toWebp($quality),
            };
        } catch (Throwable $e) {
            Log::error('ImageOptimizationService: Failed to encode image to WebP.', [
                'error' => $e->getMessage(),
                'format' => $format,
                'quality' => $quality,
            ]);

            throw ValidationException::withMessages([
                $fieldKey => 'An error occurred while optimizing the image format.',
            ]);
        }

        // 5. Generate secure, unique filename and sanitize destination directory
        $cleanDirectory = trim(str_replace(['..', '\\'], ['', '/'], $directory), '/');
        $uniqueFilename = Str::random(40) . '.' . $format;
        $targetPath = empty($cleanDirectory) ? $uniqueFilename : "{$cleanDirectory}/{$uniqueFilename}";

        // 6. Save image into Laravel storage disk
        try {
            $saved = Storage::disk($diskName)->put($targetPath, (string) $encoded);

            if (! $saved) {
                throw new \RuntimeException("Storage disk [{$diskName}] failed to write file.");
            }
        } catch (Throwable $e) {
            Log::error('ImageOptimizationService: Failed to write optimized image to storage disk.', [
                'error' => $e->getMessage(),
                'disk' => $diskName,
                'target_path' => $targetPath,
            ]);

            // Clean up if a partial file was created
            if (Storage::disk($diskName)->exists($targetPath)) {
                Storage::disk($diskName)->delete($targetPath);
            }

            throw ValidationException::withMessages([
                $fieldKey => 'Failed to save the optimized image to storage.',
            ]);
        }

        return $targetPath;
    }

    /**
     * Optimize and store multiple uploaded files.
     *
     * @param  array<int, UploadedFile>  $files
     * @param  string  $directory
     * @param  string  $preset
     * @param  int|null  $quality
     * @param  string|null  $disk
     * @param  string  $fieldKey
     * @return array<int, string> List of relative paths stored.
     */
    public function optimizeMultiple(
        array $files,
        string $directory,
        string $preset = 'default',
        ?int $quality = null,
        ?string $disk = null,
        string $fieldKey = 'images'
    ): array {
        $storedPaths = [];

        try {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile && ! $file->isValid()) {
                    continue;
                }

                $storedPaths[] = $this->optimizeAndStore(
                    $file,
                    $directory,
                    $preset,
                    $quality,
                    $disk,
                    $fieldKey
                );
            }
        } catch (Throwable $e) {
            // Roll back any images stored during this batch if one fails
            $diskName = $disk ?? config('image_optimization.disk', 'public');
            foreach ($storedPaths as $path) {
                if (Storage::disk($diskName)->exists($path)) {
                    Storage::disk($diskName)->delete($path);
                }
            }

            throw $e;
        }

        return $storedPaths;
    }

    /**
     * Delete an existing image file safely only if it exists and is no longer in use.
     *
     * @param  string|null  $path  Disk-relative image path.
     * @param  callable|null  $inUseChecker  Optional callback ($path): bool returning true if still referenced elsewhere.
     * @param  string|null  $disk  Storage disk name.
     * @return bool True if deleted, false otherwise.
     */
    public function deleteIfUnused(?string $path, ?callable $inUseChecker = null, ?string $disk = null): bool
    {
        $trimmedPath = trim((string) $path);

        if ($trimmedPath === '') {
            return false;
        }

        $diskName = $disk ?? config('image_optimization.disk', 'public');

        // Check if file exists on disk
        if (! Storage::disk($diskName)->exists($trimmedPath)) {
            return false;
        }

        // Check if file is still in use elsewhere
        if (is_callable($inUseChecker)) {
            try {
                if ($inUseChecker($trimmedPath) === true) {
                    return false;
                }
            } catch (Throwable $e) {
                Log::warning('ImageOptimizationService: inUseChecker threw an exception; skipping delete.', [
                    'path' => $trimmedPath,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        }

        return Storage::disk($diskName)->delete($trimmedPath);
    }

    /**
     * Validate an UploadedFile instance against configuration limits and MIME types.
     */
    protected function validateUploadedFile(UploadedFile $file, string $fieldKey): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                $fieldKey => 'The uploaded file is not valid or was corrupted during transfer.',
            ]);
        }

        $maxKilobytes = (int) config('image_optimization.max_file_size', 10240);
        $fileSizeKilobytes = $file->getSize() / 1024;

        if ($fileSizeKilobytes > $maxKilobytes) {
            throw ValidationException::withMessages([
                $fieldKey => "The uploaded image exceeds the maximum permitted size of {$maxKilobytes} KB.",
            ]);
        }

        $allowedMimes = config('image_optimization.allowed_mimes', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/avif',
            'image/jpg',
        ]);

        $mimeType = $file->getMimeType();

        if (! in_array($mimeType, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                $fieldKey => 'The image format is not supported. Please upload a JPG, JPEG, PNG, WebP, or AVIF file.',
            ]);
        }

        // GD decodes the full source bitmap before scaleDown() can run. A small
        // compressed upload can therefore exhaust PHP memory when its pixel
        // dimensions are enormous. Reject it before Intervention/GD allocates
        // the bitmap, leaving room for Laravel and the encoder under 128 MB.
        $dimensions = @getimagesize($file->getRealPath() ?: $file->getPathname());
        if ($dimensions !== false) {
            [$width, $height] = $dimensions;
            $memoryLimit = $this->memoryLimitBytes(ini_get('memory_limit'));
            $memoryReserve = 32 * 1024 * 1024;
            $estimatedBytesPerPixel = 8; // GD source + resize buffers, conservatively.

            if ($memoryLimit > 0 && $width > 0 && $height > 0
                && ($width * $height * $estimatedBytesPerPixel) > ($memoryLimit - $memoryReserve)) {
                throw ValidationException::withMessages([
                    $fieldKey => 'The image dimensions are too large to process. Please resize it and try again.',
                ]);
            }
        }
    }

    private function memoryLimitBytes(string|false $value): int
    {
        if ($value === false || trim($value) === '' || trim($value) === '-1') {
            return 0;
        }

        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) ($number * match ($unit) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        });
    }
}
