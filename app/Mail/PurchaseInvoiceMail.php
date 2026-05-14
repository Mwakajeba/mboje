<?php

namespace App\Mail;

use App\Models\Purchase\PurchaseInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $customMessage;
    public $subject;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseInvoice $invoice, $subject = null, $message = null)
    {
        $this->invoice = $invoice;
        $this->subject = $subject ?? "Purchase Invoice #{$invoice->invoice_number} from " . config('app.name');
        $this->customMessage = $message ?? "Please find attached purchase invoice #{$invoice->invoice_number} for your records.";
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
        // Load invoice with all necessary relationships
        $invoice = $this->invoice->load([
            'supplier',
            'items.inventoryItem',
            'items.asset',
            'branch',
            'company',
            'creator'
        ]);

        return new Content(
            view: 'emails.purchase-invoice',
            with: [
                'invoice' => $invoice,
                'customMessage' => $this->customMessage,
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

