<?php

return [

    'batch_size' => (int) env('DOCUMENT_ANALYSIS_BATCH_SIZE', 6),

    'polling_interval' => env('DOCUMENT_ANALYSIS_POLLING_INTERVAL', '5s'),

    'ai' => [
        'provider' => env('DOCUMENT_ANALYSIS_AI_PROVIDER', config('ai.default', 'openai')),
        'model' => env('DOCUMENT_ANALYSIS_AI_MODEL', 'gpt-5.4-nano'),
        'supported_providers' => ['openai', 'anthropic', 'gemini', 'azure'],
    ],

    'component_detection' => [
        'rules' => [
            ['code' => 'wifi_module', 'name' => 'WiFi Module', 'patterns' => ['/\\b(wifi|wlan|wireless)\\b/']],
            ['code' => 'bluetooth_module', 'name' => 'Bluetooth Module', 'patterns' => ['/\\b(bluetooth|bt)\\b/']],
            ['code' => 'battery', 'name' => 'Battery', 'patterns' => ['/\\b(battery|akku|batterie)\\b/']],
            ['code' => 'usb_cable', 'name' => 'USB Cable', 'patterns' => ['/\\b(usb|cable|kabel)\\b/']],
            ['code' => 'charger', 'name' => 'Charger / Power Supply', 'patterns' => ['/\\b(charger|ladegerat|netzteil)\\b/']],
            ['code' => 'nfc_module', 'name' => 'NFC Module', 'patterns' => ['/\\bnfc\\b/']],
            ['code' => 'speaker', 'name' => 'Speaker', 'patterns' => ['/\\b(speaker|lautsprecher)\\b/']],
            ['code' => 'display', 'name' => 'Display', 'patterns' => ['/\\b(display|screen)\\b/']],
            ['code' => 'emc_module', 'name' => 'EMC Module', 'patterns' => ['/\\b(emc|electromagnetic)\\b/']],
            ['code' => 'cybersecurity_module', 'name' => 'Cybersecurity Module', 'patterns' => ['/\\b(cybersecurity|cyber)\\b/']],
            ['code' => 'pcb_main_board', 'name' => 'PCB / Main Board', 'patterns' => ['/\\b(pcb|circuit\\s+board|main\\s+board)\\b/']],
        ],
    ],

];
