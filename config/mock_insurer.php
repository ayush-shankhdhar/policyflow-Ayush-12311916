<?php

return [
    // Choose scenario for deterministic mock insurer behavior.
    // Options: 'SUCCESS', 'TEMPORARY_FAILURE', 'TIMEOUT_AFTER_ISSUE', 'DELAYED_CALLBACK', 'DUPLICATE_CALLBACK'
    'scenario' => env('MOCK_INSURER_SCENARIO', 'SUCCESS'),
];
?>
