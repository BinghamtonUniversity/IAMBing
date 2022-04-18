<?php

namespace App\Mail;


use App\Models\Identity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SponsoredIdentityEntitlementExpirationReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($identityEntitlements)
    {
        $user_message['reminder']="
        You have the following identity sponsored entitlements expiring soon:
        <ul>
            {{#identity_entitlements}}
              <li>
                <p>{{identity.first_name}} {{identity.last_name}} ({{identity.default_email}})</p>
                <p>Identity Entitlement: {{entitlement.name}}</p>
                <p>Expiration Date: {{expiration_date}}</p>
              </li>  
            {{/identity_entitlements}}
        </ul>
        To take action, please follow the link: {{link}}
        ";

        $m = new \Mustache_Engine;
        $this->content = $m->render($user_message['reminder'],[
            'identity_entitlements'=>$identityEntitlements,
            'link'=>url("/")
        ]);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return
        $this->view('emails.rawData')
            ->with(['content'=>$this->content])
            ->subject('IAMBing Identity Entitlement Expiration Reminder');
    }
}
