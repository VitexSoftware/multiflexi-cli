#!/bin/bash
set -x
# Test suite for multiflexi-cli with various commands
set -e

# Use local development version - adjust path based on where script is run
if [ -f "src/multiflexi-cli.php" ]; then
    CLI_CMD="php src/multiflexi-cli.php"
elif [ -f "multiflexi-cli.php" ]; then
    CLI_CMD="php multiflexi-cli.php"
else
    CLI_CMD="php $(dirname $0)/../src/multiflexi-cli.php"
fi

# Test output format compliance
echo "Testing default text output format (should not be JSON)"
OUTPUT=$($CLI_CMD application list)
if echo "$OUTPUT" | jq . >/dev/null 2>&1; then
    echo "ERROR: Default output is JSON format, should be text!"
    exit 1
else
    echo "✓ Default output is text format (correct)"
fi

echo "Testing explicit JSON format request"
OUTPUT=$($CLI_CMD application list --format json)
if echo "$OUTPUT" | jq . >/dev/null 2>&1; then
    echo "✓ JSON format works when requested"
else
    echo "ERROR: JSON format not working when requested!"
    exit 1
fi

$CLI_CMD application list
$CLI_CMD runtemplate list

$CLI_CMD user create --login test --email test@multiflexi.eu --plaintext secret
$CLI_CMD user update --login test --email changed@multiflexi.exu --plaintext test
$CLI_CMD user list

$CLI_CMD user get --login test
$CLI_CMD user get --login test --fields login,email,company

$CLI_CMD company create --name "Test Company" --email company@multiflexi.eu --slug testco
$CLI_CMD company list

# $CLI_CMD runtemplate create --name "Test Template" --uuid 868a8085-03e5-4f9b-899d-2084e1de7d3b --company-slug testco --company-id 1
$CLI_CMD runtemplate list

# Credential Prototype tests
echo "Testing credential prototype operations..."

# Test list
$CLI_CMD crprototype list
$CLI_CMD crprototype list --format json

# Test create with proper validation
$CLI_CMD crprototype create --code TestCred123 --name "Test Credential" --uuid "12345678-1234-5678-9abc-123456789abc" --description "Test credential prototype"
$CLI_CMD crprototype list

# Test get operations
$CLI_CMD crprototype get --code TestCred123
$CLI_CMD crprototype get --uuid "12345678-1234-5678-9abc-123456789abc" --format json

# Test update
$CLI_CMD crprototype update --code TestCred123 --description "Updated test credential prototype"
$CLI_CMD crprototype get --code TestCred123

# Test import-json functionality (if test file exists)
if [ -f "/usr/lib/multiflexi/crprototype/subreg.crprototype.json" ]; then
    echo "Testing JSON import functionality..."
    $CLI_CMD crprototype import-json --file /usr/lib/multiflexi/crprototype/subreg.crprototype.json
    $CLI_CMD crprototype import-json --file /usr/lib/multiflexi/crprototype/subreg.crprototype.json --format json
fi

echo '###################################################'
$CLI_CMD appstatus
echo '###################################################'

# Format compliance tests for different commands
echo "Testing format compliance across different commands..."

echo "Testing user commands format compliance"
USER_JSON=$($CLI_CMD user list --format json)
if echo "$USER_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ User list JSON format works"
else
    echo "ERROR: User list JSON format broken!"
    exit 1
fi

echo "Testing company commands format compliance"
COMPANY_JSON=$($CLI_CMD company list --format json)
if echo "$COMPANY_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Company list JSON format works"
else
    echo "ERROR: Company list JSON format broken!"
    exit 1
fi

echo "Testing runtemplate commands format compliance"
RT_JSON=$($CLI_CMD runtemplate list --format json)
if echo "$RT_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ RunTemplate list JSON format works"
else
    echo "ERROR: RunTemplate list JSON format broken!"
    exit 1
fi

echo "Testing crprototype commands format compliance"
CRPROTO_JSON=$($CLI_CMD crprototype list --format json)
if echo "$CRPROTO_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Credential Prototype list JSON format works"
else
    echo "ERROR: Credential Prototype list JSON format broken!"
    exit 1
fi

# Run template with parameters
# multiflexi-run-template --uuid 868a8085-03e5-4f9b-899d-2084e1de7d3b --company-slug testco --company-id 1 --run-params '{"param1":"value1","param2":"value2"}'

#TODO check config editing : $CLI_CMD runtemplate update --id=159 --config=IMPORT_SCOPE=2025-08-01>2025-08-02 --format json
#TODO check json validation: $CLI_CMD application validate-json --json /usr/lib/multiflexi-probe/multiflexi/multiflexi_probe.multiflexi.app.json

echo "All format compliance tests passed! ✓"

# Delete action tests
$CLI_CMD crprototype delete --code TestCred123 --format json
$CLI_CMD crprototype list

$CLI_CMD user delete --login test --format json
$CLI_CMD user list

$CLI_CMD queue list
$CLI_CMD queue truncate
$CLI_CMD queue list



multiflexi-cli prune --logs --jobs
