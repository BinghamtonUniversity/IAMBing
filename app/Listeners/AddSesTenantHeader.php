<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Auth; // or your tenant resolver

class AddSesTenantHeader
{
    public function handle(MessageSending $event): void
    {
        $tenant = config('mail.ses_tenant');
        if ($tenant !== null && $tenant !== '') {
            $event->message->getHeaders()->addTextHeader('X-SES-TENANT', $tenant);
        }
    }    
}