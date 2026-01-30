<?php

declare(strict_types=1);

namespace Faez84\DoctrinePerformanceGuardBundle\EventSubscriber;

use Faez84\DoctrinePerformanceGuardBundle\Collector\QueryLog;
use Faez84\DoctrinePerformanceGuardBundle\Detector\NPlusOneDetector;
use Faez84\DoctrinePerformanceGuardBundle\Exception\DoctrinePerformanceGuardException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class GuardSubscriber implements EventSubscriberInterface
{
    /**
     * @param string[] $applyToPaths
     */
    public function __construct(
        private QueryLog $log,
        private NPlusOneDetector $nPlusOneDetector,
        private bool $enabled,
        private string $mode, // log|exception
        private int $maxQueries,
        private int $maxTotalTimeMs,
        private int $maxDuplicateQueryCount,
        private bool $addDebugHeaders,
        private array $applyToPaths,
        private LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
            KernelEvents::TERMINATE => ['onKernelTerminate', -10],

            ConsoleEvents::COMMAND => ['onConsoleCommand', 100],
            ConsoleEvents::TERMINATE => ['onConsoleTerminate', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (!$this->appliesToPath($path)) {
            return;
        }

        $this->log->reset();
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest() || !$this->addDebugHeaders) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (!$this->appliesToPath($path)) {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isTextLikeResponse($response)) {
            return;
        }

        $response->headers->set('X-Doctrine-Queries', (string) $this->log->count());
        $response->headers->set('X-Doctrine-Time-ms', (string) (int) round($this->log->totalMs()));
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (!$this->appliesToPath($path)) {
            return;
        }

        $this->evaluateAndAct('http ' . $path);
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->log->reset();
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $cmd = $event->getCommand()?->getName() ?? 'unknown-command';
        $this->evaluateAndAct('console ' . $cmd);
    }

    private function evaluateAndAct(string $context): void
    {
        $count = $this->log->count();
        $totalMs = (int) round($this->log->totalMs());
        $n1 = $this->nPlusOneDetector->detect($this->log->all(), $this->maxDuplicateQueryCount);

        $problems = [];

        if ($this->maxQueries > 0 && $count > $this->maxQueries) {
            $problems[] = sprintf('Too many queries: %d (limit %d)', $count, $this->maxQueries);
        }

        if ($this->maxTotalTimeMs > 0 && $totalMs > $this->maxTotalTimeMs) {
            $problems[] = sprintf('Total query time too high: %dms (limit %dms)', $totalMs, $this->maxTotalTimeMs);
        }

        if ($n1 !== []) {
            $top = $n1[0];
            $problems[] = sprintf(
                'Potential N+1 / duplicate query shape: repeated %d times (threshold %d). Example: %s',
                $top['count'],
                $this->maxDuplicateQueryCount,
                $this->shorten($top['example'])
            );
        }

        if ($problems === []) {
            return;
        }

        $message = '[DoctrinePerformanceGuard] ' . $context . ' | ' . implode(' | ', $problems);

        if ($this->mode === 'exception') {
            throw new DoctrinePerformanceGuardException($message);
        }

        $this->logger->warning($message, [
            'context' => $context,
            'query_count' => $count,
            'total_ms' => $totalMs,
            'nplusone_hits' => $n1,
        ]);
    }

    private function appliesToPath(string $path): bool
    {
        if ($this->applyToPaths === []) {
            return true;
        }

        foreach ($this->applyToPaths as $prefix) {
            if ($prefix !== '' && str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isTextLikeResponse(Response $response): bool
    {
        // avoid setting headers on binary/file downloads
        $ct = $response->headers->get('Content-Type', '');
        return $ct === '' || stripos($ct, 'text/') !== false || stripos($ct, 'json') !== false || stripos($ct, 'xml') !== false;
    }

    private function shorten(string $sql, int $max = 220): string
    {
        $s = preg_replace('/\s+/', ' ', $sql ?? '') ?? $sql;
        $s = trim($s);
        if (mb_strlen($s) <= $max) {
            return $s;
        }
        return mb_substr($s, 0, $max - 3) . '...';
    }
}
