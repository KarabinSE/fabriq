<?php

namespace Karabin\Fabriq\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\Mail;
use Karabin\Fabriq\Mail\AccountInvitation;
use Karabin\Fabriq\Models\Invitation;
use Karabin\Fabriq\Models\User;

trait HandlesAdminInvitations
{
    protected function createAndSendInvitation(User $user, int $invitedBy): void
    {
        Invitation::query()
            ->where('user_id', $user->id)
            ->delete();

        $invitation = $user->createInvitation($invitedBy);
        $invitation->load('invitedBy', 'user');

        Mail::to($user->email)
            ->queue(new AccountInvitation($invitation));
    }
}
