<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Payment;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channel for payment status updates
// Only the user who owns the payment can listen to this channel
Broadcast::channel('payment.{reference}', function ($user, $reference) {
    $payment = Payment::where('reference', $reference)->first();

    if (!$payment) {
        return false;
    }

    return (int) $user->id === (int) $payment->user_id;
});
