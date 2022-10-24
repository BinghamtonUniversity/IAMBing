<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use App\Models\Identity;
use App\Models\GroupMember;
use App\Exceptions\FailedRecalculateException;
use App\Models\GroupActionQueue;

use Throwable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 2;

    protected $body;
    protected $subject;
    protected $to;
    protected $cc;

    public function __construct($config) {
        $this->body = isset($config['body'])?$config['body']:'';
        $this->subject = isset($config['subject'])?$config['subject']:'';
        $this->to = isset($config['to'])?$config['to']:[];
        $this->cc = isset($config['cc'])?$config['cc']:[];
        $this->bcc = isset($config['bcc'])?$config['bcc']:[];
    }

    public function handle() {
        $body = $this->body;
        $subject = $this->subject;
        $to = $this->to;
        $cc = $this->cc;
        $bcc = $this->bcc;

        Mail::raw($body, function($message) use ($subject,$to,$cc,$bcc) {
            $message->subject($subject);
            foreach($to as $recipient) {
                if (is_string($recipient) && $this->send_email_check($recipient)) {
                    $message->to($recipient);
                } else if (isset($recipient['name']) && isset($recipient['email']) && $this->send_email_check($recipient['email'])) {
                    $message->to($recipient['email'],$recipient['name']);
                } else if (isset($recipient['email']) && $this->send_email_check($recipient['email'])) {
                    $message->to($recipient['email']);
                }
            }
            foreach($cc as $recipient) {
                if (is_string($recipient) && $this->send_email_check($recipient)) {
                    $message->to($recipient);
                } else if (isset($recipient['name']) && isset($recipient['email']) && $this->send_email_check($recipient['email'])) {
                    $message->to($recipient['email'],$recipient['name']);
                } else if (isset($recipient['email']) && $this->send_email_check($recipient['email'])) {
                    $message->to($recipient['email']);
                }
            }
            foreach($bcc as $recipient) {
                if (is_string($recipient) && $this->send_email_check($recipient)) {
                    $message->to($recipient);
                } else if (isset($recipient['name']) && isset($recipient['email']) && $this->send_email_check($recipient['email'])) {
                    $message->to($recipient['email'],$recipient['name']);
                } else if (isset($recipient['email']) && $this->send_email_check($recipient['email'])) {
                    $message->to($recipient['email']);
                }
            }
        });
    }

    private function send_email_check($email_address) {
        /* Send email if MAIL_LIMIT_SEND is false (Not limiting emails) */
        if (config('mail.limit_send') === false) {
            return true;
        }
        /* Send email if MAIL_LIMIT_SEND is true, and MAIL_LIMIT_ALLOW contains user's email address */
        if (config('mail.limit_send') === true && in_array($email_address,config('mail.limit_allow'))) {
            return true;
        }
        /* Otherwise don't send email */
        return false;
    }

    public function failed(Throwable $exception) {
    }   
}
