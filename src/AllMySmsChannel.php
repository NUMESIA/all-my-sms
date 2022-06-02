<?php

namespace NotificationChannels\AllMySms;

use Illuminate\Notifications\Notification;
use NotificationChannels\AllMySms\Exceptions\CouldNotSendNotification;

class AllMySmsChannel
{
    /** @var \NotificationChannels\AllMySms\AllMySms */
    protected $client;

    /**
     * The sender name the message should sent from.
     *
     * @var string|null
     */
    public $sender;

    /**
     * The phone number the message should always send to.
     *
     * @var string|null
     */
    protected $to;

    /**
     * Create a new AllMySmsChannel instance.
     *
     * @param  \NotificationChannels\AllMySms\AllMySms  $client
     * @param  string|null  $sender
     * @param  string|null  $to
     */
    public function __construct(AllMySms $client, ?string $sender = null, ?string $to = null)
    {
        $this->client = $client;
        $this->sender = $sender;
        $this->to = $to;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     * @return void
     *
     * @throws \NotificationChannels\AllMySms\Exceptions\CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $to = $notifiable->routeNotificationFor('AllMySms', $notification)) {
            return;
        }

        if (! empty($this->to)) {
            $to = $this->to;
        }

        $message = $notification->toAllMySms($notifiable);

        if (is_string($message)) {
            $message = new AllMySmsMessage($message);
        }

        if (env('ALL_MY_SMS_SANDBOX', false) === true) {
            return $this->client->logSms($to, $message->toArray(), $this->sender);
        }

        $response = $this->client->sendSms($to, $message->toArray(), $this->sender);

        if ($response->getStatusCode() != 200 && $response->getStatusCode() != 201) {
            throw CouldNotSendNotification::serviceRespondedWithAnError($response);
        }
    }
}
