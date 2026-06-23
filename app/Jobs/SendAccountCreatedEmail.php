<?php

namespace App\Jobs;

use App\Mail\AccountCreatedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAccountCreatedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    protected $user;
    protected ?string $userName;
    protected string $locale;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\User $user
     */
    public function __construct(User $user, ?string $userName = null, ?string $locale = null)
    {
        $this->user = $user;
        $this->userName = $userName;
        $this->locale = $locale ?? app()->getLocale();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            Mail::to($this->user->email)->send(
                new AccountCreatedMail($this->user, $this->userName, $this->locale)
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send AccountCreatedMail from job', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

