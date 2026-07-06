<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\MlmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MlmUser $user,
        public Invoice $invoice
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invoice #' . $this->invoice->invoice_number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice', with: [
            'user' => $this->user,
            'invoice' => $this->invoice,
        ]);
    }

    public function attachments(): array
    {
        if ($this->invoice->pdf_path) {
            return [
                Attachment::fromStorageDisk('public', $this->invoice->pdf_path)
                    ->as($this->invoice->invoice_number . '.pdf')
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }
}
