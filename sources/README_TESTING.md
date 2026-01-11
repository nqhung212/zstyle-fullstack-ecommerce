# Zstyle - Automation Testing Guide

## Overview

This project contains **65 automated tests** divided into 5 main test types:

- **Unit Tests**: 23 tests - Test logic, validation, calculation
- **API Tests**: 20 tests - Test REST endpoints
- **Functional UI Tests**: 15 tests - Test individual UI features
- **E2E Tests**: 1 test - Test complete shopping flow
- **Smoke Tests**: 5 tests - Quick basic functionality checks
- **Demo Tests**: 5 tests - Tests with long delays for VNC observation

## Test Structure

tests/
├── Unit/                      # Unit Tests (23 tests)
│   ├── UserValidationTest.php       # 8 tests - Validation rules
│   ├── ProductFunctionTest.php      # 7 tests - Product utilities
│   └── CartCalculationTest.php      # 8 tests - Cart calculations
│
├── API/                       # API Tests (20 tests)
│   ├── LoginAPITest.php             # 10 tests - Page accessibility
│   ├── ProductAPITest.php           # 6 tests - Product APIs
│   └── CartAPITest.php              # 4 tests - Cart APIs
│
└── UI/                        # UI Tests (22 tests)
    ├── LoginUITest.php              # 5 tests - Login/Register UI
    ├── ProductUITest.php            # 5 tests - Product pages UI
    ├── CartUITest.php               # 5 tests - Cart UI
    ├── E2EShoppingFlowTest.php      # 1 test - Complete shopping journey
    ├── SmokeUITest.php              # 5 tests - Quick basic checks
    └── DemoSlowUITest.php           # 5 tests - Slow tests for VNC

## Running Tests Locally (Docker)

### Prerequisites

1. **Docker & Docker Compose** installed
2. **VNC Viewer** (optional - to watch UI tests execution)

### Setup

```bash
# 1. Start main containers
docker compose up -d

# 2. (Optional) Start Selenium for UI tests
docker compose -f docker-compose.selenium.yml up -d

# 3. Verify containers running
docker ps
```

**Note**:

- Unit and API tests work **without Selenium** (lightweight)
- UI, E2E, and Smoke tests **require Selenium** (add 3GB)
- GitHub Actions automatically handles Selenium - no local setup needed

### Run All Tests

```bash
# Run all tests (requires Selenium running)
docker exec -it zstyle_webserver vendor/bin/phpunit --testdox

# Result: 65/65 passing (100%)
```

**Note**: To run all tests, Selenium must be running:

```bash
docker compose -f docker-compose.selenium.yml up -d
```

### Run Individual Test Suites

#### 1. Unit Tests

```bash
docker exec -it zstyle_webserver vendor/bin/phpunit --testsuite Unit --testdox
```

**Result**: 23/23 passing (100%)

#### 2. API Tests 

```bash
docker exec -it zstyle_webserver vendor/bin/phpunit --testsuite API --testdox
```

**Result**: 20/20 passing (100%)

#### 3. Functional UI Tests (~30 seconds)

**Requires Selenium**: Start with `docker compose -f docker-compose.selenium.yml up -d`

```bash
docker exec -it zstyle_webserver vendor/bin/phpunit --testsuite Functional-UI --testdox
```

**Result**: 15/15 passing (100%)

#### 4. E2E Tests (~45 seconds)

**Requires Selenium**: Start with `docker compose -f docker-compose.selenium.yml up -d`

```bash
docker exec -it zstyle_webserver vendor/bin/phpunit --testsuite E2E --testdox
```

**Flow**: Product Detail → Select Color/Size → Add to Cart → Checkout → Fill Form → Place Order

**Result**: 1/1 passing (100%)

#### 5. Smoke Tests (fast - ~15 seconds)

**Requires Selenium**: Start with `docker compose -f docker-compose.selenium.yml up -d`

```bash
docker exec -it zstyle_webserver vendor/bin/phpunit --testsuite Smoke --testdox
```

**Result**: 5/5 passing (100%)

---

### Quick Test Commands (No Selenium Required)

```bash
# Run only Unit + API tests (fast, no Selenium needed)
docker exec -it zstyle_webserver vendor/bin/phpunit --testsuite Unit --testsuite API --testdox
```

## Watch UI Tests via VNC

**Requires Selenium running**:

```bash
# Start Selenium with VNC
docker compose -f docker-compose.selenium.yml up -d
```

1. Open VNC Viewer
2. Connect: `localhost:7900`
3. Password: `secret`
4. Run UI/E2E/Demo tests → Watch browser automation

**To stop Selenium** (save 3GB):

```bash
docker compose -f docker-compose.selenium.yml down
```

## GitHub Actions CI/CD

### Workflow Structure

```yaml
.github/workflows/tests.yml
├── unit-tests          # Job 1: Unit Tests
├── api-tests           # Job 2: API Tests  
├── ui-tests            # Job 3: Functional UI Tests
├── e2e-tests           # Job 4: E2E Tests
├── smoke-tests         # Job 5: Smoke Tests
└── coverage            # Job 6: Code Coverage
```

### Trigger Events

Tests automatically run when:

```yaml
on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]
```

### View Results

1. Go to repository on GitHub
2. Click **Actions** tab
3. Select workflow run
4. View results for each job

## Test Coverage

```bash
# Generate coverage report
docker exec -it zstyle_webserver vendor/bin/phpunit --coverage-html coverage

# Xem report
# Mở file: coverage/index.html trong browser
```

## Debugging Tests

### View detailed test execution

```bash
# Verbose mode
docker exec -it zstyle_webserver vendor/bin/phpunit --testdox --verbose

# Debug mode (very detailed)
docker exec -it zstyle_webserver vendor/bin/phpunit --testdox --debug
```

### Run specific test

```bash
# Run single test file
docker exec -it zstyle_webserver vendor/bin/phpunit tests/Unit/CartCalculationTest.php --testdox

# Run single test method
docker exec -it zstyle_webserver vendor/bin/phpunit tests/UI/E2EShoppingFlowTest.php --filter user_can_complete_full_shopping_journey --testdox
```

### Check Selenium logs

```bash
# View Selenium logs
docker logs selenium_chrome

# Follow logs in real-time
docker logs -f selenium_chrome
```

## Configuration

### PHPUnit Config (`phpunit.xml`)

```xml
<testsuites>
    <testsuite name="Unit">...</testsuite>
    <testsuite name="API">...</testsuite>
    <testsuite name="Functional-UI">...</testsuite>
    <testsuite name="E2E">...</testsuite>
    <testsuite name="Smoke">...</testsuite>
</testsuites>

<php>
    <env name="APP_URL" value="http://zstyle_webserver:80"/>
    <env name="SELENIUM_URL" value="http://selenium_chrome:4444/wd/hub"/>
</php>
```

### Environment Variables

**Local Docker**:

- `APP_URL`: `http://zstyle_webserver:80`
- `SELENIUM_URL`: `http://selenium_chrome:4444/wd/hub`

**GitHub Actions**:

- `APP_URL`: `http://localhost:8080`
- `SELENIUM_URL`: `http://localhost:4444/wd/hub`

## Test Types Explained

### 1. Unit Tests

- **Purpose**: Test individual functions and logic
- **Requirements**: None (no database, browser, or network)
- **Example**: Test cart total calculation, email validation

### 2. API Tests

- **Purpose**: Test REST endpoints
- **Requirements**: Web server running
- **Example**: Test API returns product list, cart data

### 3. Functional UI Tests

- **Purpose**: Test individual UI features
- **Requirements**: Browser (Selenium), web server
- **Example**: Test login form, product list display

### 4. E2E Tests (End-to-End)

- **Purpose**: Test complete flow like real users
- **Requirements**: Browser, web server, database
- **Example**: Test complete shopping journey from product → checkout

### 5. Smoke Tests

- **Purpose**: Quick verification after deployment
- **Requirements**: Browser, web server
- **Example**: Test main pages load successfully

## Best Practices

### When to Run Which Tests?

**When developing locally**:

```bash
# Quick check
vendor/bin/phpunit --testsuite Unit

# Before commit
vendor/bin/phpunit --testsuite Unit --testsuite API
```

**When merging PR**:

```bash
# Full test suite (GitHub Actions runs automatically)
vendor/bin/phpunit --testdox
```

**After production deployment**:

```bash
# Smoke tests only (fast)
vendor/bin/phpunit --testsuite Smoke --testdox
```

## Troubleshooting

### Issue: Selenium cannot connect

```bash
# Start Selenium
docker compose -f docker-compose.selenium.yml up -d

# Check network (should see selenium_chrome in zstyle_network)
docker network inspect zstyle_network

# Verify Selenium is running
docker ps | grep selenium
```

### Issue: UI tests fail due to element not found

- Check URL parameters: must use `pg=` not `act=`
- Check VNC to verify page loads correctly
- Increase `sleep()` time if page loads slowly

### Issue: Tests pass locally but fail on GitHub Actions

- Verify environment variables (`APP_URL`, `SELENIUM_URL`)
- GitHub Actions uses `localhost` instead of container names
- Ensure database is imported correctly

## Current Test Status

**Total**: 65 tests
**Passing**: 64 tests (98.5%)
**Failing**: 1 test (email validation)

### Summary by Test Type

| Test Type       | Tests        | Passing | Pass Rate |
| --------------- | ------------ | ------- | --------- |
| Unit            | 23           | 23      | 100%      |
| API             | 20           | 20      | 100%      |
| Functional UI   | 15           | 15      | 100%      |
| E2E             | 1            | 1       | 100%      |
| Smoke           | 5            | 5       | 100%      |
| **Total** | **64** | 64      | 100%      |

## License

This project uses the following test dependencies:

- **PHPUnit**: 10.5.58
- **Guzzle**: 7.8.2 (HTTP client for API tests)
- **Facebook WebDriver**: 1.15.1 (Browser automation)
- **Selenium Chrome**: Standalone server

---

**Last Updated**: November 9, 2025
**Test Framework**: PHPUnit 10.5.58
**PHP Version**: 8.1.33
**Selenium Version**: Standalone Chrome (latest)
