<?php

namespace App\Jobs;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $filePath,
        public int $userId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $file = Storage::disk('local')->path($this->filePath);

        if (! file_exists($file)) {
            Log::error("Import file not found: {$file}");
            return;
        }

        $handle = fopen($file, 'r');
        if (! $handle) {
            Log::error("Could not open import file: {$file}");
            return;
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            Log::error('Could not read CSV headers');
            return;
        }

        // Normalize headers (trim, lowercase, replace spaces with underscores)
        $headers = array_map(function ($header) {
            return strtolower(trim(str_replace(' ', '_', $header)));
        }, $headers);

        // Map common column names to our field names
        $fieldMapping = [
            'email' => ['email', 'e-mail', 'email_address'],
            'first_name' => ['first_name', 'firstname', 'first', 'fname'],
            'last_name' => ['last_name', 'lastname', 'last', 'lname', 'surname'],
            'company' => ['company', 'company_name', 'organization', 'org'],
            'phone' => ['phone', 'phone_number', 'telephone', 'tel', 'mobile'],
            'status' => ['status', 'account_status', 'customer_status'],
        ];

        // Find column indices
        $columnIndices = [];
        foreach ($fieldMapping as $field => $possibleNames) {
            foreach ($possibleNames as $name) {
                $index = array_search($name, $headers, true);
                if ($index !== false) {
                    $columnIndices[$field] = $index;
                    break;
                }
            }
        }

        if (! isset($columnIndices['email'])) {
            fclose($handle);
            Log::error('Email column not found in CSV');
            return;
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        // Process rows
        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) < count($headers)) {
                $errors[] = "Row {$rowNumber}: Insufficient columns";
                $skipped++;
                continue;
            }

            $email = trim($row[$columnIndices['email']] ?? '');

            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNumber}: Invalid or missing email";
                $skipped++;
                continue;
            }

            // Check if customer already exists
            if (Customer::where('email', $email)->exists()) {
                $errors[] = "Row {$rowNumber}: Customer with email {$email} already exists";
                $skipped++;
                continue;
            }

            $data = [
                'email' => $email,
                'first_name' => trim($row[$columnIndices['first_name']] ?? ''),
                'last_name' => trim($row[$columnIndices['last_name']] ?? ''),
                'company' => trim($row[$columnIndices['company']] ?? ''),
                'phone' => trim($row[$columnIndices['phone']] ?? ''),
                'status' => trim($row[$columnIndices['status']] ?? 'active'),
            ];

            // Validate status
            if (! in_array($data['status'], ['active', 'inactive', 'suspended'], true)) {
                $data['status'] = 'active';
            }

            // Remove empty strings
            $data = array_filter($data, function ($value) {
                return $value !== '';
            });

            try {
                Customer::create($data);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$rowNumber}: {$e->getMessage()}";
                $skipped++;
                Log::error("Failed to import customer row {$rowNumber}: {$e->getMessage()}");
            }
        }

        fclose($handle);

        // Clean up file
        Storage::disk('local')->delete($this->filePath);

        // Log results
        Log::info("Customer import completed", [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => count($errors),
            'user_id' => $this->userId,
        ]);

        // Store results in cache for user to retrieve
        $resultKey = "customer_import_result_{$this->userId}_" . time();
        cache()->put($resultKey, [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'completed_at' => now()->toIso8601String(),
        ], now()->addHours(24));

        // Track result key for status checking
        $keys = cache()->get("customer_import_keys_{$this->userId}", []);
        $keys[] = $resultKey;
        // Keep only last 10 keys
        $keys = array_slice($keys, -10);
        cache()->put("customer_import_keys_{$this->userId}", $keys, now()->addHours(24));

        // Send email notification to admin user
        $user = \App\Models\User::find($this->userId);
        if ($user && $user->email) {
            \App\Jobs\SendEmailJob::dispatch(
                new \App\Mail\ImportCompletedMail($imported, $skipped, $errors, 'customers'),
                $user->email
            );
        }
    }
}
