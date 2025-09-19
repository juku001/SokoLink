<?php


Route::prefix("v1")->group(function () {

    include __DIR__ . "/api/v1/location.php";
    include __DIR__ . '/api/v1/auth.php';
    include __DIR__ . '/api/v1/contact.php';
    include __DIR__ . '/api/v1/academy.php';

    include __DIR__ . '/api/v1/marketplace.php';
    include __DIR__ . '/api/v1/transactions.php';
    include __DIR__ . '/api/v1/payments.php';
    include __DIR__ . '/api/v1/payouts.php';
    include __DIR__ . '/api/v1/admin.php';
    include __DIR__ . '/api/v1/reports.php';
});