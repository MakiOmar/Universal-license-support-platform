<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mailable $mailable,
        public string $to
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->to)->send($this->mailable);
            Log::info('Email sent successfully', [
                'to' => $this->to,
                'mailable' => get_class($this->mailable),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'to' => $this->to,
                'mailable' => get_class($this->mailable),
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email job failed after retries', [
            'to' => $this->to,
            'mailable' => get_class($this->mailable),
            'error' => $exception->getMessage(),
        ]);
    }
}
