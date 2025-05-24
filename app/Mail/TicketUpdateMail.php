<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticketTitle;
    public $ticketMessage;
    public $user;
    public $ticket;

    /**
     * Create a new message instance.
     */
    public function __construct($ticketTitle, $ticketMessage, $user, $ticket)
    {
        $this->ticketTitle = $ticketTitle;
        $this->ticketMessage = $ticketMessage;
        $this->user = $user;
        $this->ticket = $ticket;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket status updated',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.update_ticket_state',
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
