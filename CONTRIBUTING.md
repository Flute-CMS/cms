# Contributing to Flute CMS

Thank you for your interest in contributing to Flute CMS! This document outlines our development process and contribution guidelines.

## 🌿 Branch Strategy

We follow a simplified Git Flow model with two main branches:

### Branches

- **`main`** - Stable production-ready code
  - Protected branch requiring PR reviews
  - Only accepts merges from `early` via the promotion workflow
  - Automatically tagged with semantic versions (v1.0.0, v1.1.0, etc.)
  - Used for official releases

- **`early`** - Development branch (default)
  - All feature development happens here
  - Accepts direct commits and PRs
  - Generates nightly development builds
  - Default branch for new contributors

### Workflow

```
feature/new-feature → early → (promotion workflow) → main → tag
                 ↘                                ↗
                   hotfix/urgent-fix → main → fast-forward to early
```

## 🚀 Development Process

### 1. Setting Up Development Environment

```bash
# Clone the repository
git clone https://github.com/Flute-CMS/cms.git
cd cms

# Install dependencies
composer install

# Install development tools
composer require --dev friendsofphp/php-cs-fixer

# Set up database
php flute migrate
```

### 2. Making Changes

1. **Create a feature branch** (optional but recommended):
   ```bash
   git checkout early
   git pull origin early
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** following our coding standards

3. **Test your changes**:
   ```bash
   # Run code style check
   vendor/bin/php-cs-fixer fix --dry-run

   # Run static analysis
   vendor/bin/phpstan analyse

   # Test migration (if applicable)
   php flute migrate:status
   ```

4. **Commit and push**:
   ```bash
   git add .
   git commit -m "feat: add new feature description"
   git push origin feature/your-feature-name
   ```

5. **Create Pull Request** to `early` branch

### 3. Commit Message Convention

We follow conventional commits for automatic changelog generation:

```
type(scope): description

feat: new feature
fix: bug fix
docs: documentation changes
style: code style changes (formatting, etc.)
refactor: code refactoring
test: adding tests
chore: maintenance tasks
ci: CI/CD changes
```

Examples:
- `feat(auth): add OAuth2 authentication`
- `fix(database): resolve connection timeout issue`
- `docs: update installation guide`

## 📋 Code Quality Standards

### PHP Standards

- **PHP Version**: 8.2+ required
- **PSR-12** coding standard
- **PHPStan Level 8** static analysis
- **Type declarations** for all methods and properties

### Code Style

Run PHP-CS-Fixer before committing:
```bash
vendor/bin/php-cs-fixer fix
```

### Static Analysis

Ensure PHPStan passes:
```bash
vendor/bin/phpstan analyse
```

### Module Structure

```
app/Modules/YourModule/
├── composer.json          # Module dependencies
├── module.json            # Module information
├── ServiceProvider.php    # Module service provider
├── database/
│   └── migrations/        # Module-specific migrations
├── Resources/
│   ├── views/            # Blade templates
│   └── lang/             # Translations
└── Controllers/          # Module controllers
```

### Module Guidelines

- Use **Cycle ORM** for database operations [[memory:2991515]]
- Define routes via **annotations** instead of separate route files
- Use the **`<x-icon>`** component for icons
- Avoid unnecessary code comments
- Follow the minimalistic, Apple-style UI approach [[memory:2995457]]

## 🔄 Release Process

### Development Releases (Early Branch)

- **Automatic**: Every push to `early` triggers nightly build
- **Naming**: `flute-cms-dev-YYYYMMDD-{hash}.zip`
- **Channel**: Pre-release on GitHub
- **Testing**: Use for development and testing only

### Stable Releases (Main Branch)

**Only maintainers can trigger stable releases via GitHub Actions:**

1. Navigate to **Actions** → **Promote to Main**
2. Click **Run workflow**
3. Select version bump type: `patch` | `minor` | `major`
4. Add release notes (optional)
5. Confirm execution

The workflow will:
- Run full test suite
- Bump version following SemVer
- Update CHANGELOG.md
- Merge `early` → `main`
- Create git tag (e.g., `v1.2.0`)
- Build production archive
- Create GitHub release
- Fast-forward merge back to `early`
- Bump `early` to next dev version

## 🧪 Testing

### Types of Testing

1. **Static Analysis**: PHPStan level 8
2. **Code Style**: PHP-CS-Fixer with PSR-12
3. **Unit Tests**: PHPUnit (when available)
4. **Integration Tests**: Database migrations
5. **Smoke Tests**: Basic functionality checks

### Running Tests

```bash
# All quality checks
composer test

# Individual checks
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run
php flute migrate --dry-run
```

## 📖 Documentation

### Required Documentation

- **API changes**: Update relevant docblocks
- **Breaking changes**: Add to UPGRADE.md
- **New features**: Update user documentation
- **Configuration**: Document new config options

### Writing Guidelines

- Clear, concise explanations
- Include code examples
- Test all code snippets
- Link to related documentation

## 🆘 Getting Help

- **Issues**: [GitHub Issues](https://github.com/Flute-CMS/cms/issues)
- **Discussions**: [GitHub Discussions](https://github.com/Flute-CMS/cms/discussions)
- **Discord**: [Community Chat](https://discord.gg/BcBMeVJJsd)
- **Documentation**: [docs.flute-cms.com](https://docs.flute-cms.com)

## 📜 License

By contributing to Flute CMS, you agree that your contributions will be licensed under the [GPL-3.0-or-later](LICENSE) license.

---

Thank you for helping make Flute CMS better! 🚀
