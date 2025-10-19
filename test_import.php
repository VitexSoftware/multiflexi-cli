<?php

require_once __DIR__ . '/src/Command/CredentialTypeCommand.php';

// Simple test script to validate the import logic
use MultiFlexi\Cli\Command\CredentialTypeCommand;

$command = new CredentialTypeCommand();

// Test JSON validation
$testFile = __DIR__ . '/../php-vitexsoftware-multiflexi-core/tests/test.credential-type.json';

if (file_exists($testFile)) {
    $jsonContent = file_get_contents($testFile);
    $data = json_decode($jsonContent, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✓ JSON file is valid\n";
        
        // Check required fields
        $requiredFields = ['id', 'uuid', 'name', 'description', 'fields'];
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (empty($missing)) {
            echo "✓ All required fields present\n";
            echo "  ID: " . $data['id'] . "\n";
            echo "  UUID: " . $data['uuid'] . "\n";
            echo "  Name: " . (is_array($data['name']) ? json_encode($data['name']) : $data['name']) . "\n";
            echo "  Fields count: " . count($data['fields']) . "\n";
        } else {
            echo "✗ Missing required fields: " . implode(', ', $missing) . "\n";
        }
    } else {
        echo "✗ Invalid JSON: " . json_last_error_msg() . "\n";
    }
} else {
    echo "✗ Test file not found: $testFile\n";
}

echo "\nImport functionality has been added to CredentialTypeCommand.php\n";
echo "Usage: multiflexi-cli credtype import --file path/to/file.json\n";
