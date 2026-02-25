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

###############################################################################
# Output format compliance tests
###############################################################################
echo "=== Output format compliance tests ==="

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

###############################################################################
# Describe command
###############################################################################
echo "=== Describe command ==="

DESCRIBE_JSON=$($CLI_CMD describe --format json)
if echo "$DESCRIBE_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Describe JSON output is valid"
else
    echo "ERROR: Describe JSON output is invalid!"
    exit 1
fi

###############################################################################
# Status command
###############################################################################
echo "=== Status command ==="

$CLI_CMD status
STATUS_JSON=$($CLI_CMD status --format json)
if echo "$STATUS_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Status JSON format works"
else
    echo "ERROR: Status JSON format broken!"
    exit 1
fi

###############################################################################
# Application command
###############################################################################
echo "=== Application command ==="

$CLI_CMD application list
$CLI_CMD application list --format json
$CLI_CMD application list --limit 2
$CLI_CMD application list --limit 1 --order D
$CLI_CMD application list --limit 1 --order A --format json

###############################################################################
# User command (CRUD)
###############################################################################
echo "=== User command ==="

$CLI_CMD user create --login test --email test@multiflexi.eu --plaintext secret
$CLI_CMD user update --login test --email changed@multiflexi.eu --plaintext test
$CLI_CMD user list
$CLI_CMD user list --format json
$CLI_CMD user list --limit 1
$CLI_CMD user list --limit 1 --order D
$CLI_CMD user get --login test
$CLI_CMD user get --login test --fields login,email
$CLI_CMD user get --login test --format json

###############################################################################
# Token command
###############################################################################
echo "=== Token command ==="

$CLI_CMD token list
$CLI_CMD token list --format json
$CLI_CMD token list --limit 1
TOKEN_CREATE_JSON=$($CLI_CMD token create --user 1 --format json)
echo "$TOKEN_CREATE_JSON"
TOKEN_ID=$(echo "$TOKEN_CREATE_JSON" | jq -r '.token_id')

if [ "$TOKEN_ID" != "null" ] && [ -n "$TOKEN_ID" ]; then
    echo "✓ Token created with ID: $TOKEN_ID"
    $CLI_CMD token get --id "$TOKEN_ID"
    $CLI_CMD token get --id "$TOKEN_ID" --format json
fi

TOKEN_GEN_JSON=$($CLI_CMD token generate --user 1 --format json)
echo "$TOKEN_GEN_JSON"
GEN_TOKEN_ID=$(echo "$TOKEN_GEN_JSON" | jq -r '.token_id')

if [ "$GEN_TOKEN_ID" != "null" ] && [ -n "$GEN_TOKEN_ID" ]; then
    echo "✓ Token generated with ID: $GEN_TOKEN_ID"
fi

###############################################################################
# Company command (CRUD)
###############################################################################
echo "=== Company command ==="

$CLI_CMD company create --name "Test Company" --email company@multiflexi.eu --slug testco
$CLI_CMD company list
$CLI_CMD company list --format json
$CLI_CMD company list --limit 1
$CLI_CMD company list --limit 1 --order D
$CLI_CMD company get --slug testco
$CLI_CMD company get --slug testco --format json
$CLI_CMD company get --slug testco --fields id,name,slug

###############################################################################
# RunTemplate command
###############################################################################
echo "=== RunTemplate command ==="

$CLI_CMD runtemplate list
$CLI_CMD runtemplate list --format json
$CLI_CMD runtemplate list --limit 2
$CLI_CMD runtemplate list --limit 1 --order D
$CLI_CMD runtemplate list --limit 1 --order A --format json

###############################################################################
# Job command
###############################################################################
echo "=== Job command ==="

$CLI_CMD job status
$CLI_CMD job status --format json
$CLI_CMD job list
$CLI_CMD job list --format json
$CLI_CMD job list --limit 2
$CLI_CMD job list --limit 1 --order D
$CLI_CMD job list --limit 1 --order A --format json
$CLI_CMD job list --limit 1 --fields id,exitcode

###############################################################################
# Artifact command
###############################################################################
echo "=== Artifact command ==="

$CLI_CMD artifact list
$CLI_CMD artifact list --format json
$CLI_CMD artifact list --limit 2
$CLI_CMD artifact list --limit 1 --order D

###############################################################################
# Credential Prototype command (CRUD)
###############################################################################
echo "=== Credential Prototype command ==="

$CLI_CMD crprototype list
$CLI_CMD crprototype list --format json
$CLI_CMD crprototype list --limit 2
$CLI_CMD crprototype list --limit 1 --order D

# Create
$CLI_CMD crprototype create --code TestCred123 --name "Test Credential" --uuid "12345678-1234-5678-9abc-123456789abc" --description "Test credential prototype"
$CLI_CMD crprototype list

# Get
$CLI_CMD crprototype get --code TestCred123
$CLI_CMD crprototype get --uuid "12345678-1234-5678-9abc-123456789abc" --format json

# Update
$CLI_CMD crprototype update --code TestCred123 --description "Updated test credential prototype"
$CLI_CMD crprototype get --code TestCred123

# Import-json (if test file exists)
if [ -f "/usr/lib/multiflexi/crprototype/subreg.crprototype.json" ]; then
    echo "Testing JSON import functionality..."
    $CLI_CMD crprototype import-json --file /usr/lib/multiflexi/crprototype/subreg.crprototype.json
    $CLI_CMD crprototype import-json --file /usr/lib/multiflexi/crprototype/subreg.crprototype.json --format json
fi

###############################################################################
# Credential Type command
###############################################################################
echo "=== Credential Type command ==="

$CLI_CMD credtype list
$CLI_CMD credtype list --format json
$CLI_CMD credtype list --limit 2
$CLI_CMD credtype list --limit 1 --order D

###############################################################################
# Credential command
###############################################################################
echo "=== Credential command ==="

$CLI_CMD credential list
$CLI_CMD credential list --format json
$CLI_CMD credential list --limit 2
$CLI_CMD credential list --limit 1 --order D

###############################################################################
# CompanyApp command
###############################################################################
echo "=== CompanyApp command ==="

# CompanyApp requires company_id and app_id - test with existing data if available
COMPANY_IDS=$($CLI_CMD company list --format json --limit 1 | jq -r '.[0].id // empty')
APP_IDS=$($CLI_CMD application list --format json --limit 1 | jq -r '.[0].id // empty')

if [ -n "$COMPANY_IDS" ] && [ -n "$APP_IDS" ]; then
    $CLI_CMD companyapp list --company_id "$COMPANY_IDS" --app_id "$APP_IDS"
    $CLI_CMD companyapp list --company_id "$COMPANY_IDS" --app_id "$APP_IDS" --format json
    $CLI_CMD companyapp list --company_id "$COMPANY_IDS" --app_id "$APP_IDS" --limit 1
    $CLI_CMD companyapp list --company_id "$COMPANY_IDS" --app_id "$APP_IDS" --limit 1 --order D
    echo "✓ CompanyApp list tests passed"
else
    echo "⚠ Skipping companyapp tests: no companies or applications found"
fi

###############################################################################
# Encryption command
###############################################################################
echo "=== Encryption command ==="

$CLI_CMD encryption status || true
$CLI_CMD encryption status --format json || true

###############################################################################
# Queue command
###############################################################################
echo "=== Queue command ==="

# Queue overview (no action)
$CLI_CMD queue
$CLI_CMD queue --format json

# Queue list
$CLI_CMD queue list
$CLI_CMD queue list --format json
$CLI_CMD queue list --limit 5
$CLI_CMD queue list --order after --limit 5
$CLI_CMD queue list --order after --direction DESC --limit 5

###############################################################################
# Format compliance tests across all list commands
###############################################################################
echo "=== Format compliance tests ==="

echo "Testing application list JSON format compliance"
APP_JSON=$($CLI_CMD application list --format json)
if echo "$APP_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Application list JSON format works"
else
    echo "ERROR: Application list JSON format broken!"
    exit 1
fi

echo "Testing user list JSON format compliance"
USER_JSON=$($CLI_CMD user list --format json)
if echo "$USER_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ User list JSON format works"
else
    echo "ERROR: User list JSON format broken!"
    exit 1
fi

echo "Testing company list JSON format compliance"
COMPANY_JSON=$($CLI_CMD company list --format json)
if echo "$COMPANY_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Company list JSON format works"
else
    echo "ERROR: Company list JSON format broken!"
    exit 1
fi

echo "Testing runtemplate list JSON format compliance"
RT_JSON=$($CLI_CMD runtemplate list --format json)
if echo "$RT_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ RunTemplate list JSON format works"
else
    echo "ERROR: RunTemplate list JSON format broken!"
    exit 1
fi

echo "Testing job list JSON format compliance"
JOB_JSON=$($CLI_CMD job list --format json)
if echo "$JOB_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Job list JSON format works"
else
    echo "ERROR: Job list JSON format broken!"
    exit 1
fi

echo "Testing job status JSON format compliance"
JOB_STATUS_JSON=$($CLI_CMD job status --format json)
if echo "$JOB_STATUS_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Job status JSON format works"
else
    echo "ERROR: Job status JSON format broken!"
    exit 1
fi

echo "Testing artifact list JSON format compliance"
ARTIFACT_JSON=$($CLI_CMD artifact list --format json)
if echo "$ARTIFACT_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Artifact list JSON format works"
else
    echo "ERROR: Artifact list JSON format broken!"
    exit 1
fi

echo "Testing crprototype list JSON format compliance"
CRPROTO_JSON=$($CLI_CMD crprototype list --format json)
if echo "$CRPROTO_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Credential Prototype list JSON format works"
else
    echo "ERROR: Credential Prototype list JSON format broken!"
    exit 1
fi

echo "Testing credtype list JSON format compliance"
CREDTYPE_JSON=$($CLI_CMD credtype list --format json)
if echo "$CREDTYPE_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Credential Type list JSON format works"
else
    echo "ERROR: Credential Type list JSON format broken!"
    exit 1
fi

echo "Testing credential list JSON format compliance"
CRED_JSON=$($CLI_CMD credential list --format json)
if echo "$CRED_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Credential list JSON format works"
else
    echo "ERROR: Credential list JSON format broken!"
    exit 1
fi

echo "Testing token list JSON format compliance"
TOKEN_JSON=$($CLI_CMD token list --format json)
if echo "$TOKEN_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Token list JSON format works"
else
    echo "ERROR: Token list JSON format broken!"
    exit 1
fi

echo "Testing queue list JSON format compliance"
QUEUE_JSON=$($CLI_CMD queue list --format json)
if echo "$QUEUE_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Queue list JSON format works"
else
    echo "ERROR: Queue list JSON format broken!"
    exit 1
fi

echo "Testing queue overview JSON format compliance"
QUEUE_OV_JSON=$($CLI_CMD queue --format json)
if echo "$QUEUE_OV_JSON" | jq . >/dev/null 2>&1; then
    echo "✓ Queue overview JSON format works"
else
    echo "ERROR: Queue overview JSON format broken!"
    exit 1
fi

#TODO check config editing : $CLI_CMD runtemplate update --id=159 --config=IMPORT_SCOPE=2025-08-01>2025-08-02 --format json
#TODO check json validation: $CLI_CMD application validate-json --file /usr/lib/multiflexi-probe/multiflexi/multiflexi_probe.multiflexi.app.json

echo "All format compliance tests passed! ✓"

###############################################################################
# Delete / cleanup action tests (keep at bottom)
###############################################################################
echo "=== Cleanup / delete tests ==="

# Delete credential prototype
$CLI_CMD crprototype delete --code TestCred123 --format json
$CLI_CMD crprototype list

# Delete test user
$CLI_CMD user delete --login test --format json
$CLI_CMD user list

# Delete test company
TESTCO_ID=$($CLI_CMD company list --format json | jq -r '.[] | select(.slug == "testco") | .id // empty')
if [ -n "$TESTCO_ID" ]; then
    $CLI_CMD company remove --id "$TESTCO_ID" --format json
    echo "✓ Test company removed"
fi

# Queue truncate
$CLI_CMD queue list
$CLI_CMD queue truncate
$CLI_CMD queue list

# Prune
$CLI_CMD prune --logs --jobs

echo "=== All tests passed! ✓ ==="
