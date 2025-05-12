<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VentaRealizadaCliente extends Mailable
{
    use Queueable, SerializesModels;

    public $sale;
    public $client;
    public $products;

    public function __construct($client, $sale, $products)
    {
        $this->client = $client;
        $this->sale = is_array($sale) ? (object) $sale : $sale;
        $this->products = $products;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Venta Realizada Cliente',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.venta_realizada_cliente', // Aquí debes crear esta vista
            with: [
                'sale' => $this->sale,
                'client' => $this->client,
                'products' => $this->products,
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
