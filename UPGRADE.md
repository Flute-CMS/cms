# Upgrade Guide

This guide helps you upgrade Flute CMS between versions safely and efficiently.

## 📋 General Upgrade Process

### Before You Start

1. **Backup everything**:
   ```bash
   # Database backup
   mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
   
   # Files backup
   tar -czf flute_backup_$(date +%Y%m%d).tar.gz /path/to/flute
   ```

2. **Check requirements** for the target version
3. **Read the changelog** for breaking changes
4. **Test in staging environment** first

### Standard Upgrade Steps

1. **Download the latest stable release**
2. **Extract files** over existing installation (preserve config files)
3. **Run database migrations**:
   ```bash
   php flute migrate
   ```
4. **Clear application cache**:
   ```bash
   php flute cache:clear
   ```
5. **Update file permissions** if needed
6. **Test functionality**

## 🔄 Version-Specific Upgrade Instructions

### From 0.1.x to 0.2.0

**Breaking Changes:**
- New branch structure (early/main)
- SemVer adoption
- Updated migration system

**Steps:**
1. Follow standard upgrade process
2. Update any custom integrations to use new version format
3. Check module compatibility

### Future Versions

Version-specific instructions will be added here as releases are made.

## 🛠️ Migration Commands

### Database Migrations

```bash
# Check migration status
php flute migrate:status

# Run pending migrations
php flute migrate

# Rollback last migration batch (emergency only)
php flute migrate:rollback

# Dry run (check what would be executed)
php flute migrate --dry-run
```

### Cache Management

```bash
# Clear all caches
php flute cache:clear

# Clear specific cache types
php flute cache:clear --type=routes
php flute cache:clear --type=views
php flute cache:clear --type=config
```

## 🔧 Configuration Updates

### Version 0.2.0+ Configuration Changes

- New `public/meta.json` file for version tracking
- Updated schema versioning in database config

### Checking Configuration

```bash
# Validate current configuration
php flute config:validate

# Show current version info
php flute about
```

## 📦 Module Updates

### Updating Core Modules

1. **Check compatibility** with new core version
2. **Download updated modules** from official sources
3. **Run module-specific migrations**:
   ```bash
   php flute module:migrate ModuleName
   ```

### Custom Module Migration

If you have custom modules, ensure they:
- Follow new module structure guidelines
- Use updated API methods
- Include proper version constraints in `composer.json`

## 🚨 Troubleshooting

### Common Issues

#### Migration Failures

```bash
# Check migration status
php flute migrate:status

# Force migration state (use carefully)
php flute migrate:reset
php flute migrate
```

#### Permission Issues

```bash
# Fix file permissions (Linux/Mac)
find /path/to/flute -type f -exec chmod 644 {} \;
find /path/to/flute -type d -exec chmod 755 {} \;
chmod -R 777 storage/
```

#### Cache Issues

```bash
# Clear all caches and regenerate
php flute cache:clear
php flute optimize
```

### Rollback Procedure

If upgrade fails:

1. **Stop web server**
2. **Restore file backup**:
   ```bash
   tar -xzf flute_backup_YYYYMMDD.tar.gz -C /
   ```
3. **Restore database backup**:
   ```bash
   mysql -u username -p database_name < backup_YYYYMMDD.sql
   ```
4. **Start web server**
5. **Test functionality**

## 🏗️ Development/Testing Upgrades

### From Stable to Development

If switching from stable releases to development builds:

1. Change to `early` branch:
   ```bash
   git remote add origin https://github.com/Flute-CMS/cms.git
   git fetch origin
   git checkout early
   ```

2. Install dev dependencies:
   ```bash
   composer install
   ```

3. Run migrations:
   ```bash
   php flute migrate
   ```

### From Development to Stable

1. Backup development data
2. Download latest stable release
3. Follow standard upgrade process
4. Note: Some dev features may not be available in stable

## 📞 Getting Help

### Before Asking for Help

1. Check this upgrade guide
2. Read the [CHANGELOG.md](CHANGELOG.md)
3. Search [GitHub Issues](https://github.com/Flute-CMS/cms/issues)
4. Check [Documentation](https://docs.flute-cms.com)

### Reporting Upgrade Issues

When reporting upgrade problems, include:

- **Source version** (from `public/meta.json` or about page)
- **Target version** attempting to upgrade to
- **Exact error messages** or logs
- **Steps taken** during upgrade process
- **Environment details** (PHP version, database, OS)

### Support Channels

- **Bug Reports**: [GitHub Issues](https://github.com/Flute-CMS/cms/issues)
- **Community Support**: [Discord](https://discord.gg/BcBMeVJJsd)
- **Documentation**: [docs.flute-cms.com](https://docs.flute-cms.com)

## 📋 Post-Upgrade Checklist

After completing an upgrade:

- [ ] All core functionality works
- [ ] Custom modules are functional
- [ ] User authentication works
- [ ] File uploads work
- [ ] Email sending works (if configured)
- [ ] All pages load without errors
- [ ] Admin panel is accessible
- [ ] Database integrity is maintained
- [ ] Performance is acceptable
- [ ] Security settings are preserved

---

**Always test upgrades in a staging environment before applying to production!**
