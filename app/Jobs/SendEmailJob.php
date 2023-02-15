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
use Illuminate\Queue\Middleware\RateLimited;

use Throwable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 5;

    protected $body;
    protected $subject;
    protected $to;
    protected $cc;
    protected $bcc;

    public function __construct($config) {
        $this->body = isset($config['body'])?$config['body']:'';
        $this->subject = isset($config['subject'])?$config['subject']:'';
        $this->to = isset($config['to'])?$config['to']:[];
        $this->cc = isset($config['cc'])?$config['cc']:[];
        $this->bcc = isset($config['bcc'])?$config['bcc']:[];
    }

    // Commenting this out because it leads to constant retries and then maxes out
    // before executing the job. Preferring to just add manual sleeps to the individual
    // send jobs to slow them down.
    // public function middleware() {
    //     return [new RateLimited('send_email_job')];
    // }

    public function handle() {
        $body = $this->body;
        $subject = $this->subject;
        $to = $this->to;
        $cc = $this->cc;
        $bcc = $this->bcc;

        // Go to sleep for a random amount of time between 5 and 10 seconds 
        // to try to slow down email sending jobs 
        // and avoid rate limiting by mail server.
        $delay_seconds = random_int(5,10);
        sleep($delay_seconds);

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
                    $message->cc($recipient);
                } else if (isset($recipient['name']) && isset($recipient['email']) && $this->send_email_check($recipient['email'])) {
                    $message->cc($recipient['email'],$recipient['name']);
                } else if (isset($recipient['email']) && $this->send_email_check($recipient['email'])) {
                    $message->cc($recipient['email']);
                }
            }
            foreach($bcc as $recipient) {
                if (is_string($recipient) && $this->send_email_check($recipient)) {
                    $message->bcc($recipient);
                } else if (isset($recipient['name']) && isset($recipient['email']) && $this->send_email_check($recipient['email'])) {
                    $message->bcc($recipient['email'],$recipient['name']);
                } else if (isset($recipient['email']) && $this->send_email_check($recipient['email'])) {
                    $message->bcc($recipient['email']);
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

    public function tags() {
        return ['send_email'];
    }

    public function failed(Throwable $exception) {
    }   
}
