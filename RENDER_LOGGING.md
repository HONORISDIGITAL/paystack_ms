# Render Logging Configuration

## Overview

Render uses ephemeral filesystems, which means any files written to disk (including log files in `storage/logs/`) are lost when the container restarts or redeploys.

## Solution

All services on Render should use `stderr` logging, which Render automatically captures and makes available through the Render dashboard.

## Configuration

### Automatic Configuration

The logging configuration has been updated to automatically use `stderr` in production environments. If `LOG_CHANNEL` is not explicitly set and `APP_ENV=production`, logs will automatically go to stderr.

### Manual Configuration

For explicit control, set the following environment variable in your Render service:

```
LOG_CHANNEL=stderr
```

This is already configured in `render.yaml` for worker services.

### Viewing Logs

1. **Render Dashboard**: Logs are available in the Render dashboard under your service's "Logs" tab
2. **Render CLI**: Use `render logs` command to view logs
3. **No File Access**: Do not attempt to access log files directly via SSH or file system - they don't persist

## Web Services

If you have web services deployed separately (not in `render.yaml`), ensure they have `LOG_CHANNEL=stderr` set in their environment variables in the Render dashboard.







