==============
.. image:: https://img.shields.io/coderabbit/prs/github/VitexSoftware/multiflexi-cli?utm_source=oss&utm_medium=github&utm_campaign=VitexSoftware%2Fmultiflexi-cli&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews
   :target: https://coderabbit.ai
   :alt: CodeRabbit Pull Request Reviews

MultiFlexi CLI

MultiFlexi CLI (`multiflexi-cli`) is a command-line tool for managing MultiFlexi applications, jobs, users, companies, and more. It is designed to provide flexible automation and integration for MultiFlexi server environments.

Features
--------
- List, create, get, and delete entities such as applications, jobs, users, companies, and credentials.
- Query application and job status.
- Manage templates, tokens, and queues.
- Prune obsolete data.
- Internationalization support (i18n).
- JSON output for easy integration with other tools (`--format json`).

Usage
-----

.. code-block:: bash

   multiflexi-cli <command> [options]

Common Commands
---------------

- ``list``: List entities (apps, jobs, users, etc.)
- ``get``: Get details of a specific entity
- ``create``: Create a new entity
- ``delete``: Delete an entity
- ``describe``: Show available commands and features
- ``appstatus``: Show application status
- ``prune``: Remove obsolete data
- ``queue``: Manage job queue
- ``token``: Manage authentication tokens

Options
-------

- ``--format json``: Output results in JSON format
- ``--help``: Show help for a command

Examples
--------

.. code-block:: bash

   multiflexi-cli list apps --format json
   multiflexi-cli get job 123
   multiflexi-cli create user --name "John Doe" --email "john@example.com"
   multiflexi-cli delete app 456
   multiflexi-cli describe



.. code-block:: bash
   EASE_LOGGER=console multiflexi-cli remove app 15
   02/20/2024 23:48:51 🌼 ❲MultiFlexi cli⦒(15)AbraFlexi send@MultiFlexi\Application❳ Unassigned from 3 companys
   02/20/2024 23:48:53 🌼 ❲MultiFlexi cli⦒(15)AbraFlexi send@MultiFlexi\Application❳ 2 RunTemplate removal
   02/20/2024 23:48:56 🌼 ❲MultiFlexi cli⦒(15)AbraFlexi send@MultiFlexi\Application❳ 2 Config fields removed
   02/20/2024 23:48:57 🌼 ❲MultiFlexi cli⦒(15)AbraFlexi send@MultiFlexi\Application❳ 881 Jobs removed
   Done.


Documentation
-------------
For detailed documentation, see ``doc/multiflexi-cli.rst`` and the man page ``multiflexi-cli.1``.

MultiFlexi
----------

MultiFlexi CLI is part of a `MultiFlexi <https://multiflexi.eu>`_ suite.

.. image:: https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg
   :target: https://www.multiflexi.eu/
   :alt: MultiFlexi Logo


License
-------
MultiFlexi CLI is licensed under the MIT License.
