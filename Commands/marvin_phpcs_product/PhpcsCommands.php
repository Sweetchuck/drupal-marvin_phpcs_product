<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_phpcs_product;

use Drush\Commands\marvin_phpcs\PhpcsCommandsBase;
use Robo\Collection\CollectionBuilder;

class PhpcsCommands extends PhpcsCommandsBase {

  /**
   * @hook on-event marvin:git-hook:pre-commit
   */
  public function onEventMarvinGitHookPreCommit(): array {
    return [
      'marvin_phpcs_product.lint_phpcs_extension' => [
        'weight' => -200,
        'task' => $this->getTaskLintPhpcsExtension($this->getProjectRootDir()),
      ],
    ];
  }

  /**
   * @hook on-event marvin:lint
   */
  public function onEventMarvinLint(): array {
    return [
      'marvin_phpcs_product.lint_phpcs_extension' => [
        'weight' => -200,
        'task' => $this->getTaskLintPhpcsExtension($this->getProjectRootDir()),
      ],
    ];
  }

  /**
   * Runs PHP Code Sniffer.
   *
   * @command marvin:lint:phpcs
   *
   * @bootstrap none
   *
   * @marvinInitLintReporters
   */
  public function cmdLintPhpcsExecute(): CollectionBuilder {
    return $this->getTaskLintPhpcsExtension('.');
  }

}
