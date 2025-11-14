# Symfony Dependency Service Loader

[English](README.md) | [简体中文](README.zh-CN.md)

这个包为 Symfony Dependency Injection 组件提供了 `AutoExtension` 基类，用于自动加载服务配置。

## 特性

- **自动加载服务配置**：自动加载指定目录下的 YAML 服务配置文件
- **环境特定配置**：根据 `kernel.environment` 自动加载对应环境的服务配置
- **严格的文件验证**：限制配置目录中允许的文件名，避免错误配置
- **最佳实践强制**：强制要求使用 `_defaults` 配置，提高代码质量
- **禁用 exclude**：禁止使用 `exclude` 配置，鼓励明确的服务定义
- **智能配置合并**：自动合并基础配置和环境特定配置
- **环境感知**：根据 `kernel.environment` 参数动态加载配置
- **Doctrine 集成**：通过 `AppendDoctrineConnectionExtension` 支持 Doctrine 连接配置

## 安装

```bash
composer require tourze/symfony-dependency-service-loader
```

## 使用方法

### 使用 AutoExtension

继承 `AutoExtension` 类：

```php
<?php

namespace App\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class MyBundleExtension extends AutoExtension
{
    /**
     * 返回配置文件目录路径
     */
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
```

### 配置文件结构

支持以下配置文件结构：

```
Resources/config/
├── services.yaml          # 基础服务配置
├── services_dev.yaml      # 开发环境服务配置
└── services_test.yaml     # 测试环境服务配置
```

#### services.yaml（基础配置）

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  App\Service\:
    resource: '../src/App/Service/'
```

#### services_dev.yaml（开发环境）

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  # 开发环境特定的服务
  App\Dev\Service\:
    resource: '../src/App/Dev/Service/'
```

#### services_test.yaml（测试环境）

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  # 测试环境特定的服务
  App\Test\Service\:
    resource: '../src/App/Test/Service/'
```

## 使用 Doctrine 连接扩展

如果你的 Bundle 需要 Doctrine 连接配置，可以使用 `AppendDoctrineConnectionExtension`：

```php
<?php

namespace App\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AppendDoctrineConnectionExtension;

class MyDoctrineBundleExtension extends AppendDoctrineConnectionExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    /**
     * 返回 Doctrine 连接名称
     */
    protected function getDoctrineConnectionName(): string
    {
        return 'my_connection';
    }
}
```

此扩展会：
- 复制现有的 Doctrine DBAL 配置
- 自动配置连接参数
- 启用 profiling 收集回溯信息
- 移除 `use_savepoints` 配置

## 配置规则

### 允许的文件

只允许以下配置文件：
- `services.yaml`
- `services_dev.yaml`
- `services_test.yaml`

其他 YAML 文件会触发 `InvalidYamlConfigurationException` 异常

### 配置要求

1. **必须包含 `_defaults`**：所有配置文件必须包含 `services._defaults` 配置
2. **禁止使用 `exclude`**：服务配置中禁止使用 `exclude` 配置
3. **必须包含 `services` 键**：配置必须包含 `services` 顶级键

### 示例配置

❌ **错误配置**（缺少 _defaults）：
```yaml
services:
  App\Service\MyService:
    class: App\Service\MyService
```

❌ **错误配置**（使用 exclude）：
```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  App\Service\:
    resource: '../src/App/Service/'
    exclude:
      - '../src/App/Service/Deprecated/'
```

✅ **正确配置**：
```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  App\Service\MyService:
    class: App\Service\MyService
```

## 异常

可能抛出的异常：

- `InvalidYamlConfigurationException`：当 YAML 配置格式或内容不符合要求时

## Bundle 目录结构示例

```
MyBundle/
├── DependencyInjection/
│   └── MyBundleExtension.php
└── Resources/
    └── config/
        ├── services.yaml
        ├── services_dev.yaml
        └── services_test.yaml
```

## 环境配置加载逻辑

- **开发环境 (dev)**：加载 `services.yaml` + `services_dev.yaml`
- **测试环境 (test)**：加载 `services.yaml` + `services_test.yaml`
- **生产环境 (prod)**：仅加载 `services.yaml`

## 最佳实践

1. **明确配置**：避免使用 `exclude`，明确定义每个服务
2. **环境分离**：将环境特定的服务放在对应的配置文件中
3. **统一标准**：所有配置文件都应包含 `_defaults` 配置
4. **配置验证**：该扩展会自动验证配置文件的完整性和正确性

## 系统要求

- PHP 8.1+
- Symfony 7.3+
- Symfony Config 7.3+
- Symfony DependencyInjection 7.3+
- Symfony Yaml 7.3+

## 许可证

MIT License

## 作者

1. [tourze](https://github.com/tourze) 创始作者