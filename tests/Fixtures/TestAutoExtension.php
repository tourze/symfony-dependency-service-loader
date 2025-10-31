<?php

namespace Tourze\SymfonyDependencyServiceLoader\Tests\Fixtures;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * 测试用的 AutoExtension 实现
 */
class TestAutoExtension extends AutoExtension
{
    public function __construct(private ?string $configDir = null)
    {
    }

    protected function getConfigDir(): string
    {
        return $this->configDir ?? __DIR__ . '/config';
    }

    public function getAlias(): string
    {
        return 'test_auto_extension';
    }
}
