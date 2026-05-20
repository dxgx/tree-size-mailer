---
name: testing-workflow
description: 'Test the TreeSizeMailer package by running dg:tree-size-mailer command, validating output, and checking email generation. Use when testing changes, verifying functionality, or debugging report generation.'
argument-hint: 'Optional: specify custom config to test'
---

# TreeSizeMailer Testing Workflow

## When to Use

- After making code changes to verify functionality
- Testing new configuration options
- Debugging report generation issues
- Validating email delivery
- Checking report formatting and content

## Workflow

This skill automates the manual testing process for the TreeSizeMailer package.

### 1. Check Current Configuration

First, review the active configuration to understand what will be tested:

```bash
grep -A 2 "recipients\|scan_path\|max_depth\|min_.*_size" config/tree-size-mailer.php
```

### 2. Run the Report Command

Execute the Artisan command and capture output:

```bash
php artisan dg:tree-size-mailer
```

### 3. Validate Output

Check that the command output includes:
- ✅ **Root Level**: Directory count and total size
- ✅ **Detailed**: Directory count and total size
- ✅ **Tree**: Item count and total size
- ✅ **Custom Breakdowns**: If configured, breakdown summaries
- ✅ **Recipients**: Confirmation of email delivery

### 4. Verify Email Generation

**What to check:**
1. **Email received** at configured recipient address(es)
2. **Subject line** matches: `[App Name] Directory Tree Size Report – YYYY-MM-DD`
3. **All sections present**:
   - 📊 Root Level Overview
   - 🌳 Directory Tree
   - 📂 Detailed Directory Sizes
   - 📦 Custom Breakdowns (if configured)
4. **Data integrity**:
   - Sizes are formatted correctly (KB, MB, GB)
   - Paths display properly with `./` prefix
   - Tree view has proper indentation
   - Totals are calculated correctly
5. **Filters applied**:
   - Section notes show correct thresholds
   - Row limits respected
   - Excluded directories not shown

### 5. Report Results

Summarize findings:
- ✅ Command executed successfully
- ✅ Output shows expected section counts
- ✅ Email delivered to: [recipients]
- ⚠️ Any warnings or issues noted
- ❌ Any failures or errors

## Testing with Custom Configuration

To test specific configurations:

1. **Test with different size thresholds**:
   ```bash
   TREE_SIZE_REPORT_MIN_SIZE=1048576 php artisan dg:tree-size-mailer
   ```

2. **Test different scan paths**:
   ```bash
   TREE_SIZE_REPORT_SCAN_PATH=/path/to/test php artisan dg:tree-size-mailer
   ```

3. **Test with excluded directories** - Edit `config/tree-size-mailer.php` temporarily

## Common Issues to Check

- **No email received**: Check mail driver configuration in `.env`
- **Empty sections**: Size thresholds may be too high
- **Missing directories**: Check `excluded_dirs` configuration
- **Wrong totals**: Verify breakdown directories aren't double-counted
- **Permission errors**: Ensure read access to all scanned directories

## Quick Sanity Check

After running, verify these basics:
1. Exit code is 0 (success)
2. Console shows "Tree size report emailed to: ..."
3. No PHP errors or warnings
4. Email arrives within 1-2 minutes
5. Report displays correctly in email client

## Follow-up Actions

Based on results:
- **If successful**: Commit changes and update CHANGELOG
- **If issues found**: Use output to diagnose and fix
- **For new features**: Verify documentation matches behavior
- **Before release**: Test with production-like data volumes
