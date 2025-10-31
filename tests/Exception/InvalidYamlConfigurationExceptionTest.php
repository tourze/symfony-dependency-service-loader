<?php

namespace Tourze\SymfonyDependencyServiceLoader\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SymfonyDependencyServiceLoader\Exception\InvalidYamlConfigurationException;

/**
 * @internal
 */
#[CoversClass(InvalidYamlConfigurationException::class)]
class InvalidYamlConfigurationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritsFromInvalidArgumentException(): void
    {
        $exception = new InvalidYamlConfigurationException('test message');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertSame('test message', $exception->getMessage());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(InvalidYamlConfigurationException::class);
        $this->expectExceptionMessage('配置文件错误');

        throw new InvalidYamlConfigurationException('配置文件错误');
    }
}
