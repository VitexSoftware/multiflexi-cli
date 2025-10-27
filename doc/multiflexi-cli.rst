.. _multiflexi-cli:

MultiFlexi CLI
==============

The MultiFlexi CLI is a powerful Symfony Console-based command line interface for comprehensive management of MultiFlexi resources. It provides full CRUD operations for all system entities and supports both text and JSON output formats for automation and scripting.

Installation
------------

The CLI is included with MultiFlexi and available as:

.. code-block:: bash

    # System-wide installation
    multiflexi-cli <command> [action] [options]
    
    # Local installation
    ./cli/multiflexi-cli <command> [action] [options]

General Usage
-------------

.. code-block:: bash

    multiflexi-cli <command> [action] [options]

**Global Options:**

- ``-f, --format`` - Output format: text or json (default: text)
- ``-v, --verbose`` - Increase verbosity (use -vv or -vvv for more detail)
- ``--no-ansi`` - Disable colored output
- ``-h, --help`` - Display help for the command
- ``-V, --version`` - Display application version

**Environment Configuration:**

Use the ``-e`` or ``--environment`` option to specify a custom .env file:

.. code-block:: bash

    multiflexi-cli -e /path/to/custom/.env command action


Commands Overview
-----------------

The MultiFlexi CLI provides the following main commands:

- **application**   - Manage applications (import/export/remove JSON, show configuration fields)
- **company**       - Manage companies and their settings
- **companyapp**    - Manage company-application relations
- **job**           - Manage job execution and monitoring
- **runtemplate**   - Manage run templates and scheduling
- **user**          - User account management
- **user:data-erasure** - GDPR user data erasure management
- **token**         - API token management
- **credtype**      - Credential type operations
- **encryption**    - Manage encryption keys
- **queue**         - Job queue operations
- **appstatus**     - System status information
- **describe**      - List all available commands and their parameters
- **prune**         - Prune logs and jobs, keeping only the latest N records (default: 1000)
- **completion**    - Dump the shell completion script

Detailed Command Reference
-------------------------

.. contents::
   :local:
   :depth: 2


application
-----------

Manage applications (list, get, create, update, delete, import/export/remove JSON, show configuration fields).

.. code-block:: bash

    multiflexi-cli application <action> [options]

Actions:
- list:         List all applications.
- get:          Get application details by ID, UUID, or name.
- create:       Create a new application (requires --name, --uuid).
- update:       Update an existing application (requires --id or --uuid).
- delete:       Delete an application (requires --id).
- import-json:  Import application from JSON file (requires --json).
- export-json:  Export application to JSON file (requires --id, --json).
- remove-json:  Remove application from JSON file (requires --json).
- showconfig:   Show defined configuration fields for application (requires --id or --uuid).

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
  --json         Path to JSON file for import/export/remove
  --appversion   Application Version
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli application list
    multiflexi-cli application get --id=1
    multiflexi-cli application get --uuid=uuid-123
    multiflexi-cli application get --name="App1"
    multiflexi-cli application create --name="App1" --uuid="uuid-123"
    multiflexi-cli application update --id=1 --name="App1 Updated"
    multiflexi-cli application delete --id=1
    multiflexi-cli application import-json --json=app.json
    multiflexi-cli application export-json --id=1 --json=app.json
    multiflexi-cli application showconfig --id=1

companyapp
----------

Manage company-application relations (list, get, create, update, delete).

.. code-block:: bash

    multiflexi-cli companyapp <action> [options]

Actions:
- list:   List company-app relations (requires --company_id and --app_id or --app_uuid).
- get:    Get relation details by ID.
- create: Create a new relation (requires --company_id and --app_id).
- update: Update an existing relation (requires --id).
- delete: Delete a relation (requires --id).

Options:
  --id           Relation ID
  --company_id   Company ID
  --app_id       Application ID
  --app_uuid     Application UUID
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli companyapp list --company_id=1 --app_id=2
    multiflexi-cli companyapp create --company_id=1 --app_id=2
    multiflexi-cli companyapp delete --id=5

credtype
--------

Credential type operations (list, get, update).

.. code-block:: bash

    multiflexi-cli credtype <action> [options]

Actions:
- list:   List all credential types.
- get:    Get credential type details by ID or UUID.
- update: Update a credential type (requires --id or --uuid).

Options:
  --id           Credential Type ID
  --uuid         Credential Type UUID
  --name         Name
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli credtype list
    multiflexi-cli credtype get --id=1
    multiflexi-cli credtype update --id=1 --name="API Key"

company
-------

Manage companies (list, get, create, update, remove).

.. code-block:: bash

    multiflexi-cli company <action> [options]

Actions:
- list:   List all companies.
- get:    Get company details by ID.
- create: Create a new company (requires --name).
- update: Update an existing company (requires --id).
- remove: Remove a company (requires --id).

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

    multiflexi-cli company list
    multiflexi-cli company get --id=1
    multiflexi-cli company create --name="Acme Corp" --customer="CustomerX"
    multiflexi-cli company remove --id=1

job
---

Manage jobs (list, get, create, update, delete).

.. code-block:: bash

    multiflexi-cli job <action> [options]

Actions:
- list:   List all jobs.
- get:    Get job details by ID.
- create: Create a new job (requires --runtemplate_id and --scheduled).
- update: Update an existing job (requires --id).
- delete: Delete a job by its ID.

Options:
  --id           Job ID
  --runtemplate_id RunTemplate ID
  --scheduled    Scheduled datetime
  --executor     Executor
  --schedule_type Schedule type
  --app_id       App ID
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli job list
    multiflexi-cli job get --id=123
    multiflexi-cli job create --runtemplate_id=5 --scheduled="2024-07-01 12:00"
    multiflexi-cli job update --id=123 --executor=Native
    multiflexi-cli job delete --id=123

runtemplate
-----------

Manage runtemplates (list, get, create, update, delete, schedule).

.. code-block:: bash

    multiflexi-cli runtemplate <action> [options]

Actions:
- list:   List all runtemplates.
- get:    Get runtemplate details by ID.
- create: Create a new runtemplate (requires --name, --app_id, --company_id).
- update: Update an existing runtemplate (requires --id).
- delete: Delete a runtemplate (requires --id).
- schedule: Schedule a runtemplate launch as a job (requires --id).

Options:
  --id           RunTemplate ID
  --name         Name
  --app_id       App ID
  --company_id   Company ID
  --interv       Interval code
  --active       Active
  --config       Application config key=value (repeatable)
  --schedule_time Schedule time for launch (Y-m-d H:i:s or "now")
  --executor     Executor to use for launch
  --env          Environment override key=value (repeatable)
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli runtemplate create --name="Import Yesterday" --app_id=19 --company_id=1 --config=IMPORT_SCOPE=yesterday --config=ANOTHER_KEY=foo
    multiflexi-cli runtemplate update --id=230 --config=IMPORT_SCOPE=yesterday --config=ANOTHER_KEY=foo
    multiflexi-cli runtemplate get --id=230 --format=json
    multiflexi-cli runtemplate create --name="Import" --app_id=6e2b2c2e-7c2a-4b1a-8e2d-123456789abc --company_id=1
    multiflexi-cli runtemplate schedule --id=123 --schedule_time="2025-07-01 10:00:00" --executor=Native --env=FOO=bar --env=BAZ=qux

user
----

Manage users (list, get, create, update, delete).

.. code-block:: bash

    multiflexi-cli user <action> [options]

Actions:
- list:   List all users.
- get:    Get user details by ID.
- create: Create a new user (requires --login, --firstname, --lastname, --email, --password).
- update: Update an existing user (requires --id).
- delete: Delete a user (requires --id).

Options:
  --id           User ID
  --login        Login
  --firstname    First name
  --lastname     Last name
  --email        Email
  --password     Password
  --enabled      Enabled (true/false)
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli user list
    multiflexi-cli user get --id=1
    multiflexi-cli user create --login="jsmith" --firstname="John" --lastname="Smith" --email="jsmith@example.com" --password="secret"
    multiflexi-cli user update --id=1 --email="john.smith@example.com"
    multiflexi-cli user delete --id=1

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

Manage tokens (list, get, create, generate, update).

.. code-block:: bash

    multiflexi-cli token <action> [options]

Actions:
- list:   List all tokens.
- get:    Get token details by ID.
- create: Create a new token (requires --user).
- generate: Generate a new token value (requires --user).
- update: Update an existing token (requires --id).

Options:
  --id           Token ID
  --user         User ID
  --token        Token value
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli token list
    multiflexi-cli token get --id=1
    multiflexi-cli token create --user=2
    multiflexi-cli token generate --user=2
    multiflexi-cli token update --id=1 --token=NEWVALUE

encryption
----------

Manage encryption keys for secure credential storage.

.. code-block:: bash

    multiflexi-cli encryption <action> [options]

Actions:
- init: Re-initialize encryption keys (generates new 256-bit key for AES-256-GCM)

Options:
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    # Re-initialize encryption keys
    multiflexi-cli encryption init
    
    # Re-initialize with JSON output
    multiflexi-cli encryption init -f json

**Warning**: Re-initializing encryption keys will invalidate all previously encrypted credentials. 
Use this command only during initial setup or when explicitly required for security reasons.

queue
-----

Queue operations (list, truncate).

.. code-block:: bash

    multiflexi-cli queue <action> [options]

Actions:
- list:     Show all scheduled jobs in the queue.
- truncate: Remove all scheduled jobs from the queue.

Options:
  -f, --format   Output format: text or json (default: text)

Examples:

.. code-block:: bash

    multiflexi-cli queue list -f json
    multiflexi-cli queue truncate -f json

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


appstatus
---------

Show current MultiFlexi system status, including version, database, PHP, OS, resource usage, and service health.

.. code-block:: bash

    multiflexi-cli appstatus

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
- **executor**: Status of the multiflexi-executor service
- **scheduler**: Status of the multiflexi-scheduler service
- **timestamp**: ISO 8601 timestamp of the status report
