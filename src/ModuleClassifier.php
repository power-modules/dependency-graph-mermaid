<?php

declare(strict_types=1);

namespace Modular\DependencyGraph\Renderer\Mermaid;

use Modular\DependencyGraph\Graph\DependencyGraph;
use Modular\DependencyGraph\Graph\ModuleNode;

/**
 * Classifies modules into "infrastructure" vs "domain" using simple heuristics.
 */
final class ModuleClassifier
{
    /** @var string[] */
    private array $infraKeywords;
    /** @var string[] */
    private array $domainKeywords;

    /**
     * @param string[]|null $infraKeywords
     * @param string[]|null $domainKeywords
     */
    public function __construct(?array $infraKeywords = null, ?array $domainKeywords = null)
    {
        $this->infraKeywords = $infraKeywords ?? [
            'config','configuration','database','db','connection','query','repository','storage','cache','logger','log',
            'http','client','adapter','driver','queue','kafka','redis','mail','email','sms','notification','transport',
        ];
        $this->domainKeywords = $domainKeywords ?? [
            'user','account','profile','order','product','catalog','inventory','payment','billing','pricing','checkout',
            'cart','shipping','delivery','loyalty','search',
        ];
    }

    /**
     * @return array{infrastructure: array<string, ModuleNode>, domain: array<string, ModuleNode>}
     */
    public function classify(DependencyGraph $graph): array
    {
        $infra = [];
        $domain = [];

        $modules = $graph->getModules();

        // Precompute incoming degree per module
        $incoming = [];
        foreach ($modules as $class => $_) {
            $incoming[$class] = count($graph->getIncomingEdges($class));
        }

        foreach ($modules as $class => $node) {
            $scores = $this->score($node, $incoming[$class] ?? 0);
            if ($scores['infra'] > $scores['domain']) {
                $infra[$class] = $node;
            } else {
                // default ties to domain to avoid over-classifying infra
                $domain[$class] = $node;
            }
        }

        return [
            'infrastructure' => $infra,
            'domain' => $domain,
        ];
    }

    /**
     * @return array{infra:int,domain:int}
     */
    private function score(ModuleNode $node, int $incomingDegree): array
    {
        $infra = 0;
        $domain = 0;

        $imports = $node->getImportCount();
        $exports = $node->getExportCount();

        if ($imports === 0) {
            $infra += 3; // roots skew infra
        } else {
            $domain += 2;
        }

        if ($incomingDegree >= 2) {
            $infra += 2; // hubs skew infra
        }

        $name = strtolower($node->shortName);
        foreach ($this->infraKeywords as $kw) {
            if (str_contains($name, $kw)) {
                $infra += 1;

                break;
            }
        }
        foreach ($this->domainKeywords as $kw) {
            if (str_contains($name, $kw)) {
                $domain += 1;

                break;
            }
        }

        // Look at export names
        foreach ($node->exports as $export) {
            $short = strtolower($this->shortName($export));
            foreach ($this->infraKeywords as $kw) {
                if (str_contains($short, $kw)) {
                    $infra += 1;

                    break 2;
                }
            }
            foreach ($this->domainKeywords as $kw) {
                if (str_contains($short, $kw)) {
                    $domain += 1;

                    break 2;
                }
            }
        }

        // tiny nudge: modules with many exports are often domain services collections
        if ($exports >= 3) {
            $domain += 1;
        }

        return ['infra' => $infra, 'domain' => $domain];
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts) ?: $fqcn;
    }
}
