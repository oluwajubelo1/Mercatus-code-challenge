<?php

namespace App\Mail;

use App\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SubscriberJoined extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $subscriber;
    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }
    public function build()
    {
        return $this->markdown('emails.subscribers.joined-waitlist')
            ->subject('Someone joined the waitlist')
            ->replyTo('imoleolu2012@gmail.com');
    }
}
