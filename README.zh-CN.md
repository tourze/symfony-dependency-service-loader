# Symfony 依赖注入服务加载器

[English](README.md) | [中文](README.zh-CN.md)

一个为 Symfony Dependency Injection 组件提供自动化、标准化配置加载的库。它通过抽象基类 `AutoExtension` 提供了统一的配置加载模式，确保服务配置的一致性和规范性。

## 特性

- 🚀 **自动化配置加载**：自动根据环境加载对应的 YAML 服务配置文件
- 📋 **配置规范验证**：强制要求服务配置包含 `_defaults` 设置，确保配置标准化
- 🚫 **禁止使用 exclude**：不允许在服务配置中使用 `exclude`，提倡明确的配置
- 🔒 **文件白名单机制**：只允许特定的配置文件名，防止配置文件混乱
- 🌍 **环境感知**：根据 `kernel.environment` 自动加载环境特定的配置
- 🔗 **Doctrine 集成**：提供 `AppendDoctrineConnectionExtension` 基类，简化 Doctrine 连接配置

## 安装

```bash
composer require tourze/symfony-dependency-service-loader
```

## 基本用法

### 使用 AutoExtension

创建一个继承自 `AutoExtension` 的扩展类：

```php
<?php

namespace App\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class MyBundleExtension extends AutoExtension
{
    /**
     * 返回配置文件所在目录
     */
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
```

### 配置文件结构

在你的配置目录中创建以下文件：

```
Resources/config/
├── services.yaml          # 必需：基础服务配置
├── services_dev.yaml      # 可选：开发环境配置
└── services_test.yaml     # 可选：测试环境配置
```

#### services.yaml（必需）

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  App\Service\:
    resource: '../src/App/Service/'
```

#### services_dev.yaml（可选）

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  # 开发环境特定服务
  App\Dev\Service\:
    resource: '../src/App/Dev/Service/'
```

#### services_test.yaml（可选）

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  # 测试环境特定服务
  App\Test\Service\:
    resource: '../src/App/Test/Service/'
```

## 使用 Doctrine 连接扩展

如果你需要为 Bundle 自动添加 Doctrine 连接配置，可以使用 `AppendDoctrineConnectionExtension`：

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

这将自动：
- 获取现有的 Doctrine DBAL 配置
- 创建一个新的连接，继承现有配置
- 启用 profiling 收集回溯信息
- 安全地移除 `use_savepoints` 配置

## 配置验证规则

### 文件名白名单

只允许以下配置文件：
- `services.yaml`
- `services_dev.yaml`
- `services_test.yaml`

任何其他 YAML 文件都会抛出 `InvalidYamlConfigurationException` 异常。

### 服务配置要求

1. **必须包含 `_defaults`**：所有服务配置文件都必须包含 `services._defaults` 设置
2. **禁止使用 `exclude`**：不允许在服务配置中使用 `exclude` 配置
3. **必须包含 `services` 键**：配置文件必须以 `services` 为根键

### 示例错误

❌ **错误配置（缺少 _defaults）**：
```yaml
services:
  App\Service\MyService:
    class: App\Service\MyService
```

❌ **错误配置（使用 exclude）**：
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

## 异常处理

库提供了专门的异常类：

- `InvalidYamlConfigurationException`：当 YAML 配置不符合规范时抛出

## 示例 Bundle 结构

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

## 环境支持

- **开发环境 (dev)**：加载 `services.yaml` + `services_dev.yaml`
- **测试环境 (test)**：加载 `services.yaml` + `services_test.yaml`
- **生产环境 (prod)**：仅加载 `services.yaml`

## 最佳实践

1. **明确配置**：避免使用 `exclude`，直接在配置中定义需要的服务
2. **环境隔离**：将环境特定的服务放在对应的配置文件中
3. **保持一致性**：所有服务配置都使用相同的 `_defaults` 设置
4. **版本控制**：将配置文件纳入版本控制，确保配置可追溯

## 版本兼容性

- PHP 8.1+
- Symfony 7.3+
- Symfony Config 7.3+
- Symfony DependencyInjection 7.3+
- Symfony Yaml 7.3+

## 许可证

MIT License

## 作者

由 [tourze](https://github.com/tourze) 开发和维护