<?php
/**
 * @file  cli.php
 * @brief CLI route definitions
 */

use App\Cli\Colors;
use App\Cli\Enums\ForegroundColors;
use App\Controllers\Cli\Help;
use App\Controllers\Cli\Translations;
use Lsr\Core\Routing\CliRoute;
use Lsr\Helpers\Cli\CliHelper;

if (PHP_SAPI === 'cli') {

	CliRoute::cli('list', [Help::class, 'listCommands'])
					->description('Lists all available CLI commands.')
					->usage('[commandGroup]')
					->addArgument([
													'name'        => 'commandGroup',
													'isOptional'  => true,
													'description' => 'Optional filter for command groups',
												])
					->help(
						static function() {
							echo Colors::color(ForegroundColors::LIGHT_PURPLE).lang('Examples', context: 'cli.messages').':'.Colors::reset().PHP_EOL;
							echo Colors::color(ForegroundColors::LIGHT_BLUE).CliHelper::getCaller().' list'.Colors::reset().PHP_EOL."\tLists all available commands.".PHP_EOL;
							echo Colors::color(ForegroundColors::LIGHT_BLUE).CliHelper::getCaller().' list results'.Colors::reset().PHP_EOL."\tLists all available commands from the 'results' group (starting with 'results/').".PHP_EOL;
						}
					);

	CliRoute::cli('help', [Help::class, 'help'])
					->description('Print help for a command.')
					->usage('<command>')
					->addArgument([
													'name'        => 'command',
													'isOptional'  => false,
													'description' => 'A command to get information about.',
													'suggestions' => [
														'autocomplete/get',
														'list',
														'help',
														'results/load',
														'event/server',
													],
												])
					->help(
						static function() {
							Colors::color(ForegroundColors::LIGHT_PURPLE).lang('Examples', context: 'cli.messages').':'.Colors::reset().PHP_EOL;
							echo Colors::color(ForegroundColors::LIGHT_BLUE).CliHelper::getCaller().' help results/load'.Colors::reset().PHP_EOL."\tPrints out information about the command '".Colors::color(ForegroundColors::LIGHT_BLUE)."results/load".Colors::reset()."'".PHP_EOL;
						}
					);

	CliRoute::cli('autocomplete/get', [Help::class, 'generateAutocompleteJson'])
					->description('Generate an autocomplete JSON for all available commands.')
					->usage('[out]')
					->addArgument([
													'name'        => 'out',
													'isOptional'  => true,
													'description' => 'If set, output will be written to the [out] file. Otherwise, output will be written to stdout.',
													'template'    => 'filepaths',
												]);

	CliRoute::cli('translations/compile', [Translations::class, 'compile'])
					->description('Compile all translation files.');

	CliRoute::cli('translations/merge', [Translations::class, 'merge'])
					->description('Merge translations from this and one other project.')
					->usage('<dir> [contextSkip]')
					->addArgument([
													'name'        => 'dir',
													'isOptional'  => false,
													'description' => 'A language directory from the other project.',
													'template'    => 'filepaths',
												],
												[
													'name'        => 'contextSkip',
													'isOptional'  => true,
													'description' => 'A comma separated list of context names to skip while merging.',
												]);
}