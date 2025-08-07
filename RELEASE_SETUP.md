# 🚀 Release System Setup Instructions

This document provides step-by-step instructions for completing the setup of the new release system.

## ✅ What's Already Done

- ✅ Created `early` branch (development)
- ✅ Implemented SemVer versioning (0.2.0-dev)
- ✅ Added GitHub Actions workflows
- ✅ Configured code quality tools (PHPStan, PHP-CS-Fixer)
- ✅ Created comprehensive documentation
- ✅ Set up migration structure
- ✅ Added composer scripts for development

## 🔧 Manual Steps Required

### 1. Set `early` as Default Branch on GitHub

**🎯 Goal**: Make `early` the default branch so new contributors work on development

**Steps**:
1. Go to https://github.com/Flute-CMS/cms/settings/branches
2. Change default branch from `main` to `early`
3. Click "Update" and confirm

### 2. Enable Branch Protection for `main`

**🎯 Goal**: Prevent direct commits to stable branch

**Steps**:
1. Go to https://github.com/Flute-CMS/cms/settings/branches
2. Click "Add rule" for `main` branch
3. Configure:
   ```
   ✅ Require a pull request before merging
   ✅ Require status checks to pass before merging
   ✅ Require branches to be up to date before merging
   ✅ Require linear history
   ✅ Include administrators
   ```
4. Click "Create"

### 3. Add Required GitHub Secrets (if needed)

**🎯 Goal**: Ensure workflows have proper permissions

**Current Status**: Should work with default `GITHUB_TOKEN`

**If issues arise**:
1. Go to https://github.com/Flute-CMS/cms/settings/secrets/actions
2. Verify `GITHUB_TOKEN` has sufficient permissions
3. Add custom tokens if needed

### 4. Install Development Dependencies

**🎯 Goal**: Set up local development environment

**Run locally**:
```bash
composer install
composer require --dev friendsofphp/php-cs-fixer
```

### 5. Test the Workflows

**🎯 Goal**: Verify automation works correctly

**Test nightly build** (automatic on push to `early`):
- Push any commit to `early` branch
- Check Actions tab for "Early Branch - Nightly Build"
- Verify pre-release is created

**Test promotion workflow** (manual):
1. Go to Actions → "Promote to Main"
2. Click "Run workflow"
3. Select `patch` version bump
4. Add test release notes
5. Run and verify:
   - Version bumped
   - Main branch updated
   - Release created
   - Early branch synced

## 📋 Verification Checklist

### Branch Setup
- [ ] `early` is default branch
- [ ] `main` has branch protection enabled
- [ ] Both branches exist and are in sync

### Workflows
- [ ] Nightly build triggers on `early` push
- [ ] Promotion workflow available in Actions
- [ ] Legacy workflow is deprecated
- [ ] Pre-releases appear for development builds

### Code Quality
- [ ] PHPStan runs successfully (`composer analyse`)
- [ ] PHP-CS-Fixer works (`composer format-check`)
- [ ] Composer scripts function (`composer test`)

### Documentation
- [ ] CONTRIBUTING.md explains workflow
- [ ] UPGRADE.md covers version migration
- [ ] CHANGELOG.md follows format
- [ ] README reflects beta status and new workflow

### Version Tracking
- [ ] `composer.json` shows correct version
- [ ] `app/Core/App.php` version matches
- [ ] `public/meta.json` exists and is valid

## 🎯 Expected Workflow After Setup

### For Development
```bash
# Clone and setup
git clone https://github.com/Flute-CMS/cms.git
cd cms
composer install

# Make changes
git checkout -b feature/my-feature
# ... make changes ...
git commit -m "feat: add new feature"
git push origin feature/my-feature

# Create PR to early branch
# After merge, nightly build triggers automatically
```

### For Releases
```bash
# Via GitHub UI:
# Actions → Promote to Main → Run workflow
# Select version type (patch/minor/major)
# Add release notes
# Execute

# Result:
# - Version bumped (e.g., 0.2.0-dev → 0.2.0)
# - Main branch updated with stable code
# - GitHub release created with production archive
# - Early branch fast-forwarded to next dev version (0.3.0-dev)
```

## 🔍 Monitoring & Maintenance

### Daily Checks
- Monitor nightly build status
- Review development pre-releases
- Check for failed workflows

### Weekly Tasks
- Review pending PRs to `early`
- Assess stability for promotion
- Update documentation if needed

### Release Tasks
- Test promotion workflow in staging
- Verify release archives
- Update community announcements
- Monitor post-release issues

## 🆘 Troubleshooting

### Workflow Fails
1. Check Actions logs for errors
2. Verify branch permissions
3. Check GitHub token permissions
4. Review workflow syntax

### Version Conflicts
1. Ensure version consistency across files
2. Check for merge conflicts
3. Verify SemVer compliance

### Build Issues
1. Test locally with `composer test`
2. Check PHPStan and CS-Fixer output
3. Verify dependencies are up to date

## 📞 Support

If you encounter issues during setup:

1. **Check existing issues**: https://github.com/Flute-CMS/cms/issues
2. **Community Discord**: https://discord.gg/BcBMeVJJsd
3. **Create new issue** with setup logs and error messages

---

**Next Steps**: Follow the manual steps above to complete the release system setup!
