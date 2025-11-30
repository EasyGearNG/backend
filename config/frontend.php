<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Frontend URL
    |--------------------------------------------------------------------------
    |
    | This value is the URL of your frontend application. This is used for
    | CORS configuration, payment callbacks, and other frontend interactions.
    |
    */
    
    'url' => env('FRONTEND_URL', 'http://localhost:3000'),
    
    /*
    |--------------------------------------------------------------------------
    | Payment Callback Path
    |--------------------------------------------------------------------------
    |
    | The path on the frontend where payment callbacks should be handled.
    |
    */
    
    'payment_callback_path' => env('FRONTEND_PAYMENT_CALLBACK_PATH', '/payment/callback'),
    
];
