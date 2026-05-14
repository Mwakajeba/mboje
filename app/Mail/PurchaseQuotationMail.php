<?php

namespace App\Mail;

use App\Models\Purchase\PurchaseQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseQuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $quotation;
    public $message;
    public $subject;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseQuotation $quotation, $subject = null, $message = null)
    {
        $this->quotation = $quotation;
        $this->subject = $subject ?? "Purchase Quotation #{$quotation->reference} from " . config('app.name');
        $this->message = $message ?? "Please find attached purchase quotation #{$quotation->reference} for your review and pricing.";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-quotation',
            with: [
                'quotation' => $this->quotation,
                'message' => $this->message,
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
        return [
            // You can add PDF attachment here later
        ];
    }
}
