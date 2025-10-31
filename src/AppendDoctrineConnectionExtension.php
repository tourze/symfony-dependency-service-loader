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
            if (isset($config['dbal'])) {
                $defaultConfig = $config['dbal'];
                break;
            }
        }
        assert(null !== $defaultConfig);

        $basicConfig = isset($defaultConfig['connections']) ? reset($defaultConfig['connections']) : $defaultConfig;
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
