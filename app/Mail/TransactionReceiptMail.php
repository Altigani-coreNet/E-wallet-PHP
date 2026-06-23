<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionReceiptMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public $transaction;
    public $personalMessage;
    public $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Transaction $transaction, $personalMessage = null, $pdfPath = null)
    {
        $this->transaction = $transaction;
        $this->personalMessage = $personalMessage;
        $this->pdfPath = $pdfPath;
        $this->applyMailLocale();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.transaction_receipt_subject') . ' - ' . $this->transaction->transaction_id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.transaction-receipt',
            with: [
                'transaction' => $this->transaction,
                'personalMessage' => $this->personalMessage,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        
        if ($this->pdfPath && file_exists($this->pdfPath)) {
            // Determine file extension and MIME type
            $extension = pathinfo($this->pdfPath, PATHINFO_EXTENSION);
            $filename = 'Transaction_Invoice_' . $this->transaction->transaction_id;
            
            if ($extension === 'html') {
                $attachments[] = Attachment::fromPath($this->pdfPath)
                    ->as($filename . '.html')
                    ->withMime('text/html');
            } else {
                $attachments[] = Attachment::fromPath($this->pdfPath)
                    ->as($filename . '.pdf')
                    ->withMime('application/pdf');
            }
        }
        
        return $attachments;
    }
}
