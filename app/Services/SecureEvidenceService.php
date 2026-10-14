<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Response;
use PDO;

/**
 * Secure Evidence Upload & Authorization Service
 * Enforces strict MIME inspection, magic byte verification, payload sanitization,
 * outside-web-root randomized storage, and authorized streaming delivery.
 */
class SecureEvidenceService
{
    public const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024; // 5 MB

    public const ALLOWED_MIME_TYPES = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
    ];

    private string $storageBaseDir;

    public function __construct(?string $storageBaseDir = null)
    {
        $this->storageBaseDir = $storageBaseDir ?: dirname(__DIR__, 2) . '/storage/uploads/complaints';
        if (!is_dir($this->storageBaseDir)) {
            @mkdir($this->storageBaseDir, 0755, true);
        }
    }

    /**
     * Get target absolute directory
     */
    public function getStorageDir(): string
    {
        return $this->storageBaseDir;
    }

    /**
     * Validate uploaded file array ($_FILES structure or mock)
     */
    public function validateUpload(array $file): array
    {
        // 1. Basic error code check
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => match ($error) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum upload limit.',
                    UPLOAD_ERR_PARTIAL                         => 'File upload was only partially completed.',
                    UPLOAD_ERR_NO_FILE                         => 'No file was uploaded.',
                    default                                    => 'File upload failed with system error code: ' . $error,
                },
            ];
        }

        // 2. File size verification
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            return ['valid' => false, 'error' => 'Uploaded file is empty (0 bytes).'];
        }
        if ($size > self::MAX_FILE_SIZE_BYTES) {
            return [
                'valid' => false,
                'error' => 'File size (' . round($size / (1024 * 1024), 2) . ' MB) exceeds allowed limit of 5 MB.',
            ];
        }

        $tmpPath = $file['tmp_name'] ?? '';
        if (!file_exists($tmpPath) || !is_readable($tmpPath)) {
            return ['valid' => false, 'error' => 'Uploaded temporary file is inaccessible.'];
        }

        // 3. Strict MIME type detection via finfo (never trusting client Content-Type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $tmpPath) ?: '';
        finfo_close($finfo);

        if (!array_key_exists($detectedMime, self::ALLOWED_MIME_TYPES)) {
            return [
                'valid' => false,
                'error' => 'Invalid file format (' . ($detectedMime ?: 'unknown') . '). Only JPEG, PNG, WEBP, and PDF documents are allowed.',
            ];
        }

        // 4. Deep Magic Byte Verification
        $magicBytesValid = $this->verifyMagicBytes($tmpPath, $detectedMime);
        if (!$magicBytesValid) {
            return [
                'valid' => false,
                'error' => 'File signature mismatch. The file content does not match its claimed MIME type.',
            ];
        }

        // 5. Anti-Malware / Script Injection Scan
        if ($this->containsExecutablePayload($tmpPath)) {
            return [
                'valid' => false,
                'error' => 'File rejected: executable script tags or PHP code signatures detected.',
            ];
        }

        $canonicalExt = self::ALLOWED_MIME_TYPES[$detectedMime];

        return [
            'valid'         => true,
            'error'         => null,
            'mime_type'     => $detectedMime,
            'canonical_ext' => $canonicalExt,
            'size_bytes'    => $size,
            'tmp_path'      => $tmpPath,
        ];
    }

    /**
     * Store validated file outside public root with randomized name and SHA-256 hash
     */
    public function storeUpload(
        array $file,
        int $complaintId,
        int $userId,
        ?int $updateId = null,
        ?string $caption = null
    ): array {
        $validation = $this->validateUpload($file);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['error']);
        }

        $originalName = basename($file['name'] ?? 'attachment');
        // Clean original name for safe database display
        $originalName = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '', $originalName);

        // Generate high-entropy cryptographically random filename
        $storedFilename = bin2hex(random_bytes(16)) . '.' . $validation['canonical_ext'];
        $destinationPath = $this->storageBaseDir . '/' . $storedFilename;

        // Calculate cryptographic SHA-256 checksum
        $fileHash = hash_file('sha256', $validation['tmp_path']);

        // Move or copy file
        if (is_uploaded_file($validation['tmp_path'])) {
            if (!move_uploaded_file($validation['tmp_path'], $destinationPath)) {
                throw new \RuntimeException('Failed to move uploaded evidence file to secure storage directory.');
            }
        } else {
            // For CLI testing or simulated requests
            if (!copy($validation['tmp_path'], $destinationPath)) {
                throw new \RuntimeException('Failed to copy evidence file to secure storage directory.');
            }
        }

        // Ensure safe permissions
        @chmod($destinationPath, 0644);

        $relativeStoragePath = 'storage/uploads/complaints/' . $storedFilename;

        // Save evidence record in database if DB connection is active
        $evidenceId = 0;
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO `complaint_evidence` (
                    `complaint_id`, `complaint_update_id`, `original_filename`, `stored_filename`,
                    `file_path`, `file_size_bytes`, `mime_type`, `file_hash`, `uploaded_by`, `caption`
                ) VALUES (
                    :complaint_id, :complaint_update_id, :orig_name, :stored_name,
                    :file_path, :file_size, :mime_type, :file_hash, :uploaded_by, :caption
                )
            ");
            $stmt->execute([
                ':complaint_id'        => $complaintId,
                ':complaint_update_id' => $updateId,
                ':orig_name'           => $originalName,
                ':stored_name'         => $storedFilename,
                ':file_path'           => $relativeStoragePath,
                ':file_size'           => $validation['size_bytes'],
                ':mime_type'           => $validation['mime_type'],
                ':file_hash'           => $fileHash,
                ':uploaded_by'         => $userId,
                ':caption'             => $caption,
            ]);
            $evidenceId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            // In unit tests without live DB, evidenceId may be 0
        }

        return [
            'id'                => $evidenceId,
            'complaint_id'      => $complaintId,
            'original_filename' => $originalName,
            'stored_filename'   => $storedFilename,
            'absolute_path'     => $destinationPath,
            'relative_path'     => $relativeStoragePath,
            'file_size_bytes'   => $validation['size_bytes'],
            'mime_type'         => $validation['mime_type'],
            'file_hash'         => $fileHash,
            'uploaded_by'       => $userId,
        ];
    }

    /**
     * Check if a user is authorized to download or view this evidence
     */
    public function canUserAccessEvidence(int $evidenceId, array $currentUser): bool
    {
        if (empty($currentUser['id'])) {
            return false;
        }

        $role = $currentUser['role'] ?? 'supervisor';
        if ($role === 'admin') {
            return true;
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT ce.uploaded_by, c.reported_by, c.assigned_to_user_id
                FROM `complaint_evidence` ce
                JOIN `complaints` c ON ce.complaint_id = c.id
                WHERE ce.id = :id AND c.deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([':id' => $evidenceId]);
            $meta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$meta) {
                return false;
            }

            $currentUserId = (int)$currentUser['id'];

            // Access permitted if user is the uploader, complaint reporter, or current assignee
            if ((int)$meta['uploaded_by'] === $currentUserId ||
                (int)$meta['reported_by'] === $currentUserId ||
                (int)$meta['assigned_to_user_id'] === $currentUserId) {
                return true;
            }
        } catch (\Throwable $e) {
            // DB error
        }

        return false;
    }

    /**
     * Serve evidence file after authorization
     */
    public function serveEvidence(int $evidenceId, array $currentUser, bool $asAttachment = false): void
    {
        if (!$this->canUserAccessEvidence($evidenceId, $currentUser)) {
            http_response_code(403);
            echo "Access Denied: You do not have permission to view or download this complaint evidence.";
            exit;
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM `complaint_evidence` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => $evidenceId]);
            $evidence = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$evidence) {
                http_response_code(404);
                echo "Evidence not found.";
                exit;
            }

            $filePath = $this->storageBaseDir . '/' . $evidence['stored_filename'];
            if (!file_exists($filePath)) {
                http_response_code(404);
                echo "File not found on storage server.";
                exit;
            }

            Response::file($filePath, $evidence['mime_type'], $evidence['original_filename'], $asAttachment);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo "Error streaming evidence.";
            exit;
        }
    }

    /**
     * Deep header signature inspection (Magic Bytes)
     */
    private function verifyMagicBytes(string $filePath, string $mimeType): bool
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 16);
        fclose($handle);

        if ($header === false || strlen($header) < 4) {
            return false;
        }

        return match ($mimeType) {
            'image/jpeg'      => str_starts_with($header, "\xFF\xD8\xFF"),
            'image/png'       => str_starts_with($header, "\x89PNG\r\n\x1a\n"),
            'application/pdf' => str_starts_with($header, "%PDF"),
            'image/webp'      => (str_starts_with($header, 'RIFF') && str_contains(substr($header, 8, 8), 'WEBP')),
            default           => false,
        };
    }

    /**
     * Scan file for embedded PHP tags or malicious executable signatures
     */
    private function containsExecutablePayload(string $filePath): bool
    {
        // Read up to first 256KB or entire file if smaller
        $content = file_get_contents($filePath, false, null, 0, 262144);
        if ($content === false) {
            return false;
        }

        $lower = strtolower($content);

        // Check for common webshell & script injection indicators
        $suspiciousPatterns = [
            '<?php',
            '<?=',
            '<script',
            'language="php"',
            'eval(',
            'base64_decode(',
            'system(',
            'shell_exec(',
            'passthru(',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
