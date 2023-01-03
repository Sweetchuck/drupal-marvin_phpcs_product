<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_phpcs_product;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\marvin\Attributes as MarvinCLI;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\marvin_phpcs\PhpcsCommandsBase;
use Robo\Contract\TaskInterface;

class PhpcsCommands extends PhpcsCommandsBase {

  /**
   * @phpstan-return array<string, mixed>
   */
  #[CLI\Hook(
    type: HookManager::ON_EVENT,
    target: 'marvin:git-hook:pre-commit',
  )]
  public function onEventMarvinGitHookPreCommit(): array {
    return [
      'marvin_phpcs_product.lint_phpcs_extension' => [
        'weight' => -200,
        'task' => $this->getTaskLintPhpcsExtension($this->getProjectRootDir()),
      ],
    ];
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  #[CLI\Hook(
    type: HookManager::ON_EVENT,
    target: 'marvin:lint',
  )]
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
   */
  #[CLI\Command(name: 'marvin:lint:phpcs')]
  #[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
  #[MarvinCLI\PreCommandInitLintReporters]
  public function cmdLintPhpcsExecute(): TaskInterface {
    return $this->getTaskLintPhpcsExtension('.');
  }

}
