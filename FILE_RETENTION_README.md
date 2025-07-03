# File Retention System

## Overview

The billing system now includes a comprehensive file retention system that automatically manages uploaded files to prevent storage bloat and maintain system performance. The system keeps a maximum of **12 files per document type** and automatically deletes the oldest files when this limit is exceeded.

## Features

### Automatic File Retention
- **Maximum 12 files per document type**: Each document type can have up to 12 files stored
- **Automatic cleanup**: Oldest files are automatically deleted when new files are uploaded
- **Storage optimization**: Prevents unlimited file accumulation
- **Database consistency**: Both physical files and database records are cleaned up

### Document Types Supported
1. **Installment File** - Loan forecast files
2. **Savings** - Savings account files
3. **Shares** - Share account files
4. **CIF** - Customer Information Files
5. **Loan** - Loan account files
6. **CoreID** - Core ID import files
7. **Savings & Shares Product** - Product deduction files

### Admin Dashboard
- **Real-time statistics**: View file counts, sizes, and storage usage
- **Manual cleanup**: Perform manual cleanup operations
- **Dry-run preview**: Preview what would be deleted before actual cleanup
- **Per-type management**: Clean up specific document types

## Implementation Details

### Core Components

#### 1. FileRetentionService (`app/Services/FileRetentionService.php`)
- Central service for file retention logic
- Handles cleanup operations
- Provides storage statistics
- Reusable across controllers

#### 2. DocumentUploadController (`app/Http/Controllers/DocumentUploadController.php`)
- Updated to use file retention system
- Automatically cleans up old files before uploading new ones
- Supports both admin and branch uploads

#### 3. MasterController (`app/Http/Controllers/MasterController.php`)
- Updated CoreID and Savings & Shares Product uploads
- Implements file retention for additional file types
- Stores files in DocumentUpload table

#### 4. FileRetentionController (`app/Http/Controllers/FileRetentionController.php`)
- Admin interface for file retention management
- Provides dashboard and manual cleanup operations
- RESTful API for statistics and cleanup

#### 5. CleanupOldFiles Command (`app/Console/Commands/CleanupOldFiles.php`)
- Artisan command for scheduled cleanup
- Supports dry-run mode for testing
- Can target specific document types

### Database Schema

The system uses the existing `document_uploads` table with the following key fields:
- `document_type`: Type of document (enum)
- `filename`: Original filename
- `filepath`: Storage path
- `upload_date`: When the file was uploaded
- `billing_period`: Associated billing period

## Usage

### Automatic Operation

The file retention system works automatically. When you upload a new file:

1. The system checks how many files exist for that document type
2. If more than 12 files exist, the oldest files are automatically deleted
3. The new file is stored
4. Both physical files and database records are cleaned up

### Manual Management

#### Access the Dashboard
```
/admin/file-retention
```

#### View Statistics
- Total files across all types
- Storage usage in MB
- Files over the limit
- Per-type breakdown

#### Manual Cleanup
1. **Preview Cleanup**: Click "Preview Cleanup" to see what would be deleted
2. **Cleanup All**: Remove all files over the limit across all types
3. **Per-Type Cleanup**: Clean up specific document types

### Command Line Operations

#### Cleanup All Files
```bash
php artisan files:cleanup
```

#### Cleanup Specific Type
```bash
php artisan files:cleanup --type="Installment File"
```

#### Dry Run (Preview)
```bash
php artisan files:cleanup --dry-run
```

#### Combined Options
```bash
php artisan files:cleanup --type="Savings" --dry-run
```

## Configuration

### Maximum Files Per Type
The limit is set to 12 files per document type. To change this:

1. Update the constant in `FileRetentionService.php`:
```php
private const MAX_FILES_PER_TYPE = 12; // Change this value
```

2. Update the constant in `DocumentUploadController.php`:
```php
private const MAX_FILES_PER_TYPE = 12; // Change this value
```

3. Update the constant in `MasterController.php`:
```php
private const MAX_FILES_PER_TYPE = 12; // Change this value
```

4. Update the constant in `CleanupOldFiles.php`:
```php
private const MAX_FILES_PER_TYPE = 12; // Change this value
```

### Scheduled Cleanup

To set up automatic scheduled cleanup, add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Run cleanup daily at 2 AM
    $schedule->command('files:cleanup')->daily()->at('02:00');
    
    // Or run weekly
    $schedule->command('files:cleanup')->weekly()->sundays()->at('02:00');
}
```

## API Endpoints

### Get Statistics
```
GET /admin/file-retention/stats
```

Response:
```json
{
    "success": true,
    "stats": {
        "Installment File": {
            "count": 8,
            "total_size_mb": 15.5,
            "at_limit": false,
            "files_over_limit": 0
        }
    },
    "max_files_per_type": 12
}
```

### Perform Cleanup
```
POST /admin/file-retention/cleanup
```

Parameters:
- `document_type` (optional): Specific document type to clean
- `dry_run` (boolean): Preview mode

Response:
```json
{
    "success": true,
    "message": "Successfully deleted 5 old files",
    "files_deleted": 5,
    "space_freed_mb": 12.3
}
```

## Monitoring and Logging

### Log Entries
The system logs all cleanup operations:
```
File retention: Deleted 3 old files for document type 'Installment File', freed 8.5 MB
```

### Dashboard Monitoring
- Real-time file counts
- Storage usage tracking
- Files over limit alerts
- Historical statistics

## Performance Considerations

### Storage Optimization
- Automatic cleanup prevents unlimited growth
- Efficient file deletion (both physical and database)
- Minimal impact on upload performance

### Database Optimization
- Indexed queries on `document_type` and `upload_date`
- Efficient cleanup operations
- Minimal database overhead

### Memory Usage
- Batch processing for large cleanup operations
- Efficient file size calculations
- Optimized for large file collections

## Troubleshooting

### Common Issues

#### Files Not Being Deleted
1. Check file permissions on storage directory
2. Verify database connection
3. Check application logs for errors

#### Dashboard Not Loading
1. Verify routes are properly registered
2. Check authentication middleware
3. Ensure FileRetentionService is properly injected

#### Command Line Errors
1. Verify Artisan command is registered
2. Check file permissions
3. Ensure proper database access

### Debug Mode

Enable debug logging by adding to your `.env`:
```
LOG_LEVEL=debug
```

### Manual Verification

Check file counts manually:
```sql
SELECT document_type, COUNT(*) as file_count 
FROM document_uploads 
GROUP BY document_type 
ORDER BY file_count DESC;
```

## Security Considerations

### Access Control
- File retention dashboard requires admin authentication
- API endpoints are protected by middleware
- Command line operations require proper permissions

### File Safety
- Only deletes files older than the limit
- Preserves newest files automatically
- Dry-run mode available for testing

### Data Integrity
- Database transactions ensure consistency
- Physical file deletion is verified
- Error handling prevents partial operations

## Future Enhancements

### Potential Improvements
1. **Configurable limits per type**: Different limits for different document types
2. **Retention policies**: Time-based retention (e.g., keep files for 6 months)
3. **Compression**: Compress old files instead of deleting
4. **Backup integration**: Backup files before deletion
5. **Email notifications**: Alert admins when cleanup occurs
6. **Storage quotas**: Per-user or per-branch storage limits

### Monitoring Enhancements
1. **Storage trend analysis**: Track storage growth over time
2. **Usage analytics**: Most active document types
3. **Performance metrics**: Upload and cleanup performance
4. **Alert system**: Notify when approaching limits

## Support

For issues or questions about the file retention system:

1. Check the application logs
2. Review this documentation
3. Test with dry-run mode first
4. Contact system administrator

## Changelog

### Version 1.0.0
- Initial implementation of file retention system
- Automatic cleanup on file upload
- Admin dashboard for manual management
- Command line tools for scheduled cleanup
- Support for 7 document types
- Maximum 12 files per type limit 
