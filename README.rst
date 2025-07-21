MultiFlexi
----------

MultiFlexi CLI is part of a `MultiFlexi <https://multiflexi.eu>`_ suite.

.. image:: https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg
   :target: https://www.multiflexi.eu/
   :alt: MultiFlexi Logo


MultiFlexi CLI
==============

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

Internationalization
--------------------
All user-facing messages support translation using the i18n library. Use the ``_()`` function for translatable strings in code.

Documentation
-------------
For detailed documentation, see ``doc/multiflexi-cli.rst`` and the man page ``multiflexi-cli.1``.

License
-------
MultiFlexi CLI is licensed under the MIT License.
