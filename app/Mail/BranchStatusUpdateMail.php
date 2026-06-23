<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\Branch;
use App\Models\Merchant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public $branch;
    public $merchant;
    public $oldStatus;
    public $newStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(Branch $branch, Merchant $merchant, $oldStatus, $newStatus)
    {
        $this->branch = $branch;
        $this->merchant = $merchant;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->applyMailLocale();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $statusKey = 'emails.status_' . ($this->newStatus ?? 'pending');
        $statusText = __($statusKey);

        return new Envelope(
            subject: __('emails.branch_status_subject', [
                'branch' => $this->branch->name,
                'status' => $statusText,
            ]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');

        return new Content(
            view: 'emails.branch-status-update',
            with: [
                'branch' => $this->branch,
                'merchant' => $this->merchant,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'branchesUrl' => $frontend . '/merchant/branches',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
