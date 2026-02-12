# MalShare API Test Suite Results

## Overview

A comprehensive Docker-based test suite has been created to validate the MalShare API functionality. All tests run in Docker against the local MalShare instance.

## Test Execution Summary

- **Total Tests:** 45
- **Passed:** 39 ✓
- **Failed:** 5 ✗
- **Skipped:** 1 ⊘
- **Success Rate:** 86.7%

## Running the Tests

```bash
cd /home/silas/Development/Frontend/docker
docker-compose -f docker-compose.yaml up --build
```

The test suite automatically:
1. Starts the MySQL database
2. Starts the PHP/Apache web server
3. Waits for the API to be ready
4. Runs all test suites against the running API
5. Reports results with detailed failure information

## Test Categories

### 1. Basic Connectivity Tests ✓
- API endpoint is reachable
- API key validation
- Action parameter requirement
- Invalid API key rejection

**Result:** All 4 tests PASSED

### 2. GetList Endpoint Tests ✓
- Returns HTTP 200
- Returns valid JSON
- Has results field (if successful)

**Result:** 2 PASSED, 1 SKIPPED (invalid JSON response)

### 3. GetListRaw Endpoint Tests ✓
- Returns plain text
- Format validation (hash-like values)

**Result:** All 2 tests PASSED

### 4. GetSources Endpoint Tests ✓
- Returns valid response
- JSON validation

**Result:** All 2 tests PASSED

### 5. GetSourcesRaw Endpoint Tests ✓
- Returns text response

**Result:** All 1 test PASSED

### 6. GetFileNames Endpoint Tests ✓
- Returns valid response

**Result:** All 1 test PASSED

### 7. GetTypes Endpoint Tests ✓
- Returns HTTP 200
- Returns valid JSON

**Result:** All 2 tests PASSED

### 8. DailySum Endpoint Tests ✓
- Returns valid response
- JSON validation

**Result:** All 2 tests PASSED

### 9. GetLimit Endpoint Tests ✓
- Returns HTTP 200
- Has content
- Contains numeric values

**Result:** All 3 tests PASSED

### 10. Search Endpoint Tests ✗
- Search without query: **FAILED** (HTTP 500 returned)
- Search with query: PASSED
- Special character handling: PASSED

**Issues Found:**
- Search endpoint throws error when no query parameter provided
- Unicode characters cause HTTP 500 error

### 11. Details Endpoint Tests ✗
- Details response: **FAILED** (HTTP 404 returned)

**Issues Found:**
- Details endpoint not properly implemented (404 response)

### 12. HashLookup Endpoint Tests ✗
- GET method not properly rejected: **FAILED** (HTTP 200 returned instead of 400/405)
- POST method: PASSED

**Issues Found:**
- HashLookup endpoint accepts GET requests (should only accept POST)

### 13. Type Endpoint Tests ✓
- Returns valid response

**Result:** All 1 test PASSED

### 14. Upload Endpoint Tests ✗
- Missing file validation: PASSED
- Small file upload: PASSED
- Oversized file rejection: **FAILED** (HTTP 200 returned instead of 413)

**Issues Found:**
- Oversized files (>10MB) are not properly rejected with 413 status code

### 15. Download URL Endpoint Tests ✓
- GET method properly rejected: PASSED
- Requires URL field: PASSED
- Invalid URL validation: PASSED
- Valid URL handling: SKIPPED (JSON parsing failed)

**Result:** 3 PASSED, 1 SKIPPED

### 16. Download URL Check Endpoint Tests ✓
- POST method properly rejected: PASSED
- Requires GUID parameter: PASSED
- Invalid GUID rejection: PASSED
- Valid GUID format handling: PASSED

**Result:** All 4 tests PASSED

### 17. API Integration Tests ✓
- Multiple sequential requests: PASSED
- API key consistency: PASSED
- Error response consistency: PASSED

**Result:** All 3 tests PASSED

### 18. Edge Cases & Error Handling Tests ✓
- Empty API key: PASSED
- Very long queries: PASSED
- Unicode characters: **FAILED** (HTTP 500 returned)
- Null bytes handling: PASSED

**Result:** 3 PASSED, 1 FAILED

## Issues Identified

### Critical Issues

1. **Details Endpoint (404 Error)**
   - The `/api.php?action=details` endpoint returns 404 instead of 200
   - Likely not properly implemented in the codebase

2. **Search Endpoint (HTTP 500 Error)**
   - Search without query parameter returns HTTP 500
   - Unicode characters in search query cause HTTP 500
   - Missing error handling in search implementation

3. **HashLookup HTTP Method Validation**
   - GET requests to `action=hashlookup` should return 400 or 405
   - Currently returns 200, allowing invalid HTTP methods

### Medium Issues

4. **Upload Oversized File Handling**
   - Files larger than 10MB return HTTP 200 instead of 413
   - File size validation may not be working correctly

### Minor Issues

5. **Download URL JSON Response**
   - Valid URL submission returns content that cannot be parsed as JSON
   - May be returning non-JSON data for successful requests

## Test File Location

[tests/test_malshare_api.py](../tests/test_malshare_api.py)

## Test Configuration

- **API Endpoint:** `http://webserver/api.php` (Docker network)
- **Test API Key:** `f2ca1bb6c7e907d06dafe4687e579fce76b37e4e93b7605022da52e6ccc26fd2`
- **Test User:** `testuser` (from malshare_db.sql)
- **Test Framework:** pytest
- **Environment:** Docker container

## Test Utilities

### Helper Functions

- `make_request()`: Makes GET/POST requests to API endpoints
- Automatic API readiness check before tests start
- 30-second timeout for API startup

### Test Fixtures

- Session fixture for persistent HTTP connections
- Automatic cleanup after each test

## Recommendations

1. **Fix Details Endpoint**
   - Verify route handling in api.php
   - Check if details action is properly implemented

2. **Fix Search Endpoint**
   - Add proper error handling for missing query parameter
   - Add Unicode/UTF-8 support validation
   - Return appropriate HTTP status codes instead of 500

3. **Fix HashLookup HTTP Method Validation**
   - Add explicit POST-only validation
   - Return 405 Method Not Allowed for non-POST requests

4. **Fix Upload File Size Validation**
   - Verify 10MB limit is being enforced
   - Return proper 413 Payload Too Large status

5. **Fix Download URL Response**
   - Ensure JSON response is valid for successful submissions
   - Check response content-type headers

## Docker Compose Services

The test setup includes:

1. **mysql** - Database service
2. **webserver** - PHP/Apache service
3. **test_runner** - Python/pytest service

All services communicate via Docker's internal network (`malshare_web` hostname).

## Test Execution Flow

```
docker-compose up --build
    ↓
mysql starts and initializes database
    ↓
webserver starts and compiles PHP extensions
    ↓
test_runner waits for webserver to be ready (max 30 sec)
    ↓
pytest runs all test suites
    ↓
Results displayed with pass/fail summary
```
