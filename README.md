# MultiFlexi CLI

[![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/VitexSoftware/multiflexi-cli?utm_source=oss&utm_medium=github&utm_campaign=VitexSoftware%2Fmultiflexi-cli&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)](https://coderabbit.ai)

MultiFlexi CLI (`multiflexi-cli`) is a command-line tool for managing MultiFlexi applications, jobs, users, companies, and more. It is designed to provide flexible automation and integration for MultiFlexi server environments.

## Features
- List, create, get, and delete entities such as applications, jobs, users, companies, and credentials.
- Query application and job status.
- Manage templates, tokens, and queues.
- **JSON Import/Export**: Import applications and credential types from JSON files, export configurations to JSON.
- **Encryption management**: Check status and initialize encryption keys for secure credential storage.
- Prune obsolete data.
- Internationalization support (i18n).
- **Flexible output formats**: Human-readable text output by default, with optional JSON output for integration (`--format json`).

## Usage

```bash
multiflexi-cli <command> [options]
```

## Common Commands

- `list`: List entities (apps, jobs, users, etc.)
- `get`: Get details of a specific entity
- `create`: Create a new entity
- `delete`: Delete an entity
- `describe`: Show available commands and features
- `status`: Show MultiFlexi status (includes encryption, Zabbix, OpenTelemetry)
- `application import-json`: Import application from JSON file
- `credtype import-json`: Import credential type from JSON file
- `telemetry:test`: Test OpenTelemetry metrics export
- `encryption`: Manage encryption keys (status, init)
- `prune`: Remove obsolete data
- `queue`: Manage job queue (list with sorting/filtering, truncate, overview metrics)
- `token`: Manage authentication tokens

## Output Formats

By default, all commands output human-readable text format suitable for terminal viewing. For programmatic integration or automation, you can request JSON format using the `--format json` option.

## Options

- `--format json`: Output results in JSON format (default is human-readable text)
- `--help`: Show help for a command

## Examples

```bash
multiflexi-cli list apps --format json
multiflexi-cli get job 123
multiflexi-cli create user --login "jsmith" --email "john@example.com"
multiflexi-cli delete app 456
multiflexi-cli describe

# JSON Import/Export
multiflexi-cli application import-json --file app-definition.json
multiflexi-cli credtype import-json --file credential-type.json
multiflexi-cli credtype validate-json --file credential-type.json

# System status (includes encryption, Zabbix, OpenTelemetry)
multiflexi-cli status
multiflexi-cli status --format json

# Queue management with enhanced features
multiflexi-cli queue                                    # Show queue overview metrics
multiflexi-cli queue list --order after --limit 10     # List jobs by scheduled time
multiflexi-cli queue list --order after --direction DESC --limit 5  # Latest jobs first
multiflexi-cli queue list --fields "id,after,schedule_type" --format json  # Custom fields JSON

# Credential type management
multiflexi-cli credtype list
multiflexi-cli credtype get --uuid "d3d3ae58-d64a-4ab4-afb5-ba439ffc8587"

# Encryption management
multiflexi-cli encryption status

# OpenTelemetry testing
multiflexi-cli telemetry:test
```

```bash
EASE_LOGGER=console multiflexi-cli remove app 15
02/20/2024 23:48:51 🌼 ❲MultiFlexi cli⦒(15)AbraFlexi send@MultiFlexi\Application❳ Unassigned from 3 companys
02/20/2024 23:48:53 🌼 ❲MultiFlexi cli⦒(15)AbraFlexi send@MultiFlexi\Application❳ 2 RunTemplate removal
02/20/2024 23:48:56 🌼 ❲MultiFlexi cli⦒(15)AbraFlexi send@MultiFlexi\Application❳ 2 Config fields removed
02/20/2024 23:48:57 🌼 ❲MultiFlexi cli⦒(15)AbraFlexi send@MultiFlexi\Application❳ 881 Jobs removed
Done.
```

## Documentation
For detailed documentation, see [`doc/multiflexi-cli.rst`](doc/multiflexi-cli.rst) and the man page `multiflexi-cli.1`.

## MultiFlexi

MultiFlexi CLI is part of a [MultiFlexi](https://multiflexi.eu) suite.

[![MultiFlexi Logo](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/)

## License
MultiFlexi CLI is licensed under the MIT License.
