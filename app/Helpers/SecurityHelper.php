<?php

namespace App\Helpers;

class SecurityHelper
{
    /**
     * Sanitize string input to prevent XSS attacks
     */
    public static function sanitizeString(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }
        
        // Remove HTML tags and encode special characters
        return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize array of strings
     */
    public static function sanitizeArray(array $input): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return self::sanitizeString($value);
            }
            if (is_array($value)) {
                return self::sanitizeArray($value);
            }
            return $value;
        }, $input);
    }

    /**
     * Validate and sanitize file uploads
     */
    public static function validateFileUpload($file, array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], int $maxSize = 5242880): array
    {
        $errors = [];

        if (!$file || !$file->isValid()) {
            $errors[] = 'Invalid file upload';
            return ['valid' => false, 'errors' => $errors];
        }

        // Check file size (default 5MB)
        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size of ' . ($maxSize / 1024 / 1024) . 'MB';
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes);
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        $allowedMimeTypes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
        ];

        $mimeValid = false;
        foreach ($allowedMimeTypes as $mime => $extensions) {
            if ($mimeType === $mime && in_array($extension, $extensions)) {
                $mimeValid = true;
                break;
            }
        }

        if (!$mimeValid) {
            $errors[] = 'File MIME type does not match extension';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Generate secure random token
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Sanitize SQL LIKE input (for raw queries only - prefer Eloquent)
     */
    public static function sanitizeLikeInput(string $input): string
    {
        // Escape special LIKE characters
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $input);
    }

    /**
     * Check if request is from allowed origin (for CORS)
     */
    public static function isAllowedOrigin(string $origin, array $allowedOrigins = []): bool
    {
        if (empty($allowedOrigins)) {
            $allowedOrigins = config('cors.allowed_origins', []);
        }

        return in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins);
    }

    /**
     * Mask sensitive data for logging
     */
    public static function maskSensitiveData(array $data, array $keysToMask = ['password', 'token', 'api_key', 'secret']): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $keysToMask)) {
                $data[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                $data[$key] = self::maskSensitiveData($value, $keysToMask);
            }
        }

        return $data;
    }
}
