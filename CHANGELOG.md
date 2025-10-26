# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- Fixed `--file` option usage in ApplicationCommand for JSON operations
  - Changed from `--json` to `--file` for: `import-json`, `export-json`, `remove-json`, `validate-json` actions
  - Fixes consistency with option definition (line 53)
  - Resolves issue where commands failed with "Missing --json" error

### Usage
All JSON-related application commands now use `--file` instead of `--json`:

```bash
multiflexi-cli application import-json --file=app.json
multiflexi-cli application export-json --id=123 --file=output.json
multiflexi-cli application validate-json --file=app.json
multiflexi-cli application remove-json --file=app.json
```
