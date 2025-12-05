<?php

declare(strict_types=1);

namespace Drush\Commands\marvin_phpcs_product;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\ContainerInitializer;
use Drupal\marvin\Utils;
use Drupal\marvin_git\GitHook\CommandEvent as GitHookCommandEvent;
use Drupal\marvin\Lint\CommandEvent as LintCommandEvent;
use Drupal\marvin_phpcs\PhpcsCommandTrait;
use Drush\Attributes\Bootstrap as CliBootstrap;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\AutowireTrait;
use Drush\Config\DrushConfig;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\DefinitionContainerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
  name: self::NAME,
  description: 'Runs PHPCS on the codebase.',
)]
#[CliBootstrap(level: DrupalBootLevels::NONE)]
final class MarvinLintPhpcsCommand extends Command implements BuilderAwareInterface, ContainerAwareInterface {

  use ContainerAwareTrait;
  use AutowireTrait {
    create as protected autowireCreate;
  }
  use TaskAccessor;
  use PhpcsCommandTrait;

  public const string NAME = 'marvin:lint:phpcs';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    ContainerInitializer::initialize($container);

    return self::autowireCreate($container);
  }

  public function __construct(
    #[Autowire('config')]
    protected DrushConfig $drushConfig,
    #[Autowire(Utils::class)]
    protected Utils $utils,
    #[Autowire('logger')]
    protected LoggerInterface $logger,
    #[Autowire('eventDispatcher')]
    protected EventDispatcherInterface $eventDispatcher,
    #[Autowire('container')]
    DefinitionContainerInterface $container,
  ) {
    $this->setContainer($container);
    parent::__construct();

    $this->eventDispatcher->addListener(
      LintCommandEvent::EVENT_RUN_TASKS_COLLECT,
      $this->onEventMarvinLintTasksCollect(...),
    );

    if (class_exists(GitHookCommandEvent::class)) {
      $this->eventDispatcher->addListener(
        GitHookCommandEvent::EVENT_PRE_COMMIT_TASKS_COLLECT,
        $this->onEventMarvinGitHookPreCommitTasksCollect(...),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getLinterInfo(): array {
    return [
      'id' => 'phpcs',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $workingDirectory = '.';
    $composerFilePath = $this->utils->getComposerJsonFileName();
    $composerInfo = ComposerInfo::create($workingDirectory, $composerFilePath);
    $reportsDir = $this->drushConfig->get('marvin.reportsDir');
    $gitHookName = NULL;

    $result = $this
      ->getTaskLintPhpcsExtension(
        $workingDirectory,
        $composerInfo['config']['bin-dir'],
        $reportsDir,
        $gitHookName,
      )
      ->run();
    if (!$result->wasSuccessful()) {
      $this->logger->error($result->getMessage());

      return Command::FAILURE;
    }

    return Command::SUCCESS;
  }

  public function onEventMarvinLintTasksCollect(LintCommandEvent $event): void {
    $workingDirectory = '.';
    $composerFilePath = $this->utils->getComposerJsonFileName();
    $composerInfo = ComposerInfo::create($workingDirectory, $composerFilePath);
    $reportsDir = $this->drushConfig->get('marvin.reportsDir');

    $event->taskDefinitions['Invoke-Phpcs.marvin_phpcs_product'] = [
      'weight' => 20,
      'provider' => 'marvin_phpcs_product',
      'description' => 'Runs PHPCS on the codebase.',
      'task' => $this->getTaskLintPhpcsExtension(
        $workingDirectory,
        $composerInfo['config']['bin-dir'],
        $reportsDir,
        $event->gitHookName,
      ),
    ];
  }

  public function onEventMarvinGitHookPreCommitTasksCollect(GitHookCommandEvent $event): void {
    $workingDirectory = '.';
    $composerFilePath = $this->utils->getComposerJsonFileName();
    $composerInfo = ComposerInfo::create($workingDirectory, $composerFilePath);
    $reportsDir = $this->drushConfig->get('marvin.reportsDir');

    $event->taskDefinitions['Invoke-Phpcs.marvin_phpcs_product'] = [
      'weight' => 20,
      'provider' => 'marvin_phpcs_product',
      'description' => 'Runs PHPCS on the changed files.',
      'task' => $this->getTaskLintPhpcsExtension(
        $workingDirectory,
        $composerInfo['config']['bin-dir'],
        $reportsDir,
        $event->gitHookName,
      ),
    ];
  }

}
