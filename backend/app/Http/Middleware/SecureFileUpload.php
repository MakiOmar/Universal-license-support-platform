<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureFileUpload
{
    /**
     * Allowed MIME types for file uploads.
     */
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'text/log',
    ];

    /**
     * Maximum file size in bytes (10MB).
     */
    protected int $maxFileSize = 10485760;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Check file size
            if ($file->getSize() > $this->maxFileSize) {
                return response()->json([
                    'message' => 'File size exceeds maximum allowed size of 10MB.',
                ], 413);
            }

            // Check MIME type
            $mimeType = $file->getMimeType();
            if (! in_array($mimeType, $this->allowedMimeTypes)) {
                return response()->json([
                    'message' => 'File type not allowed.',
                    'allowed_types' => $this->allowedMimeTypes,
                ], 415);
            }

            // Check file extension
            $extension = strtolower($file->getClientOriginalExtension());
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'log'];
            if (! in_array($extension, $allowedExtensions)) {
                return response()->json([
                    'message' => 'File extension not allowed.',
                ], 415);
            }

            // Scan for malicious content (basic check)
            $content = file_get_contents($file->getRealPath());
            if ($this->containsMaliciousContent($content)) {
                return response()->json([
                    'message' => 'File contains potentially malicious content.',
                ], 400);
            }
        }

        return $next($request);
    }

    /**
     * Check if file content contains potentially malicious patterns.
     */
    protected function containsMaliciousContent(string $content): bool
    {
        $maliciousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/onerror=/i',
            '/onload=/i',
            '/eval\(/i',
            '/base64_decode/i',
            '/exec\(/i',
            '/system\(/i',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}

