<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\HandlesAdminInvitations;
use Karabin\Fabriq\Models\Invitation;
use Karabin\Fabriq\Models\User;

class UserInvitationController extends AdminController
{
    use HandlesAdminInvitations;

    public function store(Request $request, User $user): RedirectResponse
    {
        $this->createAndSendInvitation($user, $request->user()->id);

        return to_route('admin.users.index')->with('status', 'Aktivering skickad.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Invitation::query()
            ->where('user_id', $user->id)
            ->delete();

        return to_route('admin.users.index')->with('status', 'Inbjudan togs bort.');
    }
}
