
# MultiFlexi CLI Copilot Workspace Instructions

These instructions are designed to help Copilot generate code and documentation that matches the standards and requirements of the MultiFlexi CLI project. Please follow these rules for all code, tests, documentation, and messages:

## General Coding Standards
- Write all code in **PHP 8.4 or later**.
- Follow the **PSR-12 coding standard** for all PHP code.
- Use **meaningful variable names** that clearly describe their purpose.
- Avoid magic numbers and strings; define constants instead.
- Always include **type hints** for function parameters and return types.
- Handle exceptions properly and provide **clear, meaningful error messages** (in English).
- Ensure code is **secure** and does not expose sensitive information.
- Optimize for **performance** where necessary.
- Ensure compatibility with the **latest version of PHP** and all used libraries, especially VitexSoftware libraries.
- Use the **i18n library** for internationalization; wrap translatable strings with the `_()` function.

## Documentation and Comments
- Write all code comments and messages in **English**.
- Use complete sentences and proper grammar for comments.
- Include a **docblock** for every function and class, describing its purpose, parameters, and return types.
- Document complex logic or important decisions with inline comments.

## Output and Formatting
- For all commands, the **default output format** is human-readable text.
- When the user specifies `--format json`, return all output (including errors, status, and results) in **JSON format**.
- Never return JSON by default unless `--format json` is explicitly requested.

## Testing and Validation
- Use **PHPUnit** for all tests and follow PSR-12 for test code.
- When creating or updating a class, always create or update its PHPUnit test file.
- After every PHP file edit, run `php -l` on the edited file to check for syntax errors before proceeding further.

## Project Documentation
- Write **README** files in **Markdown** format.
- Write technical documentation in **reStructuredText (reST)** format.
- Use **concise, imperative mood** for commit messages.

## CLI Command Features
- For all list, get, delete, and create commands, return a JSON response with the result when `--format json` is used.
- Keep the `tests/test-cli.sh` file up to date with features provided by the `multiflexi-cli describe` command output.
- In `tests/test-cli.sh`, keep delete action tests at the very bottom in a separate section.
- Update `multiflexi-cli.rst` and `multiflexi-cli.1` files whenever CLI features change.
- Keep `manpage/multiflexi-cli.1` up to date with the latest CLI changes.
- Ensure `--help` output is clear, concise, and provides all necessary usage and option information.

## Development and Deployment
- When developing or testing, always run the main script from the `src/` directory:
	```bash
	cd src/
	php multiflexi-cli.php
	```
- Relative paths (e.g., `../vendor/autoload.php`, `../.env`) are intentional and resolved during Debian packaging.

---
**Summary:**
Always write code that is maintainable, well-tested, secure, and follows best practices. Document your work clearly, validate all changes, and keep all related files and documentation up to date with feature changes.
