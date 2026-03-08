<?php
// Debian autoloader for multiflexi-cli
// Load dependency autoloaders
require_once '/usr/share/php/MultiFlexi/autoload.php';
require_once '/usr/share/php/Symfony/Component/Process/autoload.php';
require_once '/usr/share/php/Symfony/Component/Console/autoload.php';
//require_once '/usr/share/php/JsonSchema/autoload.php';
require_once '/usr/share/php/LucidFrame/autoload.php';

require_once '/usr/lib/multiflexi-cli/Command/MultiFlexiCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/ApplicationCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/ArtifactCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/CompanyAppCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/CompanyCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/CredentialCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/CredentialProtoTypeCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/CredentialTypeCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/DescribeCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/EncryptionCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/EventRuleCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/EventSourceCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/JobCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/PruneCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/QueueCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/RunTemplateCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/StatusCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/TelemetryTestCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/TokenCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/UserCommand.php';
require_once '/usr/lib/multiflexi-cli/Command/UserDataErasureCommand.php';
require_once '/usr/lib/multiflexi-cli/MultiFlexi/Cli/DataRetentionCleanup.php';
require_once '/usr/lib/multiflexi-cli/MultiFlexi/Dummy.php';
