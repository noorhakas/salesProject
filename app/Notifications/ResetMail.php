<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;

class ResetMail extends Notification
{
    use Queueable;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
	    $link = $this->user->active_code; 
		$user = $notifiable;
		$from_address = env('MAIL_FROM_ADDRESS');
		$app_name = env('MAIL_FROM_NAME');
        return (new MailMessage)->from($from_address,$app_name)->subject('Password Recovery Message')->view('mail.reset', compact(['link','user']));
    }

    public function toArray($notifiable)
    {
        return [];
    }
}
