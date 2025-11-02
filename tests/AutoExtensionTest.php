<?php

namespace Tourze\SymfonyDependencyServiceLoader\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SymfonyDependencyServiceLoader\Exception\InvalidYamlConfigurationException;
use Tourze\SymfonyDependencyServiceLoader\Tests\Fixtures\TestAutoExtension;

/**
 * AutoExtension 抽象类的功能测试
 * 通过 TestAutoExtension 具体实现来测试抽象类的所有方法
 *
 * @internal
 */
#[CoversClass(TestAutoExtension::class)]
class AutoExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/test_' . uniqid();
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
    }

    public function testLoadWithValidYamlContent(): void
    {
        $yamlContent = <<<'YAML'
            services:
              _defaults:
                autowire: true
                autoconfigure: true

              TestService:
                class: stdClass
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $yamlContent);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('TestService'));
    }

    public function testLoadThrowsExceptionWhenDefaultsAreMissing(): void
    {
        $yamlContent = <<<'YAML'
            services:
              TestService:
                class: stdClass
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $yamlContent);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->expectException(InvalidYamlConfigurationException::class);
        $this->expectExceptionMessage('必须包含 services._defaults 配置');

        $extension->load([], $container);
    }

    public function testLoadThrowsExceptionWhenExcludeIsUsed(): void
    {
        $yamlContent = <<<'YAML'
            services:
              _defaults:
                autowire: true
                
              TestNamespace\:
                resource: '../src/'
                exclude:
                  - '../src/Entity/'
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $yamlContent);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->expectException(InvalidYamlConfigurationException::class);
        $this->expectExceptionMessage('禁止使用 exclude 配置');

        $extension->load([], $container);
    }

    public function testLoadWorksWhenNoServicesFileExists(): void
    {
        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->expectException(\InvalidArgumentException::class);

        $extension->load([], $container);
    }

    public function testLoadDevEnvironmentConfig(): void
    {
        $mainYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              TestService:
                class: stdClass
            YAML;

        $devYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              DevService:
                class: stdClass
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $mainYaml);
        file_put_contents($this->tempDir . '/services_dev.yaml', $devYaml);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'dev');

        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('TestService'));
        $this->assertTrue($container->hasDefinition('DevService'));
    }

    public function testLoadTestEnvironmentConfig(): void
    {
        $mainYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              TestService:
                class: stdClass
            YAML;

        $testYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              TestOnlyService:
                class: stdClass
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $mainYaml);
        file_put_contents($this->tempDir . '/services_test.yaml', $testYaml);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('TestService'));
        $this->assertTrue($container->hasDefinition('TestOnlyService'));
    }

    public function testLoadProdEnvironmentSkipsEnvConfigs(): void
    {
        $mainYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              TestService:
                class: stdClass
            YAML;

        $devYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              DevService:
                class: stdClass
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $mainYaml);
        file_put_contents($this->tempDir . '/services_dev.yaml', $devYaml);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('TestService'));
        $this->assertFalse($container->hasDefinition('DevService'));
    }

    public function testEnvironmentConfigValidation(): void
    {
        $mainYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              TestService:
                class: stdClass
            YAML;

        $invalidDevYaml = <<<'YAML'
            services:
              DevService:
                class: stdClass
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $mainYaml);
        file_put_contents($this->tempDir . '/services_dev.yaml', $invalidDevYaml);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'dev');

        $this->expectException(InvalidYamlConfigurationException::class);
        $this->expectExceptionMessage('必须包含 services._defaults 配置');

        $extension->load([], $container);
    }

    public function testValidateAllowedFilesRejectsUnknownYamlFiles(): void
    {
        $validYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              TestService:
                class: stdClass
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $validYaml);
        file_put_contents($this->tempDir . '/invalid_config.yaml', $validYaml);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->expectException(InvalidYamlConfigurationException::class);
        $this->expectExceptionMessage('配置目录');
        $this->expectExceptionMessage('中发现不允许的文件 "invalid_config.yaml"');

        $extension->load([], $container);
    }

    public function testValidateAllowedFilesAcceptsOnlyWhitelistedFiles(): void
    {
        $validYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              TestService:
                class: stdClass
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $validYaml);
        file_put_contents($this->tempDir . '/services_dev.yaml', $validYaml);
        file_put_contents($this->tempDir . '/services_test.yaml', $validYaml);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('TestService'));
    }

    public function testValidateAllowedFilesIgnoresNonYamlFiles(): void
    {
        $validYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              TestService:
                class: stdClass
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $validYaml);
        file_put_contents($this->tempDir . '/other_file.txt', 'some text');
        file_put_contents($this->tempDir . '/README.md', '# README');

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('TestService'));
    }

    public function testValidateAllowedFilesHandlesYmlExtension(): void
    {
        $validYaml = <<<'YAML'
            services:
              _defaults:
                autowire: true

              TestService:
                class: stdClass
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $validYaml);
        file_put_contents($this->tempDir . '/invalid_config.yml', $validYaml);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->expectException(InvalidYamlConfigurationException::class);
        $this->expectExceptionMessage('中发现不允许的文件 "invalid_config.yml"');

        $extension->load([], $container);
    }

    public function testLoadThrowsExceptionWhenYamlContentIsNotArray(): void
    {
        // YAML文件内容不是数组（例如只是一个字符串）
        $invalidYaml = 'just a string value';

        file_put_contents($this->tempDir . '/services.yaml', $invalidYaml);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->expectException(InvalidYamlConfigurationException::class);
        $this->expectExceptionMessage('必须包含有效的services配置');

        $extension->load([], $container);
    }

    public function testLoadThrowsExceptionWhenServicesKeyIsMissing(): void
    {
        $yamlContent = <<<'YAML'
            parameters:
              some_param: value
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $yamlContent);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->expectException(InvalidYamlConfigurationException::class);
        $this->expectExceptionMessage('必须包含有效的services配置');

        $extension->load([], $container);
    }

    public function testLoadThrowsExceptionWhenServicesIsNotArray(): void
    {
        $yamlContent = <<<'YAML'
            services: "not an array"
            YAML;

        file_put_contents($this->tempDir . '/services.yaml', $yamlContent);

        $extension = new TestAutoExtension($this->tempDir);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->expectException(InvalidYamlConfigurationException::class);
        $this->expectExceptionMessage('必须包含 services._defaults 配置');

        $extension->load([], $container);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
