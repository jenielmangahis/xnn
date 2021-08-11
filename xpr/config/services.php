<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'payquicker' => [
        'client-id' => env('PAYQUICKER_CLIENT_ID'),
        'client-secret' => env('PAYQUICKER_SECRET'),
        'funding-account-public-id' => env('PAYQUICKER_FUNDING_ACCOUNT_PUBLIC_ID'),
        'tenant-login-uri' => env('PAYQUICKER_TENANT_LOGIN_URI')
    ],

    'hyperwallet' => [
        'username' => env('HYPERWALLET_USERNAME'),
        'password' => env('HYPERWALLET_PASSWORD'),
        'program_token' => env('HYPERWALLET_PROGRAM_TOKEN'),
        'server' => env('HYPERWALLET_SERVER', 'https://api.sandbox.hyperwallet.com'),
        'member_portal_url' => env('HYPERWALLET_MEMBER_PORTAL_URL')
    ],

    'ipayout' => [
        'merchant_guid' => env('IPAYOUT_MERCHANT_GUID'),
        'merchant_password' => env('IPAYOUT_MERCHANT_PASSWORD'),
        'api_url' => env('IPAYOUT_API_URL', 'https://testewallet.com/eWalletWS/ws_JsonAdapter.aspx'),
        'member_portal_url' => env('IPAYOUT_MEMBER_PORTAL_URL'),
    ],
    'nacha' => [
        'bank_rt' => env('NACHA_BANK_RT'),
        'file_id' => env('NACHA_FILE_ID'),
        'originating_bank' => env('NACHA_ORIGINATING_BANK'),
        'company_name' => env('NACHA_COMPANY_NAME'),
        'service_class_code' => env('NACHA_SERVICE_CLASS_CODE'),
        'company_id' => env('NACHA_COMPANY_ID'),
        'sec_code' => env('NACHA_SEC_CODE'),
        'description' => env('NACHA_DESCRIPTION'),
    ]

];
