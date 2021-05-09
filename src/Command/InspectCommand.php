<?php

/**
 * @see       https://github.com/laminas/laminas-servicemanager-inspector for the canonical source repository
 * @copyright https://github.com/laminas/laminas-servicemanager-inspector/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-servicemanager-inspector/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\ServiceManager\Inspector\Command;

use Laminas\ServiceManager\Inspector\Dependency\Dependency;
use Laminas\ServiceManager\Inspector\DependencyConfig\DependencyConfigInterface;
use Laminas\ServiceManager\Inspector\EventCollector\EventCollectorInterface;
use Laminas\ServiceManager\Inspector\EventReporter\EventReporterInterface;
use Laminas\ServiceManager\Inspector\Scanner\DependencyScannerInterface;
use Laminas\ServiceManager\Inspector\Traverser\TraverserInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class InspectCommand extends Command
{
    public const HELP = <<<'EOH'
TODO
EOH;

    /** @var string|null $defaultName */
    public static $defaultName = 'servicemanager:inspect';

    /** @var DependencyConfigInterface */
    private $config;

    /** @var DependencyScannerInterface */
    private $dependencyScanner;

    /** @var TraverserInterface */
    private $traverser;

    /** @var EventCollectorInterface */
    private $eventCollector;

    /** @var EventReporterInterface */
    private $eventReporter;

    public function __construct(
        DependencyConfigInterface $config,
        DependencyScannerInterface $dependencyScanner,
        TraverserInterface $traverser,
        EventCollectorInterface $eventCollector,
        EventReporterInterface $eventReporter
    ) {
        $this->config            = $config;
        $this->dependencyScanner = $dependencyScanner;
        $this->traverser         = $traverser;
        $this->eventCollector    = $eventCollector;
        $this->eventReporter     = $eventReporter;

        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this->setDescription('ServiceManager inspector');
        $this->setHelp(self::HELP);
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->scan();

        foreach ($this->config->releaseEvents() as $event) {
            $this->eventCollector->collect($event);
        }

        $exitCode = $this->eventCollector->hasTerminalEvent() ? 1 : 0;

        $events = $this->eventCollector->release();

        ($this->eventReporter)($events, $output);

        return $exitCode;
    }

    private function scan(): void
    {
        foreach ($this->config->getFactories() as $serviceName => $factoryClass) {
            if ($this->dependencyScanner->canScan($serviceName)) {
                // TODO don't fail here - collect all occurring errors
                ($this->traverser)(new Dependency($serviceName));
            }
        }
    }
}
