<?php

namespace Tourze\SymfonyDependencyServiceLoader;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;
use Tourze\SymfonyDependencyServiceLoader\Exception\InvalidYamlConfigurationException;

/**
 * 自动、固定的加载套路，统一加载配置的逻辑
 */
abstract class AutoExtension extends Extension
{
    /**
     * 存放配置的目录文件
     */
    abstract protected function getConfigDir(): string;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configDir = $this->getConfigDir();

        $this->validateAllowedFiles($configDir);

        $servicesFile = $configDir . '/services.yaml';
        if (file_exists($servicesFile)) {
            $this->validateYamlContent($servicesFile);
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator($configDir),
        );
        $loader->load('services.yaml');

        $environment = $container->getParameter('kernel.environment');

        if ('dev' === $environment) {
            $this->loadEnvironmentConfig($loader, 'services_dev.yaml');
        } elseif ('test' === $environment) {
            $this->loadEnvironmentConfig($loader, 'services_test.yaml');
        }
    }

    private function validateAllowedFiles(string $configDir): void
    {
        $allowedFiles = [
            'services.yaml',
            'services_dev.yaml',
            'services_test.yaml',
        ];

        if (!is_dir($configDir)) {
            return;
        }

        $files = scandir($configDir);
        $yamlFiles = array_filter($files, fn ($file) => str_ends_with($file, '.yaml') || str_ends_with($file, '.yml'));

        foreach ($yamlFiles as $file) {
            if (!in_array($file, $allowedFiles, true)) {
                throw new InvalidYamlConfigurationException(sprintf('配置目录 "%s" 中发现不允许的文件 "%s"，只允许: %s', $configDir, $file, implode(', ', $allowedFiles)));
            }
        }
    }

    private function loadEnvironmentConfig(YamlFileLoader $loader, string $filename): void
    {
        $configDir = $this->getConfigDir();
        $envConfigFile = $configDir . '/' . $filename;

        if (file_exists($envConfigFile)) {
            $this->validateYamlContent($envConfigFile);
            $loader->load($filename);
        }
    }

    private function validateYamlContent(string $filePath): void
    {
        $content = Yaml::parseFile($filePath, Yaml::PARSE_CUSTOM_TAGS);

        // 确保内容是数组且包含services键
        if (!is_array($content) || !isset($content['services'])) {
            throw new InvalidYamlConfigurationException(sprintf('YAML文件 "%s" 必须包含有效的services配置', $filePath));
        }

        if (!is_array($content['services']) || !isset($content['services']['_defaults'])) {
            throw new InvalidYamlConfigurationException(sprintf('YAML文件 "%s" 必须包含 services._defaults 配置。请添加：
services:
  _defaults:
    autowire: true
    autoconfigure: true', $filePath));
        }

        $this->checkForExclude($content['services'], $filePath);
    }

    /**
     * @param array<string, mixed> $services
     */
    private function checkForExclude(array $services, string $filePath): void
    {
        foreach ($services as $serviceConfig) {
            if (is_array($serviceConfig) && isset($serviceConfig['exclude'])) {
                throw new InvalidYamlConfigurationException(sprintf('YAML文件 "%s" 禁止使用 exclude 配置。
原因：exclude 会降低代码可读性和可维护性
建议：请直接在对应的类中使用注解或属性来配置服务，或者在 services.yaml 中明确定义每个服务', $filePath));
            }
        }
    }
}
