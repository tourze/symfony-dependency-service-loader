<?php

namespace Tourze\SymfonyDependencyServiceLoader;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

abstract class AppendDoctrineConnectionExtension extends AutoExtension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $defaultConfig = null;
        foreach ($container->getExtensionConfig('doctrine') as $config) {
            if (isset($config['dbal']) && is_array($config['dbal'])) {
                $defaultConfig = $config['dbal'];
                break;
            }
        }
        assert(null !== $defaultConfig);

        // 确保获取有效的连接配置
        if (isset($defaultConfig['connections']) && is_array($defaultConfig['connections'])) {
            $connections = $defaultConfig['connections'];
            $firstConnection = reset($connections);
            $basicConfig = is_array($firstConnection) ? $firstConnection : $defaultConfig;
        } else {
            $basicConfig = $defaultConfig;
        }

        // 安全地移除use_savepoints键
        unset($basicConfig['use_savepoints']);

        // 配置 doctrine dbal 连接
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'connections' => [
                    $this->getDoctrineConnectionName() => [
                        ...$basicConfig,
                        'profiling_collect_backtrace' => true,
                        //                        'mapping_types' => [
                        //                            'enum' => 'string',
                        //                        ],
                        //                        'options' => [
                        //                            'charset' => 'utf8mb4',
                        //                        ],
                    ],
                ],
            ],
        ]);
    }

    abstract protected function getDoctrineConnectionName(): string;
}
