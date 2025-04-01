<?php

use Illuminate\Support\Facades\Broadcast;

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

// Budget channels
Broadcast::channel('private-budgets', function ($user) {
    return $user !== null; // Solo usuarios autenticados
});

// Budget Request channels
Broadcast::channel('private-budget-requests', function ($user) {
    return $user !== null; // Solo usuarios autenticados
});

// Transaction channels
Broadcast::channel('private-transactions', function ($user) {
    return $user !== null; // Solo usuarios autenticados
});

// Invoice channels
Broadcast::channel('private-invoices', function ($user) {
    return $user !== null; // Solo usuarios autenticados
});
