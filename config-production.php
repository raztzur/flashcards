<?php
// Production configuration for Flashcards System
// Copy this to site/config/config.php on your server

return [
    'debug' => false,
    'cache' => [
        'pages' => [
            'active' => true,
            'ignore' => function ($page) {
                return $page->template() === 'test';
            }
        ]
    ],
    'panel' => [
        'install' => false,  // Set to false after first admin user creation
        'slug' => 'admin'    // Change this to something unique for security
    ],
    'routes' => [
        // Your existing routes are loaded from site/config/routes.php
    ],
    'hooks' => [
        // Add any production-specific hooks here
    ]
];