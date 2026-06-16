.. _multiflexi-cli:

MultiFlexi CLI
==============

The MultiFlexi CLI is a powerful Symfony Console-based command line interface for comprehensive management of MultiFlexi resources. It provides full CRUD operations for all system entities and supports both text and JSON output formats for automation and scripting.

Installation
------------

The CLI is included with MultiFlexi and available as:

.. code-block:: bash

    # System-wide installation
    multiflexi-cli <command:action> [options]

    # Local installation
    ./cli/multiflexi-cli <command:action> [options]

General Usage
-------------

.. code-block:: bash

    multiflexi-cli <command:action> [options]

**Global Options:**

- ``-f, --format`` - Output format: text or json (default: text)
- ``-v, --verbose`` - Increase verbosity (use -vv or -vvv for more detail)
- ``--no-ansi`` - Disable colored output
- ``-h, --help`` - Display help for the command
- ``-V, --version`` - Display application version

**Environment Configuration:**

Use the ``-e`` or ``--environment`` option to specify a custom .env file:

.. code-block:: bash

    multiflexi-cli -e /path/to/custom/.env command:action


Commands Overview
-----------------

The MultiFlexi CLI provides the following main commands:

- **application:\***     - Manage applications (list, get, create, update, delete, import/export/remove JSON, show config)
- **company:\***         - Manage companies and their settings
- **company-app:\***     - Manage company-application relations (list, assign, unassign)
- **user-company:\***    - Assign/unassign users to companies
- **user-role:\***       - Set RBAC roles for users
- **job:\***             - Manage job execution and monitoring
- **run-template:\***    - Manage run templates, scheduling, and credential assignment
- **user:\***            - User account management
- **user-erasure:\***    - GDPR user data erasure management
- **token:\***           - API token management
- **credential-type:\*** - Credential type operations
- **credential:\***      - Credential management
- **event-source:\***    - Manage event sources
- **event-rule:\***      - Manage event rules
- **artifact:\***        - Manage job artifacts
- **encryption:\***      - Manage encryption keys
- **queue:\***           - Job queue operations
- **status**             - System status information (encryption, Zabbix, OpenTelemetry)
- **telemetry:test**     - Test OpenTelemetry metrics export
- **describe**           - List all available commands and their parameters
- **prune**              - Prune logs and jobs, keeping only the latest N records (default: 1000)
- **completion**         - Dump the shell completion script

Detailed Command Reference
-------------------------

.. contents::
   :local:
   :depth: 2


application
-----------

Manage applications (list, get, create, update, delete, import/export/remove JSON, show configuration fields).

Options:
  --id           Application ID
  --uuid         Application UUID
  --name         Name
  --description  Description
  --topics       Topics
  --executable   Executable
  --ociimage     OCI Image
  --requirements Requirements
  --homepage     Homepage URL
  --file         Path to JSON file for import/export/remove
  --appversion   Application Version
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli application:list
    multiflexi-cli application:get --id=1
    multiflexi-cli application:get --uuid=uuid-123
    multiflexi-cli application:get --name="App1"
    multiflexi-cli application:create --name="App1" --uuid="uuid-123"
    multiflexi-cli application:update --id=1 --name="App1 Updated"
    multiflexi-cli application:delete --id=1
    multiflexi-cli application:import-json --file=app.json
    multiflexi-cli application:export-json --id=1 --file=app.json
    multiflexi-cli application:show-config --id=1

company-app
-----------

Manage company-application relations (list, assign, unassign).

Output columns for ``company-app:list``:

- **id** – RunTemplate ID
- **company_id**, **company_name**, **company_slug** – company details
- **app_id**, **app_name**, **app_uuid** – application details

Options:
  --company_id   Company ID (optional filter)
  --app_id       Application ID (optional filter)
  --app_uuid     Application UUID (optional filter; resolved to app_id)
  --limit        Limit number of results
  --offset       Offset for pagination
  --order        Sort order: A (ascending) or D (descending)
  --fields       Comma-separated list of fields to display
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli company-app:list
    multiflexi-cli company-app:list --company_id=1
    multiflexi-cli company-app:list --company_id=1 --app_id=2
    multiflexi-cli company-app:list --company_id=1 --app_id=2 --limit=10 --offset=0 --order=D
    multiflexi-cli company-app:list --format=json
    multiflexi-cli company-app:assign --company_id=1 --app_id=2
    multiflexi-cli company-app:assign --company_id=1 --app_uuid=uuid-123 --format=json
    multiflexi-cli company-app:unassign --company_id=1 --app_id=2

user-company
------------

Manage user-company assignments (assign, unassign).

Options:
  --company_id   Company ID (required)
  --user_id      User ID (required unless --login/--email is used)
  --login        User login (alternative to --user_id)
  --email        User email (alternative to --user_id)
  --role         Assignment role for ``company_user.role`` (assign only, default: ``viewer``)
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli user-company:assign --company_id=2 --user_id=10 --role=viewer
    multiflexi-cli user-company:assign --company_id=2 --login=jsmith --role=editor
    multiflexi-cli user-company:unassign --company_id=2 --email=john@example.com

user-role
---------

Set RBAC roles for users.

Options:
  --user_id      User ID (required unless --login/--email is used)
  --login        User login (alternative to --user_id)
  --email        User email (alternative to --user_id)
  --roles        Comma-separated role names (e.g. ``admin,viewer``)
  --replace      Replace existing assignments (true/false, default: true)
  --assigned_by  Optional user ID to store as assigner
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli user-role:set --user_id=10 --roles=admin,viewer --replace=true
    multiflexi-cli user-role:set --login=jsmith --roles=editor --replace=false --assigned_by=1

credential-type
---------------

Credential type operations (list, get, create, update, delete, import-json, export-json, remove-json, validate-json).

Options:
  --id           Credential Type ID
  --uuid         Credential Type UUID
  --name         Name
  --file         Path to JSON file for import/export/remove/validate operations
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli credential-type:list
    multiflexi-cli credential-type:get --id=1
    multiflexi-cli credential-type:get --uuid="d3d3ae58-d64a-4ab4-afb5-ba439ffc8587"
    multiflexi-cli credential-type:update --id=1 --name="Updated API Key"

    # JSON Operations
    multiflexi-cli credential-type:validate-json --file new-credtype.json
    multiflexi-cli credential-type:import-json --file credential-type.json
    multiflexi-cli credential-type:export-json --id=1 --file exported-credtype.json

**JSON Import Features:**

- **Schema Validation**: All JSON files are validated against the MultiFlexi credential type schema before import
- **Duplicate Detection**: Prevents importing credential types with existing UUIDs
- **Localization Support**: Supports multi-language names and descriptions
- **Field Definition Import**: Automatically creates field definitions with proper types and validation
- **Error Reporting**: Detailed error messages for validation failures and import issues

**Credential Type JSON Structure:**

The JSON file must conform to the MultiFlexi credential type schema and include:

- ``uuid``: Unique identifier for the credential type
- ``code``: Short code for the credential type
- ``name``: Name (can be localized object or string)
- ``description``: Description (can be localized object or string)
- ``fields``: Array of field definitions with keyword, name, type, description, and requirements

Example credential type JSON:

.. code-block:: json

    {
      "uuid": "d3d3ae58-d64a-4ab4-afb5-ba439ffc8587",
      "code": "ProbeAPI",
      "name": {
        "en": "Probe API Credentials",
        "cs": "Přihlašovací údaje pro Probe API"
      },
      "description": {
        "en": "Credential type for probe integrations.",
        "cs": "Typ přihlašovacích údajů pro sondy."
      },
      "fields": [
        {
          "keyword": "PROBE_API_KEY",
          "name": {
            "en": "API Key",
            "cs": "API klíč"
          },
          "type": "secret",
          "description": {
            "en": "API key for authentication.",
            "cs": "API klíč pro autentizaci."
          },
          "required": true
        }
      ]
    }

company
-------

Manage companies (list, get, create, update, remove).

Options:
  --id           Company ID
  --name         Company name
  --customer     Customer
  --enabled      Enabled (true/false)
  --settings     Settings
  --logo         Logo
  --ic           IC
  --DatCreate    Created date (date-time)
  --DatUpdate    Updated date (date-time)
  --email        Email
  --slug         Company Slug
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli company:list
    multiflexi-cli company:get --id=1
    multiflexi-cli company:create --name="Acme Corp" --customer="CustomerX"
    multiflexi-cli company:remove --id=1

job
---

Manage jobs (list, get, create, update, delete, status).

Options:
  --id             Job ID
  --runtemplate_id RunTemplate ID
  --scheduled      Scheduled datetime
  --executor       Executor
  --schedule_type  Schedule type
  --app_id         App ID
  --limit          Limit number of results
  --offset         Offset for pagination
  --order          Sort order: A (ascending) or D (descending)
  --status         Filter by job state: ``failed``, ``success``, ``running``, ``pending``

                 - ``failed``  – completed with non-zero exit code
                 - ``success`` – completed successfully (``exitcode = 0``)
                 - ``running`` – started but not yet finished
                 - ``pending`` – scheduled but not yet started

  --fields         Comma-separated list of fields to display
  -f, --format     Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli job:list
    multiflexi-cli job:list --limit=10 --order=D
    multiflexi-cli job:list --status=failed
    multiflexi-cli job:list --status=pending --format=json
    multiflexi-cli job:get --id=123
    multiflexi-cli job:status --id=123
    multiflexi-cli job:create --runtemplate_id=5 --scheduled="2024-07-01 12:00"
    multiflexi-cli job:update --id=123 --executor=Native
    multiflexi-cli job:delete --id=123

run-template
------------

Manage run templates (list, get, create, update, delete, schedule, and credential assignment).

.. code-block:: bash

    multiflexi-cli run-template:list [options]
    multiflexi-cli run-template:get --id=<id> [options]
    multiflexi-cli run-template:create --name=<name> --app_id=<id> --company_id=<id> [options]
    multiflexi-cli run-template:update --id=<id> [options]
    multiflexi-cli run-template:delete --id=<id>
    multiflexi-cli run-template:schedule --id=<id> [options]
    multiflexi-cli run-template:assign-credential --runtemplate_id=<id> --credential_id=<id> [options]
    multiflexi-cli run-template:unassign-credential --runtemplate_id=<id> --credential_id=<id> [options]
    multiflexi-cli run-template:list-credentials --runtemplate_id=<id> [options]

Common options:
  --id           RunTemplate ID
  --name         Name
  --app_id       App ID
  --company_id   Company ID
  --interv       Interval code
  --active       Active
  --config       Config key=value, saved persistently to run-template (repeatable, used with create/update)
  -f, --format   Output format: text or json (default: text)

Schedule-specific options:
  --env          One-time environment override key=value — passed to the job but NOT saved to run-template (repeatable)
  --schedule_time Schedule time (Y-m-d H:i:s or "now", default: now)
  --executor     Executor to use for this job

Credential assignment options:
  --runtemplate_id  RunTemplate ID
  --credential_id   Credential ID

.. note::

   Use ``--env`` when scheduling to pass one-time environment variable overrides.
   ``--config`` is for persistent run-template configuration (create/update).
   This distinction prevents accidentally leaving a temporary override saved in the run-template.

Examples:

.. code-block:: bash

    multiflexi-cli run-template:create --name="Import Yesterday" --app_id=19 --company_id=1 --config=IMPORT_SCOPE=yesterday --config=ANOTHER_KEY=foo
    multiflexi-cli run-template:update --id=230 --config=IMPORT_SCOPE=yesterday --config=ANOTHER_KEY=foo
    multiflexi-cli run-template:get --id=230 --format=json
    multiflexi-cli run-template:create --name="Import" --app_id=6e2b2c2e-7c2a-4b1a-8e2d-123456789abc --company_id=1

    # One-time backfill with a custom IMPORT_SCOPE — value is NOT saved to run-template:
    multiflexi-cli run-template:schedule --id=167 --env=IMPORT_SCOPE=2025-11-1>2026-01-07

    # Regular schedule with future time:
    multiflexi-cli run-template:schedule --id=123 --schedule_time="2025-07-01 10:00:00" --executor=Native --env=FOO=bar --env=BAZ=qux

    # Assign a credential to a run template:
    multiflexi-cli run-template:assign-credential --runtemplate_id=5 --credential_id=12

    # Remove a credential assignment from a run template:
    multiflexi-cli run-template:unassign-credential --runtemplate_id=5 --credential_id=12

    # List all credentials assigned to a run template:
    multiflexi-cli run-template:list-credentials --runtemplate_id=5
    multiflexi-cli run-template:list-credentials --runtemplate_id=5 --format=json

user
----

Manage users (list, get, create, update, delete).

Options:
  --id           User ID
  --login        Login
  --firstname    First name
  --lastname     Last name
  --email        Email
  --password     Password (hashed)
  --plaintext    Plaintext password
  --enabled      Enabled (true/false)
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli user:list
    multiflexi-cli user:get --id=1
    multiflexi-cli user:create --login="jsmith" --firstname="John" --lastname="Smith" --email="jsmith@example.com" --plaintext="secret"
    multiflexi-cli user:update --id=1 --email="john.smith@example.com"
    multiflexi-cli user:delete --id=1

user:data-erasure
-----------------

Manage GDPR user data erasure requests under Article 17 (Right to Erasure).

.. code-block:: bash

    multiflexi-cli user:data-erasure <action> [options]

Actions:
- list:     List deletion requests (optionally filtered by status).
- create:   Create a new deletion request for a user.
- approve:  Approve a pending deletion request (requires admin).
- reject:   Reject a pending deletion request (requires admin).
- process:  Process an approved deletion request.
- audit:    Show audit trail for a deletion request.
- cleanup:  Clean up old audit logs (7-year retention).

Options:
  --user-id          Target user ID for the operation
  --user-login       Target user login for the operation
  --request-id       Deletion request ID
  --deletion-type    Deletion type: soft, hard, anonymize (default: soft)
  --reason           Reason for the deletion request
  --notes            Review notes for approval/rejection
  --force            Force operation without confirmation
  --export-audit     Export audit trail to CSV file
  --status           Filter requests by status: pending, approved, rejected, completed
  -f, --format       Output format: text or json (default: text)

Deletion Types:
- **soft**: Disable user account, anonymize personal data, preserve data structures
- **hard**: Permanently delete user data and account (requires approval)
- **anonymize**: Replace personal data with anonymized values, disable account

Examples:

.. code-block:: bash

    # List all pending deletion requests
    multiflexi-cli user:data-erasure list --status=pending
    
    # Create a soft deletion request for user ID 123
    multiflexi-cli user:data-erasure create --user-id=123 --deletion-type=soft --reason="User requested account deletion"
    
    # Create a hard deletion request by user login
    multiflexi-cli user:data-erasure create --user-login=jsmith --deletion-type=hard --reason="Legal compliance requirement"
    
    # Approve a deletion request with review notes
    multiflexi-cli user:data-erasure approve --request-id=456 --notes="Verified user identity and legal basis"
    
    # Reject a deletion request
    multiflexi-cli user:data-erasure reject --request-id=789 --reason="Insufficient documentation provided"
    
    # Process an approved deletion request
    multiflexi-cli user:data-erasure process --request-id=456
    
    # Show audit trail and export to CSV
    multiflexi-cli user:data-erasure audit --request-id=456 --export-audit=/tmp/audit_456.csv
    
    # Clean up old audit logs (7-year retention)
    multiflexi-cli user:data-erasure cleanup

token
-----

Manage tokens (list, get, create, generate, update, delete).

Options:
  --id           Token ID
  --user         User ID
  --token        Token value
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli token:list
    multiflexi-cli token:get --id=1
    multiflexi-cli token:create --user=2
    multiflexi-cli token:generate --user=2
    multiflexi-cli token:update --id=1 --token=NEWVALUE
    multiflexi-cli token:delete --id=1

encryption
----------

Manage encryption keys for secure credential storage. MultiFlexi uses AES-256 encryption to protect sensitive data (passwords, API keys, tokens) in the database.

Options:
  -f, --format   Output format: text or json (default: text)

Configuration
^^^^^^^^^^^^^

MultiFlexi encryption requires ``ENCRYPTION_MASTER_KEY`` to be configured in one of the following ways (checked in priority order):

1. Environment variable: ``ENCRYPTION_MASTER_KEY``
2. Environment variable: ``MULTIFLEXI_MASTER_KEY`` (backward compatibility)
3. Configuration file: ``/etc/multiflexi/multiflexi.env``

**Automatic Setup**: During installation of the ``multiflexi-common`` package, a master key is automatically generated and stored in ``/etc/multiflexi/multiflexi.env``.

**Manual Configuration**:

.. code-block:: bash

    # Generate a secure 256-bit key
    openssl rand -base64 32
    
    # Add to /etc/multiflexi/multiflexi.env
    echo "ENCRYPTION_MASTER_KEY=<generated-key>" | sudo tee -a /etc/multiflexi/multiflexi.env

**Important Security Notes**:

- Backup ``/etc/multiflexi/multiflexi.env`` - without the master key, encrypted credentials cannot be recovered
- Never commit the master key to version control
- If the master key is lost, all encrypted credentials become permanently inaccessible
- The master key is used to encrypt database encryption keys (key wrapping)

Status Action
^^^^^^^^^^^^^

Check the encryption system status:

.. code-block:: bash

    multiflexi-cli encryption:status

    # JSON output for automation
    multiflexi-cli encryption:status -f json

Sample output:

.. code-block:: text

    Encryption Status
    Master Key: configured
    Total Keys: 3
    Active Keys: 3
    
    Keys:
    +-------------+-------------+--------+---------------------+---------+
    | Key Name    | Algorithm   | Status | Created             | Rotated |
    +-------------+-------------+--------+---------------------+---------+
    | credentials | aes-256-gcm | active | 2025-10-30 09:00:00 | never   |
    | default     | aes-256-gcm | active | 2025-10-29 10:00:00 | never   |
    | personal    | aes-256-gcm | active | 2025-10-28 08:00:00 | never   |
    +-------------+-------------+--------+---------------------+---------+

JSON output includes:

.. code-block:: json

    {
        "success": true,
        "message": "Encryption status retrieved",
        "data": {
            "master_key": "configured",
            "total_keys": 3,
            "active_keys": 3,
            "keys": [
                {
                    "key_name": "credentials",
                    "algorithm": "aes-256-gcm",
                    "created_at": "2025-10-30 09:00:00",
                    "rotated_at": null,
                    "is_active": true
                }
            ]
        }
    }

Init Action
^^^^^^^^^^^

Re-initialize encryption keys:

.. code-block:: bash

    # Re-initialize encryption keys
    multiflexi-cli encryption:init

    # Re-initialize with JSON output
    multiflexi-cli encryption:init -f json

Sample output:

.. code-block:: text

    Encryption key initialized successfully
    Key name: credentials
    Algorithm: aes-256-gcm
    WARNING: All existing encrypted credentials are now invalid and must be re-entered

**Warning**: Re-initializing encryption keys will invalidate all previously encrypted credentials. All sensitive data must be re-entered after running this command. Use this command only during:

- Initial system setup
- After master key rotation
- Security incident response
- Explicit security policy requirements

**Error Handling**:

If ``ENCRYPTION_MASTER_KEY`` is not configured, the init command will fail:

.. code-block:: text

    ERROR: ENCRYPTION_MASTER_KEY is not configured. Set it in .env file or as environment variable.

queue
-----

Queue operations (overview, list, fix, truncate).

Options:
  -f, --format     Output format: text or json (default: text)
  --limit          Limit number of results
  --order          Sort field: "after", "id"
  --direction      Sort direction: "ASC", "DESC", "A", "D" (default: ASC)
  --fields         Comma-separated list of fields to display

**queue:list features:**

- **Schedule Type**: Human-readable schedule types converted from interval codes
- **Waiting Time**: Human-readable time remaining (e.g., "2h 45m", "overdue")
- **Complete Job Details**: RunTemplate name, Application name, Company information

Examples:

.. code-block:: bash

    # Show overview metrics
    multiflexi-cli queue:overview

    # Basic queue listing
    multiflexi-cli queue:list

    # Order by scheduled time (earliest first)
    multiflexi-cli queue:list --order after --limit 10

    # Order by scheduled time (latest first)
    multiflexi-cli queue:list --order after --direction DESC --limit 10

    # Show only specific fields
    multiflexi-cli queue:list --fields "id,after,schedule_type,runtemplate_name" --limit 5

    # JSON output for automation
    multiflexi-cli queue:list --format json --limit 20

    # Fix orphaned jobs and queue inconsistencies
    multiflexi-cli queue:fix

    # Truncate all jobs
    multiflexi-cli queue:truncate

prune
-----

Prune logs and jobs, keeping only the latest N records (default: 1000).

.. code-block:: bash

    multiflexi-cli prune [--logs] [--jobs] [--keep=N]

Options:
  --logs         Prune logs table
  --jobs         Prune jobs table
  --keep         Number of records to keep (default: 1000)

Examples:

.. code-block:: bash

    multiflexi-cli prune --logs
    multiflexi-cli prune --jobs --keep=500
    multiflexi-cli prune --logs --jobs --keep=2000

completion
----------

Dump the shell completion script for bash, zsh, or fish.

.. code-block:: bash

    multiflexi-cli completion [shell]

Options:
  --debug        Tail the completion debug log

Examples:

.. code-block:: bash

    multiflexi-cli completion bash
    multiflexi-cli completion zsh
    multiflexi-cli completion fish

describe
--------

List all available commands and their parameters.

.. code-block:: bash

    multiflexi-cli describe


status
------

Show current MultiFlexi system status, including version, database, PHP, OS, resource usage, monitoring systems (Zabbix, OpenTelemetry), encryption, and service health.

.. code-block:: bash

    multiflexi-cli status
    multiflexi-cli status --format json

Sample output:

.. code-block:: text

    version-cli: dev-main
    db-migration: RuntemplateCron
    php: 8.4.11
    os: Linux
    memory: 4071888
    companies: 4
    apps: 22
    runtemplates: 177
    topics: 27
    credentials: 129
    credential types: 9
    database: mysql Localhost via UNIX socket Uptime: 12711  Threads: 12  Questions: 2010  Slow queries: 0  Opens: 113  Open tables: 103  Queries per second avg: 0.158 11.8.2-MariaDB-1 from Debian
    encryption: active (3 keys)
    zabbix: multiflexi-server => zabbix.example.com
    telemetry: enabled (multiflexi, http://otel-collector:4318, http/json)
    executor: active
    scheduler: inactive
    timestamp: 2025-08-04T14:14:17+00:00

Field descriptions:

- **version-cli**: CLI version (branch or tag)
- **db-migration**: Latest database migration applied
- **php**: PHP version
- **os**: Operating system
- **memory**: Current PHP memory usage (bytes)
- **companies**: Number of companies in the system
- **apps**: Number of applications
- **runtemplates**: Number of runtemplates
- **topics**: Number of topics
- **credentials**: Number of credentials
- **credential types**: Number of credential types
- **database**: Database driver and connection info
- **encryption**: Encryption system status (see below)
- **zabbix**: Zabbix monitoring status (see below)
- **telemetry**: OpenTelemetry status (see below)
- **executor**: Status of the multiflexi-executor service
- **scheduler**: Status of the multiflexi-scheduler service
- **timestamp**: ISO 8601 timestamp of the status report

Encryption Status Values:

- **disabled**: Encryption is turned off (``DATA_ENCRYPTION_ENABLED=false``)
- **active (N keys)**: Encryption is working with N active encryption keys
- **broken (no master key)**: ``ENCRYPTION_MASTER_KEY`` not configured
- **broken (no active keys)**: Master key configured but no active keys in database
- **broken (table missing)**: ``encryption_keys`` table doesn't exist
- **unknown (error: ...)**: Database error occurred

Zabbix Status Values:

- **disabled**: Zabbix monitoring is not configured (no ``ZABBIX_SERVER``)
- **hostname => server**: Monitoring active, e.g. ``multiflexi-server => zabbix.example.com``
- Uses ``ZABBIX_HOST`` config or system hostname as monitored hostname

OpenTelemetry Status Values:

- **disabled**: OpenTelemetry is not enabled (``OTEL_ENABLED=false`` or not set)
- **enabled (service, endpoint, protocol)**: Active configuration, e.g. ``enabled (multiflexi, http://otel-collector:4318, http/json)``
- **enabled (SDK not installed)**: Enabled but OpenTelemetry PHP SDK is not installed

telemetry:test
--------------

Test OpenTelemetry metrics export functionality by sending test metrics to the configured OTLP endpoint.

.. code-block:: bash

    multiflexi-cli telemetry:test
    multiflexi-cli telemetry:test --endpoint http://custom:4318
    multiflexi-cli telemetry:test --disable-gauges

Options:
  -e, --endpoint     Override OTLP endpoint URL
  --disable-gauges   Disable observable gauges (test only counters/histograms)

This command:

1. Checks if OpenTelemetry is enabled (``OTEL_ENABLED=true``)
2. Displays current configuration (service name, endpoint, protocol)
3. Initializes the OTel Metrics Exporter
4. Sends test metrics:
   - Job start metric (job_id=99999)
   - Job end metrics (success and failure)
   - Observable gauges (jobs.running, applications.total, etc.)
5. Flushes metrics to the OTLP endpoint

Example output:

.. code-block:: text

    Testing OpenTelemetry Metrics Export

    Configuration:
      Service Name: multiflexi
      Endpoint: http://localhost:4318
      Protocol: http/json

    Initializing OTel Metrics Exporter...
    ✓ Exporter initialized successfully

    Testing job start metric...
    ✓ Job start metric recorded

    Testing job end metrics...
      ✓ Success metric (exitcode=0, duration=5.5s)
      ✓ Failure metric (exitcode=1, duration=2.3s)

    Testing observable gauges (real-time metrics)...
      ✓ multiflexi.jobs.running
      ✓ multiflexi.applications.total
      ✓ multiflexi.companies.total

    Flushing metrics to OTLP endpoint...
    ✓ Metrics flushed successfully

    Test completed successfully!

Available metrics:

- **Counters**: ``multiflexi.jobs.total``, ``multiflexi.jobs.success``, ``multiflexi.jobs.failed``
- **Histogram**: ``multiflexi.job.duration`` (seconds)
- **Gauges**: ``multiflexi.jobs.running``, ``multiflexi.applications.{total,enabled}``, ``multiflexi.companies.total``, ``multiflexi.runtemplates.total``

See the `OpenTelemetry documentation <https://multiflexi.readthedocs.io/en/latest/opentelemetry.html>`_ for complete integration guide.
