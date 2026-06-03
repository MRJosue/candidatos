<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'cv_import_model' => env('GEMINI_CV_IMPORT_MODEL', 'gemini-2.5-flash'),
        'cv_import_fallback_models' => array_filter(array_map('trim', explode(',', env('GEMINI_CV_IMPORT_FALLBACK_MODELS', 'gemini-2.5-flash-lite')))),
    ],

    'cv_import' => [
        'pdf_extract_timeout' => (int) env('CV_PDF_EXTRACT_TIMEOUT', 60),
        'pdf_ocr_enabled' => env('CV_PDF_OCR_ENABLED', true),
        'pdf_ocr_dpi' => (int) env('CV_PDF_OCR_DPI', 180),
        'pdf_ocr_language' => env('CV_PDF_OCR_LANGUAGE', 'spa+eng'),
        'binaries' => [
            'antiword' => env('ANTIWORD_BINARY'),
            'pdftotext' => env('PDFTOTEXT_BINARY'),
            'pdftoppm' => env('PDFTOPPM_BINARY'),
            'soffice' => env('SOFFICE_BINARY'),
            'tesseract' => env('TESSERACT_BINARY'),
        ],
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
