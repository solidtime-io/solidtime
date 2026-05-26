<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function verifyEmailChange(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->getAuthIdentifier() !== $user->getKey()) {
            abort(403);
        }

        $email = $request->query('email');
        if (! is_string($email)) {
            abort(403);
        }

        $email = Str::lower($email);

        if ($user->pending_email !== $email) {
            abort(403);
        }

        $emailAlreadyInUse = User::query()
            ->where('email', '=', $email)
            ->where('is_placeholder', '=', false)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($emailAlreadyInUse) {
            return redirect(route('dashboard'))
                ->with('bannerStyle', 'danger')
                ->with('bannerText', __('The email address is already in use.'));
        }

        $user->email = $email;
        $user->pending_email = null;
        $user->email_verified_at = Carbon::now();
        $user->save();

        return redirect(route('dashboard'))
            ->with('bannerStyle', 'success')
            ->with('bannerText', __('Your email address has been updated successfully.'));
    }
}
