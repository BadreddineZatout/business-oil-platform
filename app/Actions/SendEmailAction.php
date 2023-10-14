<?php

namespace App\Actions;

use App\Exceptions\EmailFailedException;
use Illuminate\Support\Facades\Storage;
use Postmark\Models\PostmarkAttachment;
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

    public function handle($receiver, $data)
    {
        try {
            if ($data['attachment']) {
                $attachment = PostmarkAttachment::fromRawData(
                    Storage::disk('public')->get($data['attachment']),
                    $data['attachment']
                );
            }
            $response = $this->client->sendEmail(
                $this->sender,
                $receiver,
                $data['title'],
                $data['message'],
                attachments: isset($attachment) ? [$attachment] : null
            );

            return $response->message;
        } catch (\Throwable $th) {
            throw new EmailFailedException($th->getMessage());
        } finally {
            Storage::disk('public')->delete($data['attachment']);
        }
    }
}
