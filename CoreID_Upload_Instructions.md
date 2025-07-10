# CoreID File Upload Instructions

## Overview
The CoreID upload feature allows you to upload a file containing Core IDs (CIDs) to set member tagging to "PGB" and manage member records in the system.

## File Format Requirements

### Excel/CSV File Structure:
- **Header**: Must have "CoreID" in cell A1
- **Data**: CID values starting from row 2
- **Format**: CIDs will be automatically padded to 9 digits

### Example File Structure:
```
| CoreID |
|--------|
| 2026   |
| 12345  |
| 789    |
```

### How CIDs are Processed:
- `2026` becomes `000002026`
- `12345` becomes `000012345`
- `789` becomes `000000789`

## Upload Process

### What Happens During Upload:

1. **For Existing Members**:
   - If a CID matches an existing member, their `member_tagging` is set to "PGB"
   - Member record is updated

2. **For New CIDs**:
   - If a CID doesn't exist, a new member record is created
   - New member gets `member_tagging` set to "PGB"

3. **For Members Not in File**:
   - Members not found in the uploaded file are removed
   - **Exception**: Members with `member_tagging = "New"` are NOT removed

## Usage Instructions

1. **Navigate to Master List**: Go to the Master List page in the admin panel
2. **Upload File**: Use the "CoreID File Upload" section at the top of the page
3. **Select File**: Choose an Excel (.xlsx, .xls) or CSV file
4. **Upload**: Click the "Upload CoreID" button
5. **Review Results**: Check the import results table for details

## Important Notes

- **First Upload**: This should be done before importing other files (loans, savings, etc.)
- **Member Tagging**: Only members with "PGB" tagging will be processed by other imports
- **Protection**: Members with "New" tagging are protected from deletion
- **Validation**: The system validates file format and provides detailed feedback

## Error Handling

The system will show:
- Success messages for matched, inserted, and removed members
- Error messages for invalid file formats
- Detailed results table with status for each CID

## File Size Limit
- Maximum file size: 2MB
- Supported formats: .xlsx, .xls, .csv 
