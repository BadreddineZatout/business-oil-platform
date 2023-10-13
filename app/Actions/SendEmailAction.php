<?php

namespace App\Actions;

use App\Exceptions\EmailFailedException;
use Postmark\PostmarkClient;

class sendEmailAction
{
    /**
     * postmark client instance
     *
     * @var PostmarkClient
     */
    protected $client;

    /**
     * sender email address
     *
     * @var string
     */
    protected $sender;

    public function __construct()
    {
        $this->client = new PostmarkClient(env('POSTMARK_TOKEN'));
        $this->sender = env('POSTMARK_SENDER');
    }

    public function handle($receiver, $title, $message)
    {
        try {
            $response = $this->client->sendEmail(
                $this->sender,
                $receiver,
                $title,
                $message
            );

            return $response->message;
        } catch (\Throwable $th) {
            throw new EmailFailedException($th->getMessage());
        }
    }
}
