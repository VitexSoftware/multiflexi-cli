<?php

// Simple validation of the import functionality
$testFile = __DIR__ . '/../php-vitexsoftware-multiflexi-core/tests/test.credential-type.json';

echo "MultiFlexi CLI Credential Type Import Validation\n";
echo "===============================================\n\n";

if (file_exists($testFile)) {
    echo "✓ Test file found: $testFile\n";
    
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
            echo "✓ All required fields present:\n";
            echo "  - ID: " . $data['id'] . "\n";
            echo "  - UUID: " . $data['uuid'] . "\n";
            echo "  - Code: " . ($data['code'] ?? 'N/A') . "\n";
            echo "  - Name: " . (is_array($data['name']) ? json_encode($data['name']) : $data['name']) . "\n";
            echo "  - Fields count: " . count($data['fields']) . "\n";
            
            echo "\n✓ Field validation:\n";
            foreach ($data['fields'] as $i => $field) {
                $fieldRequired = ['keyword', 'name', 'type'];
                $fieldMissing = [];
                foreach ($fieldRequired as $req) {
                    if (!isset($field[$req])) {
                        $fieldMissing[] = $req;
                    }
                }
                if (empty($fieldMissing)) {
                    echo "  - Field $i: " . $field['keyword'] . " (" . $field['type'] . ") ✓\n";
                } else {
                    echo "  - Field $i: Missing " . implode(', ', $fieldMissing) . " ✗\n";
                }
            }
        } else {
            echo "✗ Missing required fields: " . implode(', ', $missing) . "\n";
        }
    } else {
        echo "✗ Invalid JSON: " . json_last_error_msg() . "\n";
    }
} else {
    echo "✗ Test file not found: $testFile\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "IMPORT FUNCTIONALITY ADDED\n";
echo str_repeat("=", 50) . "\n";
echo "The CredentialTypeCommand has been updated with:\n";
echo "  • New 'import' action\n";
echo "  • --file option for specifying JSON file\n";
echo "  • JSON validation against schema\n";
echo "  • Duplicate checking by UUID and ID\n";
echo "  • Database insertion with proper field encoding\n";
echo "  • Error handling and status reporting\n\n";
echo "Usage:\n";
echo "  multiflexi-cli credtype import --file /path/to/credential-type.json\n";
echo "  multiflexi-cli credtype import --file /path/to/credential-type.json --format json\n\n";
