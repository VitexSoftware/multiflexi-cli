# MultiFlexi CLI - Listing Pagination Guide

## Overview
MultiFlexi CLI provides comprehensive pagination and filtering capabilities for all listing commands. You can control how many records to display, skip records, sort results, and customize output fields.

## Available Pagination Options

### 1. Limit Results (`--limit=<number>`)
Restrict the number of records returned:
```bash
# Show only 5 users
multiflexi-cli user list --limit=5

# Display 10 applications
multiflexi-cli application list --limit=10
```

### 2. Offset Records (`--offset=<number>`)
Skip a specified number of records (useful for pagination):
```bash
# Skip first 10 records, show the rest
multiflexi-cli user list --offset=10

# Skip 20 records, then show 5
multiflexi-cli company list --offset=20 --limit=5
```

### 3. Sort Order (`--order=<A|D>`)
Control the sort order of results:
- `A` = Ascending (default)
- `D` = Descending

```bash
# Sort users in descending order
multiflexi-cli user list --order=D

# Get latest 10 jobs (descending order)
multiflexi-cli job list --order=D --limit=10
```

### 4. Select Fields (`--fields=<field1,field2,field3>`)
Display only specific fields in the output:
```bash
# Show only ID and name for applications
multiflexi-cli application list --fields=id,name

# Display specific user fields
multiflexi-cli user list --fields=id,login,email --limit=5
```

## Combining Options for Advanced Pagination

### Page-by-Page Navigation
```bash
# Page 1: First 10 records
multiflexi-cli user list --limit=10 --offset=0

# Page 2: Next 10 records  
multiflexi-cli user list --limit=10 --offset=10

# Page 3: Next 10 records
multiflexi-cli user list --limit=10 --offset=20
```

### Efficient Data Browsing
```bash
# Get latest 5 jobs with key fields only
multiflexi-cli job list --order=D --limit=5 --fields=id,name,status

# Browse companies starting from 6th record
multiflexi-cli company list --offset=5 --limit=10 --order=A

# Get specific application data
multiflexi-cli application list --fields=name,version --limit=20
```

## Output Formatting

### JSON Output for APIs/Scripts
Add `--format=json` for machine-readable output:
```bash
# JSON pagination for scripts
multiflexi-cli user list --limit=5 --offset=10 --format=json

# Structured data with custom fields
multiflexi-cli application list --fields=id,name --format=json
```

### Human-Readable Output (Default)
```bash
# Default text output (no --format needed)
multiflexi-cli user list --limit=5 --order=D
```

## Commands Supporting Pagination

All listing operations support pagination options:

- `multiflexi-cli user list`
- `multiflexi-cli application list`
- `multiflexi-cli company list`
- `multiflexi-cli job list`
- `multiflexi-cli token list`
- `multiflexi-cli runtemplate list`
- `multiflexi-cli credential list`
- `multiflexi-cli credentialtype list`
- `multiflexi-cli artifact list`
- `multiflexi-cli queue list`
- `multiflexi-cli userdataerasure list`

## Practical Examples

### Dashboard Summary
```bash
# Quick overview: 5 latest jobs
multiflexi-cli job list --order=D --limit=5 --fields=id,name,status

# Recent applications
multiflexi-cli application list --limit=10 --fields=name,version
```

### Data Analysis
```bash
# Export all users to JSON (in batches)
multiflexi-cli user list --format=json --limit=100 --offset=0
multiflexi-cli user list --format=json --limit=100 --offset=100

# Find specific records
multiflexi-cli company list --fields=id,name,email --order=A
```

### Performance Optimization
```bash
# Large datasets: use pagination to avoid memory issues
multiflexi-cli job list --limit=50 --offset=0    # First batch
multiflexi-cli job list --limit=50 --offset=50   # Second batch
multiflexi-cli job list --limit=50 --offset=100  # Third batch
```

## Best Practices

1. **Use `--limit`** for large datasets to improve performance
2. **Combine `--offset` and `--limit`** for efficient pagination
3. **Use `--fields`** to reduce output size and improve readability  
4. **Use `--format=json`** for automated processing and API integration
5. **Use `--order=D`** to get most recent records first

## Tips for TUI Implementation

- **Page Navigation**: Implement next/previous page buttons using offset calculations
- **Dynamic Limits**: Allow users to change page size (10, 25, 50, 100 records)
- **Field Selection**: Provide checkboxes for field selection
- **Sort Toggle**: Click column headers to toggle A/D ordering
- **Search Integration**: Combine pagination with filtering options

---

This guide provides comprehensive information about pagination features that can be implemented in the multiflexi-tui interface for an optimal user experience.