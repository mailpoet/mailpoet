<?php // phpcs:ignoreFile

// throw exception if anything fails.
set_error_handler(
	function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

$mailpoetRootDir = dirname( __DIR__, 4 );

$emailEditorPhpDir = dirname( __DIR__, 1 );

$phpStanDir = "$mailpoetRootDir/mailpoet/tasks/phpstan";
$phpStanBin = "$phpStanDir/vendor/bin/phpstan";

$emailEditorCustomConfig = "$phpStanDir/email-editor-phpstan.neon";


$extraAgrPhpVersion = '';
if ( $argc > 1 && isset( $argv[1] ) && stripos( $argv[1], 'php-version' ) !== false ) {
	$rawArgv            = explode( '=', escapeshellcmd( $argv[1] ) );
	$value              = $rawArgv[1];
	$extraAgrPhpVersion = "ANALYSIS_PHP_VERSION=$value ";
}

$commands = array(
	"cd $phpStanDir && ", // we run commands from the PHPStan dir because we save MailPoet-specific configuration in it
	"$extraAgrPhpVersion",
	'php -d memory_limit=-1 ',
	"$phpStanBin analyse ",
	"-c $emailEditorCustomConfig ",
	"$emailEditorPhpDir/src ",
	"$emailEditorPhpDir/tests/integration ",
	"$emailEditorPhpDir/tests/unit ",
);

$allCommands = implode( ' ', $commands );

echo "[run-phpstan] Running command: $allCommands \n";

passthru( $allCommands );
