<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_phpcs_product\Integration;

/**
 * @group marvin_product
 * @group marvin_phpcs_product
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin_phpcs_product\PhpcsCommands
 */
class MarvinLintPhpcsTest extends UnishIntegrationTestCase {

  public function testLintPhpcsHelp(): void {
    $expected = [
      'stdError' => '',
      'stdOutput' => 'Runs PHP Code Sniffer.',
      'exitCode' => 0,
    ];

    $args = [];
    $options = $this->getCommonCommandLineOptions();
    $options['help'] = NULL;

    $this->drush(
      'marvin:lint:phpcs',
      $args,
      $options,
      NULL,
      NULL,
      $expected['exitCode'],
      NULL,
      $this->getCommonCommandLineEnvVars(),
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertStringContainsString($expected['stdError'], $actualStdError, 'StdError');
    static::assertStringContainsString($expected['stdOutput'], $actualStdOutput, 'StdOutput');
  }

}
