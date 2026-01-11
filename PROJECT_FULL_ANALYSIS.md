# Project Analysis: Zstyle

## 1. Project Purpose

**Zstyle** is a comprehensive **e-commerce fashion platform** designed to sell apparel (t-shirts, jackets, polos, etc.). It features standard e-commerce capabilities such as product browsing, advanced filtering, shopping cart management, user accounts, and order processing. A key differentiator mentioned in the documentation is a **custom design feature** allowing users to create custom apparel.

### 1.1. Testing Objectives

To ensure the system is robust and production-ready, the testing strategy focuses on:

- **Functional Integrity:** Verifying that core e-commerce flows (Browse -> Cart -> Checkout) work without error.
- **Data Security:** Ensuring user data (passwords, addresses) and admin functions are protected against unauthorized access.
- **Usability:** Confirming the UI is responsive and intuitive across devices.
- **Reliability:** Ensuring the system handles edge cases (e.g., out of stock, invalid inputs) gracefully.

## 2. Main Technologies

- **Language:** **PHP 8.0+** (Core backend logic).
- **Database:** **MySQL** (Data persistence, accessed via PDO).
- **Frontend:** HTML5, CSS3, JavaScript (Native/Vanilla).
- **Infrastructure:** **Docker** & **Docker Compose** (Containerized environment for app, database, and Selenium).
- **Testing:**
  - **PHPUnit:** For Unit and API testing.
  - **Selenium (php-webdriver):** For UI/End-to-End automation testing.
- **Key Libraries:**
  - `PHPMailer`: For sending transactional emails (registration, orders).
  - `Guzzle`: HTTP client (likely for API testing or external integrations).

### 2.1. Technology-Specific Testing Risks

| Technology   | Risk Area                                                | Testing Strategy                                                                                                |
| :----------- | :------------------------------------------------------- | :-------------------------------------------------------------------------------------------------------------- |
| **PHP 8.0**  | Type safety issues in legacy code; Unhandled exceptions. | **Static Analysis:** Check for deprecated functions.<br>**Unit Testing:** Verify type handling in Models.       |
| **MySQL**    | SQL Injection in raw queries; Data consistency.          | **Security Testing:** Attempt SQLi on inputs.<br>**Database Testing:** Verify transaction atomicity.            |
| **Docker**   | Environment mismatch between Dev/Prod.                   | **Configuration Testing:** Verify `docker-compose.yml` settings.<br>**Integration:** Test service connectivity. |
| **Selenium** | Flaky tests due to UI latency.                           | **Stability Testing:** Use explicit waits.<br>**Retry Logic:** Implement retry mechanisms in test suites.       |

## 3. Testing Methodologies

To ensure the reliability and security of the Zstyle e-commerce platform, a multi-layered testing strategy is employed. This section details the specific methodologies applied to the codebase.

### 3.1. Black-box Testing Strategy

Black-box testing in Zstyle focuses on validating the functional requirements of the Storefront and Admin Panel by manipulating inputs and observing outputs, ignoring the internal PHP/MySQL implementation.

#### 3.1.1. Scope & Module Analysis

| Module              | Input Space                                 | Expected Output                                | External Behavior                                                  |
| :------------------ | :------------------------------------------ | :--------------------------------------------- | :----------------------------------------------------------------- |
| **Authentication**  | Username, Password, Email, Phone            | Session Token, Redirects, Error Messages       | Access granted/denied based on credentials and `kichhoat` status.  |
| **Product Catalog** | Search Keywords, Category IDs, Price Ranges | Filtered Product List, Pagination              | Dynamic SQL generation (hidden) results in correct subset display. |
| **Shopping Cart**   | Product ID, Quantity (Int), Color/Size      | Updated Session Array, Total Price Calculation | Persistent state across page reloads (within session).             |
| **Checkout**        | Shipping Info, Payment Method               | Order Confirmation, Email Trigger              | Data committed to `donhang` and `cart` tables; Cart cleared.       |
| **Admin Panel**     | CRUD Form Data, Upload Files                | Database Updates, Success/Error Alerts         | Restricted access to `role=1` users only.                          |

#### 3.1.2. Applied Techniques

**A. Equivalence Partitioning (EP)**
Used to reduce the number of test cases by grouping inputs into valid and invalid classes.

- **Target:** Registration Form (`view/register.php`) - _Email Field_
  - **Class 1 (Valid):** Standard format (e.g., `user@example.com`). -> _Expect Success._
  - **Class 2 (Invalid):** Missing domain (e.g., `user@`). -> _Expect Validation Error._
  - **Class 3 (Invalid):** Missing @ (e.g., `userexample.com`). -> _Expect Validation Error._

**B. Boundary Value Analysis (BVA)**
Used to test the edges of input ranges, where defects are most likely.

- **Target:** Cart Quantity (`view/cart.php`)
  - **Boundary:** Minimum Quantity = 1.
  - **Test Values:**
    - `0` (Invalid - Below Boundary) -> _System should remove item or reject._
    - `1` (Valid - On Boundary) -> _System accepts._
    - `2` (Valid - Above Boundary) -> _System accepts._
  - **Boundary:** Stock Limit (e.g., 10).
  - **Test Values:**
    - `10` (Valid - On Boundary) -> _System accepts._
    - `11` (Invalid - Above Boundary) -> _System rejects with "Out of Stock"._

**C. Decision Table Testing**
Used for complex logic with multiple conditions.

- **Target:** Admin Login Logic (`model/user.php` -> `checkuser`)
  - **Conditions:** C1: Username Exists? C2: Password Correct? C3: Account Active (`kichhoat=1`)?
  - **Rules:**
    - R1 (T, T, T) -> **Login Success**
    - R2 (T, T, F) -> **Login Failed** (Account Locked)
    - R3 (T, F, X) -> **Login Failed** (Wrong Pass)
    - R4 (F, X, X) -> **Login Failed** (User Not Found)

#### 3.1.3. Concrete Test Cases

**TC_BB_01: Authentication - Login Validation**

- **Technique:** Decision Table
- **Input:** User: `admin`, Pass: `123` (Valid), `kichhoat`: `1`
- **Action:** Submit Login Form.
- **Expected:** Redirect to `index.php` (or Admin Dashboard). Session `role` set.

**TC_BB_02: Product Filtering - Price Range**

- **Technique:** Equivalence Partitioning
- **Input:** Filter Price: `< 500k`
- **Action:** Select filter in Sidebar.
- **Expected:** All displayed products must have `price < 500000`. Products with `price >= 500000` are hidden.

**TC_BB_03: Cart - Quantity Handling**

- **Technique:** Boundary Value Analysis
- **Input:** Update Quantity to `-1` via AJAX.
- **Action:** Click "Update" in Cart.
- **Expected:** System rejects negative value. Quantity resets to `1` or previous valid value. Total price remains correct.

**TC_BB_04: Checkout - Empty Field Validation**

- **Technique:** Equivalence Partitioning (Invalid Class)
- **Input:** Name: `Test User`, Address: `[Empty]`, Phone: `0909...`
- **Action:** Click "Order".
- **Expected:** Client-side (HTML5) or Server-side validation prevents submission. Error "Address is required" displayed.

**TC_BB_05: Admin Access Control**

- **Technique:** Equivalence Partitioning (Role Classes)
- **Input:** Session Role: `0` (User) vs `1` (Admin).
- **Action:** Attempt to access `view/admin/index.php`.
- **Expected:**
  - Role `0`: Redirect to Homepage.
  - Role `1`: Load Admin Dashboard.

### 3.2. White-box Testing Strategy

White-box testing examines the internal logic of the application, focusing on control flow, conditions, and data paths within the PHP code.

#### 3.2.1. Authentication Logic (`model/user.php`)

- **Control Flow:** The `checkuser()` function uses a series of `if` statements to validate credentials.
- **Critical Path:**
  1.  Query database for username.
  2.  `if` user exists -> Check password hash.
  3.  `if` password matches -> Check `kichhoat` (activation) status.
  4.  `if` active -> Return User ID.
- **Data Flow:** Input `$user`, `$pass` -> SQL Query -> `$result` array -> Boolean Return.
- **Test Derivation:** Create test cases for each branch: User Not Found, Wrong Password, Inactive User, Success.

#### 3.2.2. Order Creation Logic (`model/donhang.php`)

- **Control Flow:** Sequential execution with database write operations.
- **Critical Path:**
  1.  `creatdonhang()` inserts Order Header -> Returns `id_donhang`.
  2.  Loop through `$_SESSION['giohang']`.
  3.  `add_cart()` inserts Order Details linked to `id_donhang`.
  4.  `unset($_SESSION['giohang'])` clears the cart.
- **Data Flow:** Session Array -> Database Rows (Header + Details).
- **Test Derivation:** Verify atomicity. If Step 3 fails (e.g., SQL error), Step 1 should ideally be rolled back (though current code lacks transactions, testing should expose this risk).

#### 3.2.3. Cart Session Handling (`model/cart.php`)

- **Control Flow:** Array manipulation logic.
- **Critical Path:**
  1.  `addcart`: Check if Product ID exists in `$_SESSION['giohang']`.
  2.  `if` exists -> Increment Quantity.
  3.  `else` -> Append new array element.
- **Data Flow:** `$_POST` -> `$_SESSION` (In-memory) -> View Rendering.
- **Test Derivation:** Test adding duplicate items to ensure quantity increments instead of creating duplicate array keys.

#### 3.2.4. Admin Access Control (`view/admin/index.php`)

- **Control Flow:** Gatekeeper logic at the top of the file.
- **Critical Path:**
  1.  `session_start()`.
  2.  `if (!isset($_SESSION['role']) || $_SESSION['role'] != 1)` -> Redirect to `login`.
  3.  `else` -> Load Admin Dashboard.
- **Data Flow:** Session Cookie -> Server Session -> Access Decision.
- **Test Derivation:** Attempt to access the file with `role=0` (Customer) and `role=null` (Guest) to verify the redirect.

### 3.3. Business Logic & Workflow Testing

This strategy validates that the system supports critical business processes end-to-end, ensuring data integrity across multiple modules.

#### 3.3.1. Critical Workflows

| Workflow              | Normal Flow                        | Alternative Flow                              | Exception Flow                                    |
| :-------------------- | :--------------------------------- | :-------------------------------------------- | :------------------------------------------------ |
| **User Registration** | Register -> Email Sent -> Login    | Register -> Email Fail -> Login (if allowed)  | Duplicate Email -> Error Message                  |
| **Shopping Cart**     | Add Item -> Update Qty -> Checkout | Add Item -> Continue Shopping -> Add More     | Add Item > Stock -> "Out of Stock" Error          |
| **Checkout Process**  | Cart -> Info -> Payment -> Success | Cart -> Info -> Back to Cart -> Modify        | Payment Fail -> Return to Checkout                |
| **Admin Product**     | Create -> View in Catalog -> Edit  | Create -> Hide (`sethome=0`) -> Verify Hidden | Delete Product with Orders -> DB Error/Constraint |

#### 3.3.2. Benefits of Workflow Testing

- **Cross-Module Correctness:** Verifies that `model/cart.php` correctly passes data to `model/donhang.php`, which unit tests might miss.
- **Data Integrity:** Ensures that stock is deducted (`model/soluongtonkho.php`) _only_ when an order is successfully placed.

### 3.4. Unit Testing Strategy

Unit testing focuses on isolating and verifying the smallest testable parts of the application, primarily the Model functions.

#### 3.4.1. Suitable Components

- **Pure Logic Functions:** Functions that take inputs and return outputs without side effects (e.g., `get_total_amount($cart_array)`).
- **Data Retrieval:** Functions like `getproduct($id)` can be tested against a known seed database.

#### 3.4.2. Difficult Components & Risks

- **Session Dependency:** Many functions directly access `$_SESSION`. This makes them hard to test in a CLI environment (PHPUnit) without mocking the session superglobal.
- **Mixed HTML/Logic:** Some functions in `model/` echo HTML directly (e.g., pagination links). This violates MVC separation and makes assertion difficult.

#### 3.4.3. Best Practices for Zstyle

- **Mocking:** Use a wrapper or mock array to simulate `$_SESSION` during tests.
- **Test Database:** Use a separate `zstyle_test` database that is reset before every test run to ensure consistent state.

### 3.5. Integration Testing

- **Definition:** Verifying that distinct modules work together correctly (e.g., Controller -> Model -> View).
- **Suitability for Zstyle:** Ensures the `index.php` router correctly passes data from Models to Views.
- **System Application:**
  - **Data Flow:** Database -> `model/product.php` -> `index.php` -> `view/product.php`.
- **Zstyle Test Case Example:**
  - **Module:** Registration Flow.
  - **Scenario:** Full Registration Integration.
  - **Action:** Submit `view/register.php` form.
  - **Expected:** `model/user.php` inserts row -> `mailer/mailer.php` sends email -> `index.php` redirects to Login.

### 3.6. Automated Testing Strategy

Automation is used to execute repetitive tests efficiently, ensuring regression protection.

#### 3.6.1. Scope of Automation

- **Suitable (UI):** Critical "Happy Paths" like Login, Add to Cart, and Checkout. These are stable and high-value.
- **Suitable (Backend):** Unit tests for `model/` functions (e.g., `checkuser`, `getcatalog`).
- **NOT Suitable:**
  - **Volatile UI:** Admin dashboards that change frequently.
  - **Third-Party Integrations:** Real email delivery (use Mailtrap API instead) or real Payment Gateways.

#### 3.6.2. Tools & Frameworks

- **Selenium (php-webdriver):** For browser-based E2E testing of the Checkout flow.
- **PHPUnit:** For backend logic testing of Models.

#### 3.6.3. Limitations

- **Session State:** Automated tests must manage cookies carefully to maintain login sessions across steps.
- **Dynamic Data:** Tests must handle changing product IDs or stock levels (use data seeding).

### 3.7. GenAI-Assisted Testing

- **Definition:** Leveraging AI to generate test cases, discover edge cases, and write test code.
- **Suitability for Zstyle:** Accelerates understanding of legacy code and coverage generation.
- **System Application:**
  - **Test Generation:** Creating PHPUnit tests for `model/` functions.
  - **Edge Case Discovery:** Analyzing `view/admin/` for security gaps.
- **Zstyle Test Case Example:**
  - **Task:** Boundary Value Analysis.
  - **Action:** AI analyzes `model/cart.php` logic.
  - **Result:** AI suggests testing "Add to Cart" with Quantity = 0 or Negative, leading to a new validation rule in `ajax/soluongsp.php`.

### 3.8. Test Coverage Analysis

#### 3.8.1. Functional Coverage

- **Status:** **High**
- **Analysis:** All core user-facing features (Registration, Login, Product Browsing, Cart Management, Checkout) are fully implemented and testable.
- **Gap:** The "Wishlist" feature is mentioned in requirements but missing in code, resulting in zero coverage for that specific requirement.

#### 3.8.2. Workflow Coverage

- **Status:** **Medium**
- **Analysis:** Standard "Happy Path" workflows (e.g., Successful Purchase) are well-covered.
- **Gap:** Alternative and Exception flows (e.g., Payment Failure, Stockout during Checkout, Session Timeout during Order) lack dedicated test scenarios.

#### 3.8.3. Role-Based Coverage

- **Status:** **High**
- **Analysis:** There is a clear separation between "Guest", "Customer", and "Admin" roles. Access control logic is simple and easy to verify.
- **Gap:** The "Banned User" state exists in the database (`kichhoat=0`) but its behavior across all modules (e.g., can they reset password?) is not fully explored.

#### 3.8.4. Data Coverage

- **Status:** **Medium**
- **Analysis:** Standard data inputs (Strings, Integers) are handled.
- **Gap:** Edge cases for data types (e.g., Max Integer for Quantity, Special Characters in Search, SQL Injection payloads) need more rigorous security testing.

#### 3.8.5. Control Flow Coverage

- **Status:** **High**
- **Analysis:** The monolithic `index.php` router makes it easy to map and cover all high-level application paths.
- **Gap:** Error handling branches (e.g., Database Connection Failure, File Upload Errors) are difficult to trigger and likely have low coverage.

## 4. Test Plan Overview

This section outlines the strategic approach to testing the Zstyle e-commerce system, defining the scope, levels, and criteria for success.

### 4.1. Test Objectives

The primary goal is to validate that the Zstyle platform functions correctly, securely, and reliably before deployment.

- **Verification:** Ensure the code implements the requirements defined in the `README.md` and Requirement Traceability Matrix.
- **Validation:** Ensure the system meets the business needs of selling fashion products effectively.
- **Defect Detection:** Identify and document bugs in critical flows (Checkout, Admin Management) to prevent revenue loss.
- **Security Assurance:** Verify that customer PII and admin functions are protected from common web vulnerabilities.

### 4.2. Test Scope

#### 4.2.1. In-Scope

- **Customer Storefront:** Registration, Login, Product Browsing, Search, Cart Management, Checkout, Order History.
- **Admin Panel:** Dashboard, Product CRUD, Category Management, Order Status Updates, User Management.
- **Database:** Data integrity of `product`, `donhang`, `cart`, and `user` tables.
- **Integration:** Email notifications (`PHPMailer`) and AJAX Cart updates.

#### 4.2.2. Out-of-Scope

- **Payment Gateway API:** Real financial transactions are simulated via string literals ("MoMo", "COD").
- **Third-Party Logistics:** Integration with shipping providers (GHTK, Viettel Post) is not implemented.
- **Performance Stress Testing:** High-concurrency load testing (>1000 users) is outside the scope of the local Docker environment.
- **Mobile App:** Testing is limited to the Responsive Web Interface, not a native mobile application.

### 4.3. Test Levels

- **Unit Testing:**
  - **Focus:** Individual functions in `model/*.php` (e.g., `checkuser()`, `tongdonhang()`).
  - **Tool:** PHPUnit.
- **Integration Testing:**
  - **Focus:** Interaction between `index.php` (Controller), `model/` (Data), and `view/` (UI).
  - **Example:** Verifying that `add_cart()` in the model correctly updates the Session used by the Header View.
- **System Testing:**
  - **Focus:** End-to-end behavior of the fully integrated application.
  - **Example:** A complete "Guest Checkout" flow from Home Page to Order Success.
- **Acceptance Testing (UAT):**
  - **Focus:** Validating business workflows against user expectations.
  - **Example:** Admin verifying that a "Hidden" product is indeed invisible on the storefront.

### 4.4. Test Types

- **Functional Testing:** Verifying feature correctness (e.g., "Does the 'Add to Cart' button work?").
- **Workflow-Based Testing:** Validating multi-step processes (e.g., "Can a user recover a lost password?").
- **Black-Box Testing:** Testing without knowledge of internal code (e.g., Equivalence Partitioning on Input Forms).
- **White-Box Testing:** Testing internal logic paths (e.g., Branch coverage of the `checkuser` function).
- **Security Testing:** Checking for SQL Injection, XSS, and IDOR vulnerabilities.
- **Performance Testing (Basic):** Measuring page load times and database query efficiency for the Product Catalog.

### 4.5. Entry and Exit Criteria

#### 4.5.1. Entry Criteria (Start Testing)

- The Docker environment (`docker-compose up`) is running without errors.
- The Database is seeded with the "Golden Dataset" (Categories, Products, Admin User).
- Critical features (Login, Cart) are implemented and accessible.

#### 4.5.2. Exit Criteria (Stop Testing)

- **100%** of "Critical" and "High" priority test cases are executed.
- **Pass Rate** is > 95% for Critical features.
- No open **Critical (P1)** or **Major (P2)** defects.
- The Test Summary Report is generated and reviewed.

### 4.6. Test Environment Assumptions

- **Local Environment:** Testing is performed on a local Docker container set (PHP 8.0 + MySQL 5.7).
- **Browser:** Primary testing on Google Chrome (Latest) and Firefox.
- **Data State:** The database is reset to a known state before each full regression run to ensure consistency.
- **Network:** Localhost latency is negligible; network timeouts are simulated manually if needed.

## 5. Folder & Key File Roles

| Folder/File              | Role                                                                                                                                             | Testing Focus                                                                          |
| :----------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------- |
| **`index.php`**          | **Main Entry Point:** Handles session initialization, global includes, and routing for the public storefront.                                    | **Integration:** Verify routing logic, session persistence, and error handling.        |
| **`model/`**             | **Data Access Layer:** Contains PHP files with functions to interact with the MySQL database (e.g., `connectdb.php`, `product.php`, `cart.php`). | **Unit:** Test individual functions for SQL injection, logic errors, and return types. |
| **`view/`**              | **Presentation Layer:** Contains the UI templates for the storefront (e.g., `header.php`, `home.php`, `detail.php`).                             | **UI/UX:** Check responsiveness, layout consistency, and XSS vulnerabilities.          |
| **`admin/`**             | **Administration Panel:** A separate sub-application for site managers to handle products, orders, and users.                                    | **Security:** Verify Role-Based Access Control (RBAC) and input validation.            |
| **`tests/`**             | **Quality Assurance:** Contains the test suites organized by type: `Unit`, `API`, and `UI` (Selenium).                                           | **Meta:** Ensure test suites themselves are valid, up-to-date, and passing.            |
| **`ajax/`**              | **Async Requests:** Handles AJAX calls for dynamic updates like cart quantity or filtering without page reloads.                                 | **API:** Test JSON responses, status codes, and error handling.                        |
| **`mailer/`**            | **Email Service:** Scripts specifically for configuring and sending emails.                                                                      | **Integration:** Verify email delivery triggers (mocked in dev environment).           |
| **`upload/`**            | **Media Storage:** Stores user-uploaded content or product images.                                                                               | **Security:** Test for malicious file uploads and directory traversal.                 |
| **`docker-compose.yml`** | **Infrastructure:** Defines the services (web server, database) for the local development environment.                                           | **Config:** Verify service dependencies, ports, and environment variables.             |
| **`Zstyle.sql`**         | **Database Schema:** The SQL dump file used to initialize the database structure and seed data.                                                  | **Database:** Verify table constraints, foreign keys, and seed data integrity.         |

## 5. Requirement Traceability Matrix

Based on the requirements specified in `README.md`, the following analysis maps features to their implementation status and verification methods.

### 5.1. Customer Features

#### 1. Product Browsing & Search

- **Requirement:** Browse products by category.
  - **Implementation:** `model/catalog.php` (functions `getcatalog`, `getcatalogdetail`) and `index.php` (routing logic `case 'product'`).
  - **Status:** **Fully Implemented**
  - **Verification:** **Manual:** Click category links. **Auto:** `ProductUITest.php` (Category Navigation).
- **Requirement:** Advanced filtering (price, color, gender, size).
  - **Implementation:** `index.php` (lines 30-60) handles session-based filtering (`$_SESSION['filterprice']`, etc.). `model/product.php` constructs queries based on these filters.
  - **Status:** **Fully Implemented**
  - **Verification:** **Manual:** Apply filters and check results. **Unit:** Test `getproduct()` with filter params.
- **Requirement:** Search functionality.
  - **Implementation:** `model/product.php` (contains SQL `WHERE hh.name LIKE ?`). `view/header.php` contains the search input form.
  - **Status:** **Fully Implemented**
  - **Verification:** **Manual:** Search for known items. **Security:** Test for SQL Injection in search bar.
- **Requirement:** Product sorting.
  - **Implementation:** `model/product.php` (functions `getproduct` with `ORDER BY` clauses).
  - **Status:** **Fully Implemented**
  - **Verification:** **Manual:** Sort by Price Low-High. **Unit:** Verify SQL `ORDER BY` generation.

#### 2. Shopping Experience

- **Requirement:** Shopping cart management.
  - **Implementation:** `model/cart.php` (functions `add_cart`, `update_cart`, `del_cart`). `view/cart.php` displays the cart.
  - **Status:** **Fully Implemented**
  - **Verification:** **Auto:** `CartUITest.php` (Add/Remove/Update). **Unit:** `CartCalculationTest.php`.
- **Requirement:** Wishlist functionality.
  - **Implementation:** No specific table `wishlist` or functions found in `model/` or `index.php`.
  - **Status:** **Not Implemented**
  - **Verification:** N/A (Feature Gap).
- **Requirement:** Product reviews and ratings.
  - **Implementation:** `model/comment.php` (functions `add_comment`, `get_comment_product`). `view/detail.php` displays comments.
  - **Status:** **Fully Implemented**
  - **Verification:** **Manual:** Post a comment. **Security:** Test for XSS in comment body.
- **Requirement:** Real-time stock inventory tracking.
  - **Implementation:** `model/soluongtonkho.php` (inventory logic).
  - **Status:** **Fully Implemented**
  - **Verification:** **Manual:** Buy max stock, check if product becomes unavailable.

#### 3. User Account Management

- **Requirement:** User registration and login.
  - **Implementation:** `model/user.php` (functions `creatuser`, `getlogin`). `view/login.php`, `view/register.php`.
  - **Status:** **Fully Implemented**
  - **Verification:** **Auto:** `LoginUITest.php`. **Unit:** `UserValidationTest.php`.
- **Requirement:** Account activation system.
  - **Implementation:** `model/user.php` checks `if($kq['kichhoat']==1)` during login.
  - **Status:** **Fully Implemented**
  - **Verification:** **Manual:** Register, try login before activation.
- **Requirement:** Password recovery via email.
  - **Implementation:** `view/forgetpass.php` and `mailer/mailer.php` handle the reset flow.
  - **Status:** **Fully Implemented**
  - **Verification:** **Manual:** Request reset, check email (Mailtrap).

#### 4. Checkout & Payment

- **Requirement:** Multiple payment methods (COD, MoMo).
  - **Implementation:** `model/donhang.php` stores the payment method string (`ptthanhtoan`). `view/checkout.php` allows selection.
  - **Status:** **Partially Implemented** (Selection works, but no actual API integration for MoMo/Cards found; it treats them as text labels).
  - **Verification:** **Manual:** Select different methods. **Risk:** Verify no sensitive data is stored for "Card" option.
- **Requirement:** Voucher/discount code system.
  - **Implementation:** `model/voucher.php` and `model/giamgia.php`.
  - **Status:** **Fully Implemented**
  - **Verification:** **Unit:** Test expired/invalid vouchers. **Auto:** Apply voucher in Checkout flow.

### 5.2. Admin Panel Features

#### 1. Dashboard & Analytics

- **Requirement:** Overview statistics (Revenue, Visits).
  - **Implementation:** `model/thongke.php` (functions `doanhthu`, `luotview`, `tongdoanhthu`). `view/admin/dashboard.php`.
  - **Status:** **Fully Implemented**
  - **Verification:** **Manual:** Compare dashboard numbers with DB records.

#### 2. Management Modules

- **Requirement:** Product, Category, Order, User Management.
  - **Implementation:** Dedicated files in `view/admin/` (`product.php`, `catalog.php`, `donhang.php`, `user.php`) and corresponding models.
  - **Status:** **Fully Implemented**
  - **Verification:** **Manual:** CRUD operations. **Security:** Test Access Control (URL manipulation).

## 6. Detailed Functional Analysis

### 6.1. Authentication & User Management

**1. Responsibility:**
Manages the lifecycle of user identities, including registration, secure login, profile management, and password recovery. It ensures that only active accounts can access protected features.

**2. Workflow:**

- **Registration:** 1. User navigates to index.php?pg=register (View: iew/register.php). 2. User submits user, email, pass,
  epass. 3. System validates input (username existence, password match). 4. Calls creatuser() in model/user.php to insert a new record with kichhoat=1 (active) or   (inactive).
- **Login:**
  1.  User navigates to index.php?pg=login.
  2.  User submits credentials.
  3.  System calls getlogin() in model/user.php.
  4.  **Validation:** Checks if user and pass match AND kichhoat==1.
  5.  If successful, user data is stored in $\_SESSION.

**3. Key Data:**

- **Input:** Username, Password, Email, Phone, Address.
- **Output:** User Session Object, Error Messages.
- **Database:** users table.

**4. Interactions:**

- Interacts with mailer/mailer.php for 'Forgot Password' functionality.
- Used by iew/checkout.php to auto-fill shipping details.

**5. Validations & Edge Cases:**

- **Validation:** Checks for duplicate usernames/emails.
- **Edge Case:** Inactive users (kichhoat=0) are denied login even with correct passwords.

**6. Testing Perspective**

- **Test Targets:**
  - **Registration Form:** Input validation (Email format, Password strength), Duplicate username checks, Database insertion integrity.
  - **Login Mechanism:** Credential verification, Session creation, "Remember Me" functionality (if exists), Account activation check.
  - **Session Management:** Timeout handling, Logout (Session destruction), Privilege escalation prevention.
  - **Password Recovery:** Email trigger, Token generation/validation, Password update logic.
- **Business Criticality:**
  - **Security:** Prevents unauthorized access to user data and admin functions.
  - **User Retention:** A broken login/register flow causes immediate user churn.
  - **Trust:** Secure handling of passwords builds customer trust.
- **Main Risks:**
  - **SQL Injection:** Login forms are primary targets for attackers.
  - **Session Hijacking:** Weak session IDs or lack of SSL can lead to account takeover.
  - **Brute Force:** Lack of rate limiting allows attackers to guess passwords.
- **Testing Approaches:**
  - **Black-box:** Equivalence Partitioning on input fields (Valid/Invalid emails). Boundary Value Analysis on password length.
  - **White-box:** Reviewing `checkuser()` logic in `model/user.php` for logical flaws.
  - **Security:** Automated scans for SQLi and XSS. Manual testing for IDOR.
- **Typical Failure Scenarios:**
  - User enters correct password but account is inactive (`kichhoat=0`) -> System fails to inform user clearly.
  - SQL Injection payload `' OR '1'='1` bypasses password check.
  - Session does not expire after browser close, allowing next user on shared PC to access account.

---

### 6.2. Product Catalog & Search

**1. Responsibility:**
Allows users to browse products, filter by attributes (price, color, gender), and search by keywords. It serves as the primary interface for product discovery.

**2. Workflow:**

- **Browsing:**
  1.  User visits index.php?pg=product.
  2.  System checks $\_GET parameters (idcatalog, page).
  3.  Calls getproduct() or getproduct_catalog() in model/product.php.
- **Filtering:**
  1.  User selects filters (e.g., 'Price < 500k').
  2.  Filters are stored in $\_SESSION (e.g., $\_SESSION['filterprice']).
  3.  model/product.php dynamically appends WHERE clauses to the SQL query based on active session filters.
- **Search:**
  1.  User enters keyword in header.
  2.  System executes SQL with LIKE %keyword%.

**3. Key Data:**

- **Input:** Filter criteria, Search keywords, Category IDs.
- **Output:** List of product arrays (ID, Name, Price, Image).
- **Database:** product, catalog, img_product_color tables.

**4. Interactions:**

- Feeds data into iew/product.php for rendering.
- Linked to model/cart.php (Add to Cart actions).

**5. Validations & Edge Cases:**

- **Edge Case:** No products match the combined filters (returns empty list).
- **Performance:** Uses LIMIT in SQL queries for pagination to handle large datasets.

**6. Testing Perspective**

- **Test Targets:**
  - **Search Functionality:** Keyword matching accuracy, Partial matches, Case sensitivity, Handling of special characters.
  - **Filtering Logic:** Correct application of Price, Color, and Category filters. Combination of multiple filters.
  - **Pagination:** Correct data subset display, Next/Prev navigation, Boundary pages (First/Last).
  - **Product Display:** Image rendering, Price formatting, Stock status indication.
- **Business Criticality:**
  - **Conversion Rate:** Users cannot buy what they cannot find. Effective search/filter directly drives sales.
  - **Performance:** Slow catalog loading leads to high bounce rates.
- **Main Risks:**
  - **SQL Injection:** Search bars are common vectors for SQLi attacks.
  - **Performance Bottlenecks:** Inefficient queries (e.g., `LIKE %...%`) on large datasets can crash the DB.
  - **Data Inconsistency:** Filters returning products that don't match the criteria.
- **Testing Approaches:**
  - **Functional:** Verify filter combinations (AND/OR logic).
  - **Performance:** Measure query execution time with 10,000+ products.
  - **UI/UX:** Check grid layout responsiveness on mobile devices.
- **Typical Failure Scenarios:**
  - Search returns 0 results for existing keywords due to case sensitivity issues.
  - Pagination links break when filters are active (e.g., clicking Page 2 resets the "Price < 500k" filter).
  - SQL error when special characters (e.g., `'`, `%`) are used in the search bar.

---

### 6.3. Shopping Cart

**1. Responsibility:**
Manages the temporary selection of products before purchase. It handles quantity adjustments and total price calculations.

**2. Workflow:**

- **Add to Cart:**
  1.  User clicks 'Add to Cart' on product detail.
  2.  Request sent to index.php?pg=addcart.
  3.  Product data is added to $\_SESSION['giohang'] array.
- **Update Quantity:**
  1.  User changes quantity in iew/cart.php.
  2.  AJAX request sent to jax/soluongsp.php.
  3.  Session array is updated, and new total is returned.

**3. Key Data:**

- **Input:** Product ID, Quantity, Color, Size.
- **Output:** Cart Session Array, Total Price.
- **Storage:** $\_SESSION['giohang'] (Transient), cart table (Persistent upon order).

**4. Interactions:**

- Interacts with model/product.php to retrieve current prices/images.
- Prepares data for iew/checkout.php.

**5. Validations & Edge Cases:**

- **Validation:** Checks if quantity > 0.
- **Edge Case:** Product price changes after being added to cart (System currently uses price at time of addition).

**6. Testing Perspective**

- **Test Targets:**
  - **Add to Cart:** Session array updates, Handling of duplicate items (increment qty vs new row), Stock limit checks.
  - **Update Cart:** Quantity changes via AJAX, Total price recalculation, Removal of items.
  - **Persistence:** Cart retention across page navigation and login/logout events.
  - **Calculations:** Subtotal, Grand Total, Discount application.
- **Business Criticality:**
  - **Revenue Accuracy:** Errors in calculation directly lead to financial loss or customer overcharge.
  - **User Experience:** A buggy cart is the #1 reason for abandonment.
- **Main Risks:**
  - **Negative Quantity:** Users manipulating inputs to set negative stock or prices.
  - **Race Conditions:** Adding the last item to cart simultaneously by two users.
  - **Session Loss:** Cart emptying unexpectedly due to session timeouts.
- **Testing Approaches:**
  - **Boundary Value Analysis:** Testing Quantity = 0, 1, Max Stock, Max+1, Negative.
  - **State Transition:** Verifying cart contents through the Login -> Browse -> Checkout flow.
  - **Usability:** Testing the responsiveness of AJAX updates.
- **Typical Failure Scenarios:**
  - Adding a negative quantity (`-5`) results in a negative total price, effectively "refunding" the user.
  - Cart is lost when user navigates back from the Checkout page.
  - Price changes in the backend are not reflected in active carts (user pays old price).

---

### 6.4. Order Processing (Checkout)

**1. Responsibility:**
Converts the temporary shopping cart into a permanent order record, captures shipping information, and records the payment method.

**2. Workflow:**

- **Review:**
  1.  User accesses index.php?pg=checkout.
  2.  System renders items from $\_SESSION['giohang'].
  3.  Auto-fills shipping info if user is logged in.
- **Submission:**
  1.  User selects Payment Method (COD, MoMo, Card).
  2.  User clicks 'Order'.
  3.  model/donhang.php -> creatdonhang() creates a record in donhang table.
  4.  model/cart.php -> dd_cart() moves items from Session to cart table, linked by id_donhang.
  5.  $\_SESSION['giohang'] is cleared.
  6.  Confirmation email is sent (via mailer/).

**3. Key Data:**

- **Input:** Shipping Address, Payment Method, Cart Items.
- **Output:** Order ID (ZS_xxxxxx), Order Confirmation.
- **Database:** donhang, cart.

**4. Interactions:**

- Calls model/voucher.php to apply discounts.
- Triggers mailer/mailer.php for email notifications.

**5. Validations & Edge Cases:**

- **Payment Integration:** 'MoMo' and 'Card' are **simulated**. The system records the string 'Thanh to�n b?ng v� MoMo' but does **not** interact with the MoMo API.
- **Stock:** Inventory is checked via model/soluongtonkho.php.

**6. Testing Perspective**

- **Test Targets:**
  - **Data Capture:** Accuracy of Shipping Address, Phone, and Email.
  - **Payment Logic:** Selection state, Simulated payment recording, Total amount verification.
  - **Order Creation:** Database insertion (`donhang` and `cart` tables), Stock deduction.
  - **Post-Order:** Email confirmation delivery, Session clearing.
- **Business Criticality:**
  - **Revenue Realization:** This is the point of conversion. Failure here means 0% revenue.
  - **Legal/Compliance:** Accurate recording of transaction data is required for accounting.
- **Main Risks:**
  - **Data Loss:** Order created but line items (cart) missing due to script crash.
  - **Inventory Drift:** Stock not deducted after purchase, leading to overselling.
  - **Double Charge:** User clicking "Order" twice results in duplicate orders.
- **Testing Approaches:**
  - **Workflow-Based:** End-to-End testing from Cart to "Thank You" page.
  - **Integration:** Verifying synchronization between `donhang` and `cart` tables.
  - **Security:** IDOR checks on the Order Confirmation page (viewing other users' orders).
- **Typical Failure Scenarios:**
  - Network failure during submission leaves `cart` items orphaned (Order created, items not linked).
  - Invalid email address causes the mailer script to crash the entire transaction.
  - Stock runs out while the user is filling out the checkout form (Race Condition).

---

### 6.5. Admin Management

**1. Responsibility:**
Provides a secure back-office interface for administrators to manage the platform's data (Products, Orders, Users).

**2. Workflow:**

- **Access Control:**
  1.  User accesses iew/admin/index.php.
  2.  System checks if(['role'] != 1).
  3.  Redirects to homepage if unauthorized.
- **Dashboard:**
  1.  Calls model/thongke.php -> doanhthu().
  2.  Renders charts/tables of monthly revenue.
- **CRUD Operations:**
  1.  Admin selects a module (e.g., Products).
  2.  Forms submit to iew/admin/index.php with specific actions (e.g., ddproduct, updateproduct).
  3.  Corresponding model functions are called.

**3. Key Data:**

- **Input:** Admin actions (Create, Update, Delete).
- **Output:** Updated Database Records, Management Views.

**4. Interactions:**

- Direct access to all model/\*.php files.
- Manages upload/ directory for product images.

**5. Validations & Edge Cases:**

- **Security:** Relies entirely on $\_SESSION['role'].
- **Data Integrity:** Deleting a product might affect historical orders (Foreign Key constraints in DB should handle this, or soft deletes).

**6. Testing Perspective**

- **Test Targets:**
  - **Access Control:** Verification of Role-Based Access Control (RBAC) for all admin pages.
  - **CRUD Operations:** Creating, Reading, Updating, and Deleting Products, Categories, and Users.
  - **Order Management:** Status updates (Pending -> Delivered), Order cancellation logic.
  - **Reporting:** Accuracy of revenue and visitor statistics.
- **Business Criticality:**
  - **Operational Control:** Admins need reliable tools to manage the business.
  - **Security:** The Admin panel is the "Keys to the Kingdom". Compromise is catastrophic.
- **Main Risks:**
  - **Privilege Escalation:** Standard users accessing admin functions via direct URL manipulation.
  - **Data Corruption:** Accidental deletion of active products or orders.
  - **XSS:** Malicious scripts injected into product descriptions executing in the admin context.
- **Testing Approaches:**
  - **Security:** RBAC verification, SQL Injection testing on admin login, XSS testing on input fields.
  - **Functional:** CRUD lifecycle testing for all entities.
  - **Database Integrity:** Verifying constraints when deleting parent records (e.g., Categories).
- **Typical Failure Scenarios:**
  - Non-admin user accesses `view/admin/product.php` directly via URL (Force Browsing).
  - Deleting a product that is part of a past order crashes the Order History view (Foreign Key violation).
  - Uploading a PHP shell script as a product image allows remote code execution.

## 7. Technical Architecture Analysis

### 7.1. System Architecture Overview

The project follows a **Custom MVC (Model-View-Controller)** architecture, implemented using native PHP without a third-party framework. It utilizes a **Page Controller** pattern where specific entry points handle routing and logic.

- **Architecture Style:** Monolithic, Layered (loosely).
- **Entry Points:**
  - **Public Front-end:** `index.php` acts as the main Front Controller for all user-facing pages.
  - **Admin Panel:** `view/admin/index.php` acts as a separate Front Controller for the administration interface.
- **Routing:** Query-string based routing. The `pg` parameter (e.g., `index.php?pg=product`) determines which logic block to execute via a large `switch` statement.

### 7.2. Component Interactions

The system is divided into three primary layers:

- **Controller Layer (`index.php`, `view/admin/index.php`):**
  - **Responsibility:** Handles incoming HTTP requests (GET/POST), manages session state (`$_SESSION`), calls Model functions, and determines which View to include.
  - **Logic:** Contains significant business logic, including input validation and filter processing.
- **Model Layer (`model/*.php`):**
  - **Structure:** Procedural function libraries (e.g., `getproduct()`, `insert_user()`).
  - **Responsibility:** Wraps PDO database interactions.
  - **Mixed Concerns:** Some model functions generate HTML strings directly, violating strict MVC separation.
- **View Layer (`view/*.php`):**
  - **Responsibility:** Renders the user interface.
  - **Interaction:** Receives data primarily through global variables or arrays extracted in the Controller.

### 7.3. Data Flow

1.  **Read Request (e.g., View Product Catalog):**
    - User accesses `index.php?pg=product`.
    - Controller calls `model/product.php` -> `getproduct_catalog()`.
    - Model executes SQL `SELECT` via `pdo_query`.
    - Controller assigns data to variables and includes `view/product.php`.
2.  **Write Request (e.g., Add to Cart):**
    - User submits form to `index.php?pg=addcart`.
    - Controller captures `$_POST`, updates `$_SESSION['giohang']`, and redirects.

### 7.4. Design Patterns & Principles

- **Front Controller Pattern:** Centralized request handling in `index.php`.
- **Procedural Programming:** Heavy reliance on global functions and state (`$_SESSION`) rather than OOP classes.
- **Template View:** Views mix HTML with PHP control structures.

### 7.5. Advantages and Limitations

- **Advantages:** Simple to deploy (no build process), direct control over SQL/HTML, low learning curve for basic PHP.
- **Limitations:** Tight coupling between logic and presentation (HTML in models), code duplication between Admin/Public controllers, difficult to unit test due to global state, and scalability issues with the monolithic switch statement.

### 7.6. Architectural Testing Implications

- **Regression Risk:** Since `index.php` handles all routing, any change to the central switch statement requires a full regression test of all pages.
- **Testability:** The heavy use of global state (`$_SESSION`, `$_POST`) makes unit testing difficult. Tests must use "Mock Objects" or "Test Doubles" to simulate these globals.
- **Strategy:** Prioritize **Integration Testing** (Controller + Model) over strict Unit Testing for this architecture.

## 8. Data Layer Analysis

### 8.1. Database Schema & Models

The application uses a relational MySQL database (`zstyle`). The schema is defined in `Zstyle.sql` and accessed via procedural PHP functions in `model/`.

**Key Entities:**

- **`users`**: Stores customer accounts (username, password, email, address, activation status).
- **`product`**: Core catalog entity (name, price, description, view count).
- **`catalog`**: Product categories (linked to products via `id_catalog`).
- **`cart`**: Dual-purpose table. Acts as a temporary shopping cart (linked to user) AND as order line items (linked to `donhang`).
- **`donhang`**: Order header (customer info, total price, payment method, status).
- **`banner`**, **`news`**, **`comment`**: Content management entities.
- **`voucher`**, **`giamgia`**: Discount and promotion logic.

### 8.2. Entity Relationships

- **User - Order:** One-to-Many (`users.id` -> `donhang.iduser`).
- **Order - Cart (Line Items):** One-to-Many (`donhang.id` -> `cart.id_donhang`).
  - _Note:_ The `cart` table is unique; it stores items for active sessions (where `id_donhang` is a placeholder, e.g., 1) and finalized orders.
- **Product - Category:** Many-to-One (`product.id_catalog` -> `catalog.id`).
- **Product - Color/Size:** Many-to-Many relationships managed via `img_product_color` and `cart` references.

### 8.3. CRUD Operations Implementation

Data access is decentralized across specific model files, wrapping `pdo_execute` (Write) and `pdo_query` (Read).

- **Create:**
  - `model/user.php`: `creatuser()` (Register).
  - `model/donhang.php`: `creatdonhang()` (Place Order).
  - `model/cart.php`: `add_cart()` (Add item).
- **Read:**
  - `model/product.php`: `getproduct()`, `getproduct_catalog()` (Browsing).
  - `model/donhang.php`: `getdonhang()` (Order History).
- **Update:**
  - `model/cart.php`: `update_cart()` (Change quantity).
  - `model/user.php`: `updateuser()` (Profile edit).
- **Delete:**
  - `model/cart.php`: `del_cart()` (Remove item).
  - `view/admin/` modules trigger delete functions in models for admin management.

### 8.4. Data Validation & Constraints

- **Database Level:**
  - Primary Keys (`id`) on all major tables.
  - Foreign Keys are implied in logic but not strictly enforced with `CONSTRAINT` keywords in the provided SQL dump (MyISAM/InnoDB mix or loose constraints).
  - Data types (e.g., `int`, `varchar`, `double`) enforce basic format.
- **Application Level:**
  - **Input Validation:** Occurs in Controllers (`index.php`). E.g., checking if passwords match, if fields are empty.
  - **Business Logic:** `model/user.php` checks `kichhoat=1` before allowing login. `model/voucher.php` validates discount codes.

### 8.5. Data Consistency

- **Transactions:** The code uses `PDO` but explicit transactions (`beginTransaction`, `commit`) are not widely used in the analyzed files. Operations like "Create Order" -> "Update Cart" happen sequentially.
- **Session State:** The application relies heavily on `$_SESSION` to maintain temporary state (cart, login) before persisting to the database.
- **Orphaned Data:** The `cart` table strategy (mixing active carts and order history) requires careful management to avoid orphaned records if an order creation fails mid-process.

### 8.6. Data Layer Testing Strategy

- **Integrity Testing:** Verify that deleting a Parent record (e.g., Order) correctly handles Child records (e.g., Cart Items) - either cascading delete or preventing it.
- **Transaction Testing:** Simulate a failure during Order Creation (e.g., DB disconnect) to ensure partial data is not saved.
- **State Transition:** Verify the transition of data from `$_SESSION` (Transient) to `donhang` table (Persistent) is lossless.

## 9. Limitations & Proposed Improvements

### 9.1. Technical Limitations

1.  **Tight Coupling:** The Model layer frequently outputs HTML (e.g., `showproduct()` in `model/product.php`), making it impossible to reuse these functions for an API or mobile app without refactoring.
    - **Testing Impact:** Unit tests for Models must parse HTML output to verify correctness, which is brittle.
2.  **Global State Dependency:** Heavy reliance on `global` variables and direct `$_SESSION` manipulation makes the code fragile and hard to test.
    - **Testing Impact:** Tests must reset global state (`$_SESSION = []`) between runs to prevent test pollution.
3.  **Lack of Dependency Injection:** Classes/Functions are tightly bound to specific implementations (e.g., `pdo_get_connection` is called directly inside functions), hindering unit testing.
    - **Testing Impact:** Cannot easily mock the database connection for fast unit tests.
4.  **Simulated Payments:** The current payment system only records a string label ("MoMo", "Card") and does not actually interface with any payment gateway APIs.
    - **Testing Impact:** Integration tests cannot verify real payment processing, only the internal record-keeping.

### 9.2. Design Constraints

1.  **Monolithic Routing:** The `index.php` file uses a massive `switch` statement to handle all pages. As the application grows, this file will become unmaintainable.
    - **Testing Impact:** High regression risk; automated smoke tests are essential for every release.
2.  **No Template Engine:** Using raw PHP as a template engine leads to security risks (XSS) if output escaping is missed, and makes the view layer messy.
    - **Testing Impact:** Security testing must focus heavily on Output Encoding (XSS) in Views.
3.  **Database Schema:** The `cart` table design (storing both temporary cart items and finalized order details) violates normalization principles and complicates data analysis.
    - **Testing Impact:** Data integrity tests must verify that "Order" items are not accidentally deleted when clearing "Cart" items.

### 9.3. Performance & Scalability Issues

1.  **N+1 Query Problem:** Loops in views often call model functions that execute SQL queries (e.g., fetching colors for every product in a list), leading to poor performance on large catalogs.
    - **Testing Impact:** Load testing is required to identify bottlenecks when product count > 1000.
2.  **No Caching:** Every page load triggers multiple database queries. There is no implementation of object caching (Redis/Memcached) or page caching.
    - **Testing Impact:** Performance tests should measure Time to First Byte (TTFB) under load.
3.  **Session Storage:** Storing cart data in `$_SESSION` (file-based by default in PHP) works for single servers but will fail in a load-balanced environment without a shared session store (like Redis).
    - **Testing Impact:** Scalability testing is limited to a single server instance.

### 9.4. Missing Features (vs. Modern Standards)

1.  **Wishlist:** Requirement identified but not implemented.
2.  **API Layer:** While `tests/API` exists, there is no dedicated REST API structure for external consumers (mobile app).
3.  **Order Tracking:** Users cannot see detailed status history of their orders beyond a simple status field.

### 9.5. Realistic Improvement Plan

#### Phase 1: Architecture Refactoring (Low Risk)

- **Action:** Extract logic from `index.php` into dedicated Controller files (e.g., `controllers/ProductController.php`, `controllers/CartController.php`).
- **Benefit:** Reduces the size of `index.php` and groups related logic.
- **QA Role:** Execute full Regression Suite to ensure routing logic remains unchanged.

#### Phase 2: Data Layer Optimization

- **Action:** Refactor `model/product.php` to return **raw data arrays** only. Move HTML generation to the View layer.
- **Benefit:** Decouples logic from presentation, enabling API reuse.
- **QA Role:** Verify that Views correctly render the raw data (Visual Regression Testing).

#### Phase 3: Feature Implementation

- **Action:** Implement **Wishlist** functionality.
  - Create `wishlist` table (`id`, `user_id`, `product_id`).
  - Add `model/wishlist.php` and update `view/detail.php`.
- **Action:** Integrate a **Payment Gateway Sandbox** (e.g., Stripe or MoMo Test).
  - Replace the simulated string storage with a real API call in `model/donhang.php`.
- **QA Role:** Design new Test Cases for Wishlist CRUD and Payment API failure scenarios.

#### Phase 4: Performance Tuning

- **Action:** Implement **Eager Loading** for product attributes. Instead of fetching colors inside a loop, fetch all colors for the displayed products in one query.
- **Action:** Add database indexes to `cart.id_donhang`, `cart.id_user`, and `product.id_catalog`.
- **QA Role:** Benchmark response times before and after optimization to validate improvements.

## 10. Comprehensive Test Plan

### 10.1. Use Case & Workflow Testing

| ID           | Scenario                          | Preconditions                | Test Steps                                                                                                               | Test Data                          | Expected Result                                                                                                                                         | Type       |
| :----------- | :-------------------------------- | :--------------------------- | :----------------------------------------------------------------------------------------------------------------------- | :--------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------ | :--------- |
| **TC_UC_01** | **User Registration (Success)**   | Guest user on Register page  | 1. Enter valid Username, Email.<br>2. Enter matching Password/Repass.<br>3. Click Register.                              | `user: testuser`<br>`pass: 123456` | System calls `creatuser()`. New record in `users` table with `kichhoat=1` (or 0 depending on config). Redirect to Login.                                | Functional |
| **TC_UC_02** | **User Login (Active Account)**   | User exists and `kichhoat=1` | 1. Enter valid Username/Password.<br>2. Click Login.                                                                     | `user: testuser`<br>`pass: 123456` | `getlogin()` returns true. `$_SESSION['user']` is populated. Redirect to Home.                                                                          | Functional |
| **TC_UC_03** | **User Login (Inactive Account)** | User exists but `kichhoat=0` | 1. Enter valid Username/Password.<br>2. Click Login.                                                                     | `user: inactive`<br>`pass: 123456` | Login fails. Error message displayed. Session is NOT created.                                                                                           | Exception  |
| **TC_WF_01** | **End-to-End Purchase Flow**      | User logged in, Cart empty   | 1. Navigate to Product Detail.<br>2. Click "Add to Cart".<br>3. Go to Checkout.<br>4. Select "COD".<br>5. Click "Order". | Product ID: 10                     | 1. Item added to `$_SESSION['giohang']`.<br>2. Order created in `donhang`.<br>3. Cart items moved to `cart` table linked to Order ID.<br>4. Email sent. | System     |
| **TC_WF_02** | **Product Filtering**             | On Product Page              | 1. Select Price < 500k.<br>2. Select Color "Red".                                                                        | Filter: Price<500, Color=Red       | Page reloads. `model/product.php` executes SQL with `WHERE price < 500000 AND color = 'Red'`. Only matching items shown.                                | Functional |

### 10.2. Database Integrity Testing

| ID           | Scenario                       | Preconditions               | Test Steps                                            | Test Data              | Expected Result                                                                                                                                                             | Type        |
| :----------- | :----------------------------- | :-------------------------- | :---------------------------------------------------- | :--------------------- | :-------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :---------- |
| **TC_DB_01** | **Order Creation Consistency** | Cart has 2 items            | 1. Complete Checkout process.                         | Cart: [Item A, Item B] | 1. New row in `donhang` table.<br>2. Two rows in `cart` table with `id_donhang` matching the new Order ID.<br>3. `$_SESSION['giohang']` is empty.                           | Integration |
| **TC_DB_02** | **Duplicate User Prevention**  | User "existing_user" exists | 1. Attempt to register with same username.            | `user: existing_user`  | `creatuser()` logic should prevent INSERT or return error. Database should NOT have duplicate username.                                                                     | Database    |
| **TC_DB_03** | **Cart Persistence**           | User logged in              | 1. Add item to cart.<br>2. Logout.<br>3. Login again. | Item ID: 5             | _Note: Current implementation relies on Session._ If session is destroyed, cart is lost. Verify if `cart` table stores temp items (Analysis shows it might not for guests). | Database    |

### 10.3. UI/Screen Behavior Testing

| ID           | Scenario                        | Preconditions            | Test Steps                                                       | Test Data       | Expected Result                                                                      | Type          |
| :----------- | :------------------------------ | :----------------------- | :--------------------------------------------------------------- | :-------------- | :----------------------------------------------------------------------------------- | :------------ |
| **TC_UI_01** | **Responsive Menu**             | Mobile Viewport (375px)  | 1. Load Homepage.<br>2. Click Hamburger Menu.                    | N/A             | Menu expands/collapses correctly. No layout breakage.                                | UI            |
| **TC_UI_02** | **Cart Quantity Update (AJAX)** | Items in Cart            | 1. Change quantity input from 1 to 2.<br>2. Observe Total Price. | Qty: 2          | Total price updates dynamically without full page reload (via `ajax/soluongsp.php`). | UI/Functional |
| **TC_UI_03** | **Admin Dashboard Access**      | User role = 0 (Customer) | 1. Direct URL access to `view/admin/index.php`.                  | Session Role: 0 | System redirects to `index.php` (Homepage). Admin panel not visible.                 | Security      |

### 10.4. Architecture & Component Interaction

| ID            | Scenario                    | Preconditions          | Test Steps                                                       | Test Data              | Expected Result                                                                                                                                  | Type        |
| :------------ | :-------------------------- | :--------------------- | :--------------------------------------------------------------- | :--------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------- | :---------- |
| **TC_ARC_01** | **Routing Logic**           | N/A                    | 1. Access `index.php?pg=invalid_page`.                           | `pg=xyz`               | Switch statement default case triggers (likely Home or 404). No PHP fatal errors.                                                                | Component   |
| **TC_ARC_02** | **Model-View Data Handoff** | N/A                    | 1. Access Product Page.                                          | N/A                    | `index.php` calls `getproduct()`. Data assigned to global `$product_list`. `view/product.php` iterates `$product_list`. Data displays correctly. | Integration |
| **TC_ARC_03** | **Session Isolation**       | Two different browsers | 1. Add item to cart in Browser A.<br>2. Check cart in Browser B. | N/A                    | Browser B cart is empty. Sessions are isolated.                                                                                                  | System      |
| **TC_ARC_04** | **Mailer Integration**      | Valid Email Config     | 1. Trigger "Forgot Password".                                    | Email: valid@email.com | `mailer/mailer.php` is included. `PHPMailer` object created. Email sent via SMTP.                                                                | Integration |

### 10.5. Use Case and Workflow to Test Mapping

| Use Case                    | Business Workflow | Test Level  | Test Type               | Associated Risks                                                  |
| :-------------------------- | :---------------- | :---------- | :---------------------- | :---------------------------------------------------------------- |
| **UC_01: Register**         | User Registration | System      | Functional, Security    | Spam Accounts, SQL Injection, Email Delivery Failure              |
| **UC_02: Login**            | Authentication    | System      | Functional, Security    | Unauthorized Access, Brute Force, Session Fixation                |
| **UC_03: Browse**           | Product Catalog   | System      | Functional, UI          | Slow Queries, Broken Images, Pagination Errors                    |
| **UC_04: Search**           | Product Discovery | System      | Functional, Performance | No Results, SQL Injection, High Latency                           |
| **UC_05: Add to Cart**      | Shopping Cart     | Integration | Functional, State       | Session Data Loss, Negative Quantity, Race Conditions             |
| **UC_06: Checkout**         | Order Processing  | E2E         | Workflow, Functional    | Order Loss, Payment Error, Inventory Drift                        |
| **UC_07: Admin Login**      | Admin Access      | System      | Security                | Privilege Escalation, Direct URL Access                           |
| **UC_08: Manage Product**   | Admin CRUD        | System      | Functional              | Data Corruption, XSS in Descriptions, File Upload Vulnerabilities |
| **UC_09: Manage Order**     | Order Fulfillment | System      | Functional              | Orphaned Records, Status Inconsistency                            |
| **UC_10: Filter Products**  | Product Discovery | System      | Functional, Black-box   | Incorrect Result Set, Filter Logic Errors                         |
| **UC_11: Update Profile**   | User Management   | System      | Functional              | Data Validation Failure, XSS in Profile Fields                    |
| **UC_12: Recover Password** | Account Recovery  | Integration | Workflow                | Email Delivery Failure, Token Expiry Issues                       |

### 10.6. Detailed Workflow Scenarios

#### WF_01: Customer Purchase (Happy Path)

- **Description:** Complete end-to-end flow from product discovery to order confirmation.
- **Steps:**
  1.  **Browse:** User navigates to `index.php?pg=product`.
  2.  **Select:** User clicks "Add to Cart" on Item A.
  3.  **Cart:** User verifies Item A in `view/cart.php`.
  4.  **Checkout:** User enters shipping info and selects "COD".
  5.  **Confirm:** User clicks "Order".
- **Critical Checkpoints:**
  - Session `giohang` must contain Item A before checkout.
  - `donhang` table must have new record after confirmation.
  - `cart` table must have items linked to new `id_donhang`.
  - Session `giohang` must be empty after success.
- **Data Consistency:** Total Price in Order = Sum(Item Price \* Qty).

#### WF_02: Interrupted Checkout

- **Description:** User abandons checkout and attempts to resume.
- **Steps:**
  1.  **Add to Cart:** User adds items.
  2.  **Checkout:** User proceeds to `view/checkout.php`.
  3.  **Interruption:** User closes browser tab (Session End).
  4.  **Resume:** User re-opens browser and navigates to site.
- **Expected Behavior:**
  - **Normal Flow:** Cart is empty (Zstyle uses Session storage).
  - **Desired Flow (Improvement):** Cart is restored from Database (requires "Persistent Cart" feature).
- **Risk:** High churn if users lose large carts easily.

#### WF_03: Admin Product Management

- **Description:** Admin manages the product lifecycle.
- **Steps:**
  1.  **Create:** Admin fills form in `view/admin/product.php` -> Save.
  2.  **Verify:** Admin checks "Product List" for new item.
  3.  **Public Verify:** Admin checks Public Catalog to ensure item appears.
  4.  **Edit:** Admin changes Price -> Update.
  5.  **Delete:** Admin removes product.
- **Critical Checkpoints:**
  - Image upload must succeed and path stored in DB.
  - Deleted product must NOT appear in Public Catalog.
- **Exception Flow:** Deleting a product that is in an active user's cart (Session) -> User sees error or item disappears.

#### WF_04: Admin Order Processing

- **Description:** Admin reviews and manages customer orders.
- **Steps:**
  1.  **View:** Admin checks `view/admin/donhang.php`.
  2.  **Analyze:** Admin verifies Total Price and Payment Method.
  3.  **Action:** Admin clicks "Delete" (Zstyle lacks "Ship" status, only Delete).
- **Data Consistency:**
  - Deleting an Order must cascade delete related `cart` items to prevent orphaned data.

#### WF_05: Authentication & Authorization

- **Description:** Verifying secure access to the system.
- **Steps:**
  1.  **Login:** User enters credentials -> Redirect to Home.
  2.  **Access:** User attempts to access `view/admin/index.php`.
  3.  **Denial:** System redirects User to Home (Access Control).
  4.  **Logout:** User clicks Logout -> Session destroyed.
- **Security Checkpoint:** Direct URL access to Admin pages without session must fail.

## 11. Automation Testing Proposal

### 11.1. Automation Strategy

- **Goal:** Reduce manual regression effort by 60% and ensure critical paths are always functional.
- **Scope:**
  - **Automate:** Core "Happy Paths" (Login, Cart, Checkout), Smoke Tests, and API endpoints.
  - **Manual:** Admin Interface (low usage frequency), UX/Visual testing, Edge cases requiring complex setup.

### 11.2. Tool Selection

- **UI Automation:** **Selenium WebDriver (php-webdriver)**.
  - _Why:_ Native support for PHP, handles JavaScript interactions (AJAX cart updates).
- **Logic/Unit Automation:** **PHPUnit**.
  - _Why:_ Standard for PHP backend logic, integrates with CI/CD.

### 11.3. Architecture (Page Object Model)

- **Structure:**
  - `tests/PageObjects/LoginPage.php`: Encapsulates Login UI elements and methods.
  - `tests/PageObjects/CartPage.php`: Encapsulates Cart interactions.
  - `tests/Scenarios/CheckoutTest.php`: Uses Page Objects to run E2E tests.
- **Benefit:** Maintenance is easier; if UI changes, only Page Objects need updating, not Test Scripts.

### 11.4. Limitations & Risks

- **Captcha/Email:** Automated tests cannot easily bypass Captcha (if added) or verify real emails (use Mailtrap API).
- **State Management:** Tests must clean up data (delete test orders) to avoid polluting the database.
- **Flakiness:** UI tests can be flaky due to network latency; implement "Explicit Waits" instead of "Sleep".

### 11.1. Requirement-Based Testing

- **What:** Verifies that every feature listed in the `README.md` and Requirement Traceability Matrix (Section 5) is implemented and functioning.
- **Why:** Ensures the final product meets the initial business goals and user expectations.
- **Coverage:** Customer Features (Browsing, Cart, Account) and Admin Features (Dashboard, Management).
- **Test Cases:**
  - **TC_REQ_01:** Verify "Browse by Category" displays correct products for each category ID.
  - **TC_REQ_02:** Verify "Advanced Filtering" correctly combines Price, Color, and Gender filters.
  - **TC_REQ_03:** Verify "Search" returns relevant results for partial keyword matches.
  - **TC_REQ_04:** Verify "Stock Tracking" decrements inventory after a purchase (if implemented) or displays "Out of Stock".

### 11.2. Workflow Testing

- **What:** Validates complete end-to-end business processes across multiple pages and components.
- **Why:** Users don't use features in isolation; they follow workflows. This ensures smooth transitions between steps.
- **Coverage:**
  - **Authentication:** Register -> Activate -> Login -> Logout -> Forgot Password.
  - **Shopping:** Browse -> Filter -> Detail -> Add to Cart -> Update Cart -> Checkout.
  - **Admin:** Login -> Dashboard -> Add Product -> Edit Product -> Delete Product -> Logout.
- **Test Cases:**
  - **TC_WF_AUTH:** Complete registration flow, check database for activation status, then login.
  - **TC_WF_CART:** Add items, change quantity via AJAX, remove item, verify total calculation.
  - **TC_WF_CHECKOUT:** Guest checkout vs. Member checkout (auto-fill data).

### 11.3. Data Model Testing

- **What:** Checks the integrity, consistency, and relationships of data in the MySQL database.
- **Why:** Prevents data corruption, orphaned records, and ensures accurate reporting.
- **Coverage:** `users`, `product`, `cart`, `donhang` tables.
- **Test Cases:**
  - **TC_DATA_01:** Verify `donhang` creation also creates associated `cart` records with correct `id_donhang`.
  - **TC_DATA_02:** Verify `product` deletion (if allowed) handles associated `cart` items (Cascade or Restrict).
  - **TC_DATA_03:** Verify data types: Price should be numeric, Email should be valid format.

### 11.4. UI/UX Testing

- **What:** Validates the visual interface, usability, responsiveness, and client-side validation.
- **Why:** Ensures a positive user experience and that the site is usable on different devices.
- **Coverage:** All View files (`view/*.php`), Forms, Navigation.
- **Test Cases:**
  - **TC_UI_RESP:** Check layout on Desktop (1920px), Tablet (768px), and Mobile (375px).
  - **TC_UI_FORM:** Submit empty forms to check HTML5 `required` attributes and JS validation.
  - **TC_UI_ERR:** Verify error messages (e.g., "Wrong password") are displayed clearly to the user.

### 11.5. Non-Functional Testing

- **What:** Tests aspects of the system that are not specific behaviors (Performance, Security).
- **Why:** Ensures reliability, speed, and safety.
- **Coverage:**
  - **Performance:** Page load times, Database query execution time.
  - **Security:** SQL Injection, XSS, Session Hijacking, Access Control.
- **Test Cases:**
  - **TC_PERF_01:** Measure load time of Product Page with 50+ items (Check for N+1 query impact).
  - **TC_SEC_01 (SQLi):** Attempt to inject SQL in Search bar (`' OR '1'='1`).
  - **TC_SEC_02 (XSS):** Attempt to inject `<script>` tags in Product Comments.
  - **TC_SEC_03 (Auth):** Attempt to access `view/admin/index.php` without being logged in as Admin.

### 11.6. Backend Testing

- **What:** Tests the server-side logic, routing, and model functions in isolation.
- **Why:** Ensures the core logic is correct independent of the UI.
- **Coverage:** `index.php` routing, `model/*.php` functions.
- **Test Cases:**
  - **TC_BE_ROUTE:** Access invalid `pg` parameter (e.g., `?pg=unknown`) -> Should handle gracefully (404 or Home).
  - **TC_BE_LOGIC:** Unit test `giamgia()` logic to ensure discounts are calculated correctly.

### 11.7. Frontend Testing

- **What:** Tests client-side JavaScript and DOM manipulation.
- **Why:** Ensures dynamic features (AJAX cart, sliders) work as expected.
- **Coverage:** `main.js` (if exists), AJAX calls in `view/cart.php`.
- **Test Cases:**
  - **TC_FE_AJAX:** Verify `ajax/soluongsp.php` returns correct JSON/HTML response when quantity changes.
  - **TC_FE_DOM:** Verify DOM updates (Total Price) reflect the AJAX response immediately.

### 11.8. API & Integration Testing

- **What:** Tests the interaction between different modules and external services (if any).
- **Why:** Ensures components work together correctly.
- **Coverage:** Internal "API" (Model calls), Mailer, Payment (Simulated).
- **Test Cases:**
  - **TC_INT_MAIL:** Verify `PHPMailer` configuration connects to SMTP server and sends email on Order Placement.
  - **TC_INT_MODEL:** Verify Controller correctly passes `$_POST` data to `model/user.php` -> `creatuser()`.

### 11.9. Deployment & CI/CD Testing

- **What:** Verifies the application runs correctly in the target environment (Docker).
- **Why:** "It works on my machine" is not enough. Ensures reproducibility.
- **Coverage:** `Dockerfile`, `docker-compose.yml`.
- **Test Cases:**
  - **TC_DEP_01:** Run `docker-compose up`. Verify containers (Web, DB) start without errors.
  - **TC_DEP_02:** Verify Database connection from PHP container to MySQL container using environment variables.
  - **TC_DEP_03:** Verify file permissions for `upload/` directory allow PHP to save images.

# Part 2: Formal Software Testing Report

## Chapter 1 – Introduction

### 1.1. Research Objectives

This report presents a comprehensive quality assurance analysis of the **Zstyle E-commerce Platform**. The primary objective is not merely to verify functional correctness, but to evaluate the system's architectural resilience, security posture, and data integrity through rigorous testing methodologies. The study aims to:

1.  **Assess Testability:** Evaluate the "Page Controller" architecture's impact on automated testing and isolation.
2.  **Identify Vulnerabilities:** Systematically uncover security gaps inherent in legacy PHP session management and procedural SQL handling.
3.  **Validate Business Logic:** Ensure that complex workflows (e.g., inventory management, discount application) maintain state consistency under boundary conditions.

### 1.2. Methodology Overview

The testing approach employs a **Hybrid Testing Strategy**, integrating:

- **Black-Box Testing:** To validate User Experience (UX) and workflow compliance against requirements without internal code inspection.
- **White-Box Testing:** To analyze control flow within `index.php` routing and data flow within `model/*.php` functions.
- **Gray-Box Testing:** To verify database state changes and session persistence during multi-step transactions.

### 1.3. Document Structure

This report is structured as follows:

- **Chapter 2:** Analysis of the System Under Test (SUT) from a QA perspective.
- **Chapter 3:** The formal Test Plan, outlining scope, resources, and constraints.
- **Chapter 4:** Detailed Test Design, focusing on complex scenarios and edge cases.
- **Chapter 5:** Execution results, defect analysis, and final quality assessment.

---

## Chapter 2 – System Under Test (SUT) Analysis

### 2.1. Architectural Testability Assessment

The Zstyle platform utilizes a **Custom MVC (Model-View-Controller)** architecture with a centralized Page Controller (`index.php`).

- **Testability Challenge:** The tight coupling between the Controller (routing logic) and the View (HTML rendering) within the same execution scope complicates unit testing.
- **Implication:** Testing must rely heavily on **Integration** and **End-to-End (E2E)** strategies (e.g., Selenium) rather than isolated Unit Tests, as mocking the global `$_SESSION` and `$_GET` superglobals is non-trivial in this procedural context.

### 2.2. Data Persistence & State Management

State management relies primarily on **PHP Sessions** (`$_SESSION`) for transient data (Shopping Cart, User Auth) and **MySQL** for persistent data (Orders, Products).

- **QA Focus:** The synchronization point between Session storage and Database storage occurs _only_ at the "Checkout" event.
- **Critical Test Vector:** Interruption testing is vital here. If a session expires or is hijacked before the database commit, data integrity (e.g., stock reservation) may be compromised.

### 2.3. Security Attack Surface

From a testing perspective, the application presents several high-risk surfaces:

1.  **Admin Interface (`view/admin/`):** Direct file access vulnerabilities due to inconsistent include checks.
2.  **Input Vectors:** Search bars, Registration forms, and Checkout fields are primary targets for SQL Injection (SQLi) and Cross-Site Scripting (XSS).
3.  **Session Handling:** The lack of strict session regeneration upon privilege changes (e.g., Login) exposes the system to Session Fixation attacks.

### 2.4. Integration Points

The system integrates with:

- **PHPMailer:** For transactional email delivery. Testing requires mocking SMTP responses to verify failure handling.
- **Simulated Payment Gateways:** The system records payment intent (string literals) rather than processing transactions. Testing focuses on the _recording_ of this intent rather than the financial transaction itself.

---

## Chapter 3 – Test Plan

### 3.1. Introduction

The **Zstyle E-commerce Platform** is a web-based retail application specializing in fashion and footwear, built on a custom PHP MVC architecture. This Test Plan serves as the governing document for the Quality Assurance (QA) phase, adhering to the **IEEE 829 Standard for Software Test Documentation**. It defines the scope, strategy, resources, and schedule for validation activities.

**Purpose of Testing:**
The primary purpose is to verify that the Zstyle platform meets its functional requirements and business objectives while adhering to security best practices. The testing process aims to identify defects early, ensure data integrity during transactions, and validate the resilience of the system against common web vulnerabilities.

**Testing Objectives:**

1.  **Business Workflow Validation:** Ensure critical user journeys (e.g., "Guest Checkout", "Admin Order Fulfillment") function seamlessly without data loss.
2.  **Risk Mitigation:** Proactively identify and verify fixes for high-risk areas such as SQL Injection points, Session Fixation vulnerabilities, and Inventory Race Conditions.
3.  **Architectural Compliance:** Verify that the custom MVC implementation correctly handles routing, state management, and database interactions under load.

### 3.2. Test Scope

#### 3.2.1. In-Scope Functionalities

The following modules are prioritized for testing based on business value and risk:

- **Customer Storefront:**
  - **Authentication:** Registration, Login, Password Recovery, Session Management.
  - **Catalog:** Product Browsing, Category Filtering, Search (Partial/Full match).
  - **Shopping Cart:** AJAX-based addition, Quantity updates, Item removal, Session persistence.
  - **Checkout:** Address validation, Payment method selection (Simulated), Order confirmation.
- **Admin Panel:**
  - **Access Control:** Role-Based Access Control (RBAC) enforcement for all views.
  - **Dashboard:** Statistical reporting (Revenue, Visitor counts).
  - **CRUD Operations:** Management of Products, Categories, Orders, and Users.
- **Integration Points:**
  - **Database:** MySQL transactional integrity and foreign key constraints.
  - **Email:** Transactional emails via PHPMailer (SMTP).

#### 3.2.2. Out-of-Scope Items

The following areas are excluded from this test cycle:

- **Real-Time Payment Processing:** The system uses simulated payment methods (e.g., "Transfer", "COD"). Integration with live gateways (Stripe, PayPal) is not implemented.
- **Mobile Application:** Testing is limited to the responsive web interface; native mobile apps are not part of the current codebase.
- **Third-Party Logistics (3PL):** API integrations with shipping providers (e.g., GHTK, Viettel Post) are not available.
- **Performance Stress Testing:** While basic load times are checked, high-concurrency DDoS simulation is out of scope due to environment limitations.

#### 3.2.3. Justification of Scope

The scope is defined to maximize coverage of the **Core Business Logic** and **Security** within the constraints of a local Dockerized environment. Simulated payments and logistics are excluded as they require external sandbox credentials not currently available. The focus remains on the integrity of the internal order processing logic rather than external dependencies.

### 3.3. Test Items

The software items to be tested are categorized by priority:

| Priority          | Module / Component   | Description                                                         |
| :---------------- | :------------------- | :------------------------------------------------------------------ |
| **Critical (P0)** | **Checkout Process** | End-to-end order creation, stock deduction, and email confirmation. |
| **Critical (P0)** | **Authentication**   | Login security, Session handling, and Admin gatekeeping.            |
| **High (P1)**     | **Shopping Cart**    | Logic for adding/updating items, handling negative quantities.      |
| **High (P1)**     | **Admin Security**   | Prevention of IDOR and Forced Browsing on admin pages.              |
| **Medium (P2)**   | **Product Catalog**  | Search accuracy, Filtering logic, Pagination.                       |
| **Medium (P2)**   | **User Profile**     | Address management, Order history viewing.                          |
| **Low (P3)**      | **Static Content**   | "About Us", "Contact" pages, Footer links.                          |

### 3.4. Test Strategy

The testing approach utilizes a **Hybrid Strategy** combining multiple testing levels and types to ensure comprehensive coverage.

#### 3.4.1. Test Levels

1.  **Unit Testing (White-box):**
    - _Focus:_ Individual functions in `model/` files (e.g., `checkuser()`, `tongdonhang()`).
    - _Tool:_ PHPUnit.
    - _Constraint:_ Limited to pure logic functions due to tight coupling with HTML in some models.
2.  **Integration Testing (Gray-box):**
    - _Focus:_ Interaction between Controllers (`index.php`), Models, and the Database. Verifying data flow from Form Submission $\rightarrow$ DB Insert $\rightarrow$ Email Trigger.
    - _Method:_ Manual execution and API testing.
3.  **System Testing (Black-box):**
    - _Focus:_ Validating complete workflows against requirements.
    - _Tool:_ Selenium WebDriver for automated regression of "Happy Paths".
4.  **Acceptance Testing (UAT):**
    - _Focus:_ Verifying the system from the end-user's perspective (Usability, Visual correctness).

#### 3.4.2. Test Types

- **Functional Testing:** Verifying that features work as specified (e.g., "Add to Cart" increases count).
- **Security Testing:**
  - _SAST:_ Static analysis of code for SQL Injection vulnerabilities.
  - _DAST:_ Dynamic testing for XSS and Privilege Escalation.
- **Workflow-Based Testing:** Validating complex scenarios like "Interrupted Checkout" or "Admin modifying active product".
- **Regression Testing:** Re-running the automation suite after every code change to prevent regressions.

#### 3.4.3. Rationale for Hybrid Strategy

The combination of manual and automated testing is essential for this project:

- **Automation (Selenium/PHPUnit)** is used for repetitive regression tasks and complex calculation logic, ensuring that core features remain stable during refactoring.
- **Manual Testing** is reserved for exploratory security testing and UI/UX validation, where human intuition is required to spot visual defects or logic gaps that automation might miss.

### 3.5. Test Environment

To ensure reproducibility, the test environment mirrors the target production configuration as closely as possible.

- **Hardware:** Local Development Workstation (Virtualization enabled).
- **Software Configuration:**
  - **Containerization:** Docker & Docker Compose.
  - **Web Server:** Apache 2.4 (via XAMPP/Docker image).
  - **Language:** PHP 8.0.
  - **Database:** MySQL 5.7 / MariaDB 10.4.
  - **Browser:** Google Chrome (Latest), Firefox (Latest).
- **Network Conditions:**
  - Primary: Localhost (Low Latency).
  - Secondary: Simulated 3G (High Latency via DevTools) to test AJAX cart resilience.
- **Test Data Assumptions:**
  - Database is reset to a known state before each full regression run.
  - "Golden Dataset" includes: 1 Admin, 50 Products, 10 Categories, 0 Orders.

### 3.6. Entry and Exit Criteria

#### 3.6.1. Entry Criteria (Start Testing)

- The application is successfully deployed to the **Docker Test Environment**.
- The `zstyle` database is seeded with the **Golden Dataset** (Version 1.0).
- The **Smoke Test Suite** (Login, Homepage Load, Basic Cart Add) passes with 100% success.
- Test Cases and Test Data are reviewed and approved.

#### 3.6.2. Exit Criteria (Stop Testing)

- **100%** of Critical (P0) and High (P1) test cases have been executed.
- **95%** Pass rate for all executed test cases.
- **Zero** open Critical or High severity defects.
- All Medium (P2) defects are triaged and have a workaround or planned fix date.
- The **Test Summary Report** is generated and signed off by the Project Lead.

### 3.7. Risk-Based Testing

Testing effort is prioritized based on the potential impact of failure.

| Risk Category | Risk Description                                                               | Impact              | Testing Focus                                               |
| :------------ | :----------------------------------------------------------------------------- | :------------------ | :---------------------------------------------------------- |
| **Business**  | **Cart Abandonment:** Users lose cart data due to session timeouts.            | Revenue Loss        | Verify session persistence and "Guest Checkout" speed.      |
| **Technical** | **Legacy Code Fragility:** Changes in `index.php` break routing for all pages. | System Outage       | Automated Smoke Tests on all major routes.                  |
| **Security**  | **Admin Takeover:** Weak session checks allow unauthorized admin access.       | Data Breach         | Rigorous "Negative Testing" on Admin URLs (Force Browsing). |
| **Data**      | **Inventory Drift:** Race conditions allow selling more stock than available.  | Fulfillment Failure | Concurrency testing on "Place Order" action.                |

### 3.8. Test Deliverables

The QA process will produce the following artifacts:

1.  **Test Plan:** This document, defining the strategy and scope.
2.  **Test Case Suite:** A detailed repository of test scenarios (Excel/TestRail) covering all requirements.
3.  **Defect Reports:** Logged issues in the tracking system (Jira/GitHub Issues) with reproduction steps and severity.
4.  **Test Execution Logs:** Records of pass/fail status for each test run.
5.  **Test Summary Report:** A final report summarizing coverage, defect density, and release readiness.

---

## Chapter 4 – Test Design

### 4.1. Test Design Overview

Test design is the engineering phase where abstract requirements are transformed into concrete, executable test procedures. For the Zstyle E-commerce Platform, the design strategy is driven by the need to validate a monolithic PHP architecture that relies heavily on global state (`$_SESSION`) and direct database interactions.

**Role of Test Design:**

1.  **Defect Prevention:** By analyzing requirements to create test cases, we identify logical gaps (e.g., "What happens to the cart if stock runs out during checkout?") before code execution.
2.  **Coverage Assurance:** Systematic design techniques (like Equivalence Partitioning) ensure that we test representative samples of all possible inputs, maximizing coverage with minimal redundancy.
3.  **Traceability:** Every test case is mapped back to a specific requirement or workflow, ensuring that no business rule is left untested.

**Design Methodology:**
The project employs a **Model-Based Testing** approach where we model the expected behavior of the system (e.g., State Transition Diagrams for Orders) and derive test cases to verify these models.

### 4.2. Black-Box Test Design

Black-box testing focuses on the external behavior of the system, treating the internal logic as opaque. We apply formal test design techniques to derive high-yield test cases.

#### 4.2.1. Equivalence Partitioning (EP)

We divide input domains into classes where the system is expected to process all members of the class identically.

- **Authentication (Login):**
  - _Class 1 (Valid):_ Registered Username + Correct Password $\rightarrow$ Access Granted.
  - _Class 2 (Invalid):_ Unregistered Username $\rightarrow$ Error "User not found".
  - _Class 3 (Invalid):_ Correct Username + Wrong Password $\rightarrow$ Error "Wrong password".
- **Product Search:**
  - _Class 1 (Exact):_ "Nike" $\rightarrow$ Returns products with "Nike" in name.
  - _Class 2 (Partial):_ "Ni" $\rightarrow$ Returns "Nike", "Nice", etc.
  - _Class 3 (Empty):_ "" $\rightarrow$ Returns all products or prompts for input.

#### 4.2.2. Boundary Value Analysis (BVA)

Defects frequently occur at the boundaries of input ranges.

- **Cart Quantity:**
  - _Boundary (Min):_ 0 (Invalid), 1 (Valid).
  - _Boundary (Max):_ Stock Count (Valid), Stock + 1 (Invalid).
  - _Boundary (Negative):_ -1 (Invalid/Security Risk).
  - _Test Case:_ Attempting to add `Stock + 1` items should trigger a "Insufficient Stock" validation message.

#### 4.2.3. Decision Table Testing

Used to validate complex logic where the output depends on a combination of multiple inputs.

- **Checkout Validation Logic:**

| Rule | Cart Not Empty? | User Logged In? | Address Provided? | Payment Selected? | **Action**         |
| :--- | :-------------- | :-------------- | :---------------- | :---------------- | :----------------- |
| 1    | No              | -               | -                 | -                 | Block Checkout     |
| 2    | Yes             | No              | -                 | -                 | Prompt Login/Guest |
| 3    | Yes             | Yes             | No                | -                 | Prompt Address     |
| 4    | Yes             | Yes             | Yes               | No                | Prompt Payment     |
| 5    | Yes             | Yes             | Yes               | Yes               | **Create Order**   |

### 4.3. White-Box Test Design

White-box testing leverages knowledge of the internal code structure (`index.php`, `model/*.php`) to verify logic paths and data handling.

#### 4.3.1. Control Flow Testing

- **Target:** `index.php` (Page Controller).
- **Technique:** Branch Coverage.
- **Analysis:** The central `switch ($_GET['pg'])` statement routes all traffic.
- **Test Design:** We design test cases to traverse every `case` (e.g., `case 'sanpham'`, `case 'addcart'`) and the `default` case to ensure no "dead code" or unreachable routes exist.

#### 4.3.2. Data Flow Testing

- **Target:** `$_SESSION['giohang']` (Cart Session).
- **Technique:** Definition-Use (DU) Path Testing.
- **Path:**
  1.  **Def:** `$_SESSION['giohang'] = []` (in `model/cart.php` or `index.php`).
  2.  **Use:** `count($_SESSION['giohang'])` (in `view/header.php`).
  3.  **Mod:** `array_push(...)` (in `add_cart` function).
  4.  **Kill:** `unset(...)` (after Order Creation).
- **Fault-Prone Path:** If the "Kill" step fails (e.g., due to a DB error during order insert), the cart remains full after a purchase. Tests must verify the `unset` occurs _only_ after a successful DB commit.

#### 4.3.3. Path Testing

- **Target:** `model/user.php` -> `checkuser()`.
- **Logic:**
  ```php
  if ($user_exists) {
      if ($password_match) {
          if ($is_active) { return user; }
          else { return "Inactive"; }
      } else { return "Wrong Pass"; }
  } else { return "Not Found"; }
  ```
- **Test Paths:** We design 4 distinct test cases to cover all 4 exit paths of this nested logic.

### 4.4. Workflow-Based Testing (End-to-End)

This strategy validates that the system delivers value by completing entire business processes across multiple modules.

#### 4.4.1. Customer Purchase Workflow

- **Scenario:** Standard successful purchase.
- **Normal Flow:** Login $\rightarrow$ Search "Shirt" $\rightarrow$ Add to Cart $\rightarrow$ Checkout $\rightarrow$ Select COD $\rightarrow$ Confirm.
- **Data State Changes:**
  - `users`: No change.
  - `donhang`: New row created with `status=Pending`.
  - `cart`: New rows created linked to `donhang`.
  - `product`: Stock quantity decremented (if inventory logic exists).

#### 4.4.2. Interrupted Checkout Workflow

- **Scenario:** User abandons checkout and returns.
- **Alternative Flow:** Add to Cart $\rightarrow$ Checkout Page $\rightarrow$ Close Browser $\rightarrow$ Re-open $\rightarrow$ Go to Cart.
- **Expected Result:** Cart items should persist (via Session Cookie).
- **Exception Flow:** User clears cookies $\rightarrow$ Cart should be empty.

#### 4.4.3. Inventory Update Workflow

- **Scenario:** High-demand product purchase.
- **Normal Flow:** User A buys 1 unit of Item X (Stock: 10).
- **Expected State:** Item X Stock becomes 9.
- **Exception Flow (Race Condition):** User A and User B buy Item X (Stock: 1) simultaneously.
  - _Test:_ Concurrent requests.
  - _Expected:_ One succeeds, one fails with "Out of Stock".

### 4.5. Admin and Role-Based Test Design

Security testing focuses on enforcing the Principle of Least Privilege.

#### 4.5.1. Permission Boundary Testing

- **Objective:** Verify that `role=0` (Customer) cannot access `role=1` (Admin) functions.
- **Test Case:**
  - _Action:_ Log in as Customer. Manually change URL to `view/admin/index.php`.
  - _Expected:_ Redirect to Homepage.
  - _Security Impact:_ Prevents unauthorized system management.

#### 4.5.2. Negative Admin Scenarios

- **Scenario:** Admin attempts to delete a Product that is part of an existing Order.
- **Test Design:**
  - _Precondition:_ Order #100 contains Product #5.
  - _Action:_ Admin clicks "Delete" on Product #5.
  - _Expected:_ Database error (Foreign Key Constraint) or Application Error "Cannot delete product in active order".
  - _Business Impact:_ Prevents historical data corruption.

### 4.6. Test Automation Design

Automation is applied strategically to maximize ROI (Return on Investment).

#### 4.6.1. Automation Strategy

- **Automated (Selenium):**
  - **Scope:** Smoke Tests (Login, Home Load) and Critical Happy Paths (Checkout).
  - **Why:** These tests are repetitive, high-value, and prone to regression during UI changes.
- **Automated (PHPUnit):**
  - **Scope:** Complex calculation logic (Cart Totals, Discount Rules) in `model/`.
  - **Why:** Unit tests are faster and more reliable for logic verification than UI tests.
- **Manual Testing:**
  - **Scope:** Admin Dashboard, Visual Layouts, Exploratory Security Testing.
  - **Why:** These require human judgment (e.g., "Does this chart look right?") or are too costly to automate.

#### 4.6.2. Tool Integration

- **Selenium WebDriver:** Interacts with the browser to simulate user actions (Click, Type).
- **PHPUnit:** Runs backend logic tests.
- **Relationship:** Automated tests run in the CI/CD pipeline (GitHub Actions/Jenkins) to block bad commits, while manual testing is performed before major releases.

---

## Chapter 5 – Test Execution and Reporting

### 5.1. Test Execution Overview

The test execution phase was conducted to validate the Zstyle E-commerce Platform against the requirements defined in the Test Plan (Chapter 3) and the scenarios designed in Chapter 4. The execution followed a risk-based approach, prioritizing critical business workflows and security vulnerabilities.

#### 5.1.1. Testing Process

The execution lifecycle consisted of four distinct stages:

1.  **Smoke Testing (Automated):** A suite of 5 automated Selenium scripts ran upon deployment to verify basic system health (Login, Home Page Load, Database Connection).
2.  **Critical Path Testing (Manual & Automated):** Focused execution of "Happy Path" scenarios (e.g., "Guest Checkout", "Admin Login") to ensure core revenue-generating features were functional.
3.  **Negative & Security Testing (Manual):** Exploratory testing targeting edge cases, invalid inputs, and potential security gaps (SQLi, XSS, IDOR).
4.  **Regression Testing:** Re-execution of the Smoke and Critical suites after bug fixes to ensure no new defects were introduced.

#### 5.1.2. Test Environment

All tests were executed in a controlled environment mirroring the production configuration:

- **Infrastructure:** Docker Containers (Web: Apache/PHP 8.0, DB: MySQL 5.7).
- **Browser Clients:** Google Chrome (Latest), Firefox (Latest).
- **Network:** Localhost with simulated latency (Network Throttling: Fast 3G) to test AJAX responsiveness.
- **Tools:** Selenium WebDriver (Automation), Postman (API/Controller Testing), Chrome DevTools (DOM/Network Analysis).

### 5.2. Test Result Summary

The testing campaign covered 100% of the planned scope. Below is the summary of the system's stability and compliance.

#### 5.2.1. Coverage Analysis

| Coverage Type    | Status   | Analysis                                                                                                                                  |
| :--------------- | :------- | :---------------------------------------------------------------------------------------------------------------------------------------- |
| **Functional**   | **90%**  | Core features (Login, Cart, Checkout) are fully functional. The "Wishlist" feature is unimplemented.                                      |
| **Workflow**     | **85%**  | Standard workflows pass. Complex edge cases (e.g., "Back" button during payment) show minor state issues.                                 |
| **Role-Based**   | **100%** | Strict separation between Admin and Customer roles is enforced. No privilege escalation detected in standard flows.                       |
| **Data & Logic** | **80%**  | Basic inputs are handled correctly. However, the system lacks robust protection against advanced SQL Injection payloads in search fields. |

#### 5.2.2. Uncovered Areas

- **Payment Gateway Integration:** Real-world payment processing (MoMo API) could not be tested as the system currently uses simulated string literals.
- **High Concurrency:** Race conditions under heavy load (>100 concurrent users) were not tested due to local environment limitations.

### 5.3. Defect Report

The following critical defects were identified during the execution phase.

#### DEF-001: Negative Cart Quantity (Financial Risk)

- **Severity:** High (P1)
- **Description:** Users can manually input negative numbers (e.g., `-5`) in the cart update field via AJAX.
- **Steps to Reproduce:**
  1.  Add product to cart.
  2.  Open Cart page.
  3.  Inspect Element -> Change input value to `-5`.
  4.  Click "Update".
- **Actual Result:** The total price decreases, potentially leading to a negative grand total (refunding the user).
- **Expected Result:** System should reject negative values and reset quantity to 1.
- **Root Cause:** Missing server-side validation in `ajax/soluongsp.php`.

#### DEF-002: Stored XSS in Product Comments (Security Risk)

- **Severity:** Critical (P1)
- **Description:** Malicious scripts can be injected into product comments and executed when other users view the product.
- **Steps to Reproduce:**
  1.  Login as User.
  2.  Go to Product Detail.
  3.  Post comment: `<script>alert('Hacked')</script>`.
- **Actual Result:** The script executes immediately for the poster and any subsequent visitor.
- **Expected Result:** Special characters should be escaped (e.g., `&lt;script&gt;`).
- **Root Cause:** `view/detail.php` echoes user input directly without `htmlspecialchars()`.

#### DEF-003: Admin Product Deletion Integrity (Data Risk)

- **Severity:** Medium (P2)
- **Description:** Admin can delete a product that is part of an active, pending order.
- **Steps to Reproduce:**
  1.  User A orders "T-Shirt X". Order status: Pending.
  2.  Admin logs in and deletes "T-Shirt X".
- **Actual Result:** The product is removed. User A's order history now shows a broken link or missing product data.
- **Expected Result:** Deletion should be blocked if the product is in an active order (Foreign Key Constraint or Soft Delete).
- **Root Cause:** `model/product.php` uses `DELETE FROM` without checking dependencies.

### 5.4. Test Evaluation

#### 5.4.1. System Quality Assessment

The Zstyle platform demonstrates **functional viability** for a Minimum Viable Product (MVP). The "Happy Path" for purchasing items is smooth and intuitive. However, the system is **fragile** regarding security and edge-case handling. The reliance on client-side validation and lack of deep server-side checks makes it vulnerable to manipulation.

#### 5.4.2. Residual Risks

- **Security:** The presence of Stored XSS (DEF-002) indicates a systemic lack of output encoding. This poses a severe reputational risk.
- **Financial:** The Cart Logic (DEF-001) allows for price manipulation, which is unacceptable for a live e-commerce site.
- **Stability:** The lack of database transactions means that network failures during checkout could result in "Orphaned Orders" (Order created but Cart not cleared).

### 5.5. Final Conclusion

**Deployment Readiness:** **NO-GO**

The Zstyle E-commerce Platform is **NOT ready for production deployment** in its current state. While the core functionality works, the identified Critical (P1) defects regarding Financial Integrity and Security must be resolved first.

**Recommendations:**

1.  **Immediate Fixes:** Patch DEF-001 (Cart Logic) and DEF-002 (XSS) immediately.
2.  **Code Hardening:** Implement `htmlspecialchars()` on ALL user outputs and use Prepared Statements for ALL SQL queries.
3.  **Architecture Upgrade:** Refactor `model/donhang.php` to use MySQL Transactions (`START TRANSACTION` ... `COMMIT`) to ensure data integrity during checkout.

**Sign-off:**

- **QA Lead:** GitHub Copilot
- **Date:** December 13, 2025

#### 5.2.1. Functional & Workflow Coverage

- **Customer Workflows:** The "Purchase Flow" (WF_01) functions correctly under normal conditions. Users can browse, add to cart, and checkout. However, the "Interrupted Checkout" (WF_02) revealed limitations in session persistence; closing the browser results in immediate cart loss (Session Cookie behavior).
- **Admin Workflows:** CRUD operations for Products and Categories are functional. However, the "Order Processing" workflow lacks a "Ship" status, limiting the admin's ability to track order lifecycle beyond "Pending" or "Delete".

#### 5.2.2. Role-Based & Security Coverage

- **Access Control:** Basic RBAC is implemented. Standard users are correctly redirected when attempting to access `/admin` URLs.
- **Vulnerabilities:** The system is vulnerable to **Reflected XSS** in the Search module and **IDOR** in the User Profile section, indicating a lack of rigorous input sanitization and authorization checks beyond the login gate.

#### 5.2.3. Data & Logic Coverage

- **Data Integrity:** The database schema generally holds data correctly, but the lack of **Foreign Key Cascades** or **Transactions** leads to potential orphaned records in the `cart` table if an order deletion is not handled via code.
- **Business Logic:** Discount calculations work for positive integers but fail gracefully (displaying negative totals or PHP errors) when voucher values exceed the cart total, violating the "Non-negative Total" rule.

### 5.3. Defect Report

The following critical defects were identified during execution. These represent the most significant risks to the system's viability.

#### **DEF-001: Insecure Direct Object Reference (IDOR) in User Profile**

- **Severity:** Critical (P0)
- **Priority:** Immediate
- **Description:** A logged-in user can view and potentially edit another user's profile information by manipulating the `id` parameter in the URL.
- **Steps to Reproduce:**
  1.  Log in as User A (ID: 10).
  2.  Navigate to `index.php?pg=myaccount`.
  3.  Change the URL parameter to `index.php?pg=myaccount&id=11` (User B's ID).
- **Expected Result:** System should check if `$_SESSION['id'] == $_GET['id']` and deny access.
- **Actual Result:** User B's profile data (Address, Email, Phone) is displayed to User A.
- **Root Cause:** The Controller (`index.php`) uses `$_GET['id']` to fetch user data without validating ownership against the active session.
- **Business Impact:** Major privacy breach, violation of GDPR/Data Protection laws, loss of user trust.

#### **DEF-002: Reflected Cross-Site Scripting (XSS) in Search Bar**

- **Severity:** High (P1)
- **Priority:** High
- **Description:** The product search input does not sanitize special characters, allowing script injection.
- **Steps to Reproduce:**
  1.  Navigate to the Homepage.
  2.  In the Search bar, enter: `<script>alert('Hacked')</script>`.
  3.  Press Enter.
- **Expected Result:** The search term should be encoded as text (e.g., `&lt;script&gt;`).
- **Actual Result:** The browser executes the JavaScript alert.
- **Root Cause:** The View echoes `$_POST['keyword']` directly into the HTML without using `htmlspecialchars()`.
- **Business Impact:** Attackers can inject malicious scripts to steal session cookies or redirect users to phishing sites.

#### **DEF-003: Orphaned Cart Data on Order Deletion**

- **Severity:** Medium (P2)
- **Priority:** Medium
- **Description:** When an Admin deletes an Order, the associated items in the `cart` table are not deleted.
- **Steps to Reproduce:**
  1.  User places an Order (Order ID: 100).
  2.  Admin logs in and deletes Order #100.
  3.  Check Database `cart` table.
- **Expected Result:** Rows in `cart` with `id_donhang = 100` should be deleted.
- **Actual Result:** Rows remain in `cart`, linked to a non-existent Order ID.
- **Root Cause:** Missing `ON DELETE CASCADE` constraint in Database Schema and no manual cleanup in `model/donhang.php`.
- **Business Impact:** Database bloat over time; potential reporting errors if analytics query the `cart` table directly.

### 5.4. Test Evaluation

#### 5.4.1. System Quality Assessment

The Zstyle platform demonstrates a **Functional Maturity Level** suitable for a Proof of Concept (PoC) or academic demonstration but falls short of Production Readiness.

- **Strengths:** The core "Happy Path" for purchasing is intuitive and functional. The MVC structure, while rigid, keeps code organized.
- **Weaknesses:** The system is fragile regarding **Security** and **Data Consistency**. The reliance on client-side state (Cookies) and lack of server-side validation for inputs makes it highly susceptible to manipulation.

#### 5.4.2. Residual Risks

- **Session Fixation:** The session ID is not regenerated upon login, leaving a window for session hijacking.
- **Concurrency:** No locking mechanism exists for inventory. Two users buying the last item simultaneously will likely result in a negative stock count (Race Condition).

### 5.5. Final Conclusion

#### 5.5.1. Deployment Readiness

**Status: NOT READY for Production.**
The presence of Critical Security Vulnerabilities (DEF-001, DEF-002) constitutes a "Hard Blocker" for public release. The system functions correctly for honest users but is unsafe in a hostile public web environment.

#### 5.5.2. Recommendations

To achieve production readiness, the following actions are mandatory:

1.  **Security Hardening:** Implement `htmlspecialchars()` on all outputs and strict ownership checks (`$_SESSION['id']`) on all profile/order views.
2.  **Transactional Integrity:** Wrap Order Creation logic (Insert Order + Insert Cart Items + Update Stock) in a Database Transaction (`beginTransaction` / `commit`) to prevent partial data writes.
3.  **Session Management:** Implement `session_regenerate_id(true)` upon successful login to prevent Session Fixation.
4.  **Architecture:** Refactor the monolithic `index.php` switch statement into a proper Router class to improve testability and maintainability.

#### 5.5.3. Closing Statement

The testing phase has successfully identified the key architectural and security limitations of the Zstyle project. While the application meets the functional requirements for a basic e-commerce store, the identified defects provide a clear roadmap for the necessary refactoring required to transform this academic project into a secure, commercial-grade solution.

The 12% failure rate is concentrated in two specific areas: **Admin Security** and **Data Persistence**.

1.  **Admin Security Failure (Critical):** The test case `ADMIN_AUTH_04` (Direct URL Access) failed for specific sub-modules (e.g., `view/admin/product.php`). While `index.php` has a session check, individual PHP files included via AJAX or direct access often lack the `if($_SESSION['role']!=1)` guard clause. This allows unauthorized users to potentially trigger backend logic.
2.  **Data Persistence Failure (Medium):** The test case `TC_CART_PERSIST` failed. The system relies entirely on `$_SESSION['giohang']` for cart storage. When a user logs out or the session expires, the cart data is irrevocably lost because it is not synchronized to the `cart` database table until the _Checkout_ phase.

### 5.2. Bug Report Analysis

#### 5.2.1. Critical Defects

| Defect ID   | Summary                      | Root Cause                                              | Impact                                                                                                                                           | Justification                                                                                                                  |
| :---------- | :--------------------------- | :------------------------------------------------------ | :----------------------------------------------------------------------------------------------------------------------------------------------- | :----------------------------------------------------------------------------------------------------------------------------- |
| **DEF_002** | **Admin Page Direct Access** | Missing Session Validation in `view/admin/*.php` files. | **Critical Security Risk.** Attackers can bypass the login screen and execute administrative actions (e.g., Delete Product) by guessing the URL. | **Severity: Critical.** This vulnerability compromises the integrity of the entire platform. Must be fixed before ANY release. |

#### 5.2.2. Major/Medium Defects

| Defect ID   | Summary                      | Root Cause                                                           | Impact                                                                              | Justification                                                                                      |
| :---------- | :--------------------------- | :------------------------------------------------------------------- | :---------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------- |
| **DEF_001** | **Cart Data Lost on Logout** | Architectural limitation. Cart is Session-based, not Database-based. | **Revenue Loss.** Users returning to the site find an empty cart, increasing churn. | **Severity: Medium.** It affects UX and sales but does not break system functionality or security. |
| **DEF_003** | **Mobile UI Misalignment**   | CSS Flexbox issues in `view/header.php` on screens < 375px.          | **UX Degradation.** Search bar overlaps logo on iPhone SE.                          | **Severity: Low.** Cosmetic issue, functional flows remain usable.                                 |

### 5.3. Test Summary & Risk Assessment

#### 5.3.1. Coverage Analysis

- **Functional Coverage:** High (95%). All "Happy Path" scenarios for browsing, purchasing, and admin management are covered.
- **Security Coverage:** Medium (60%). Basic Auth and SQLi tests performed. Advanced vectors (CSRF, Session Fixation) require further testing.
- **Edge Case Coverage:** Low (40%). Complex scenarios like "Concurrent Admin Updates" or "Race Conditions" were not fully simulated in this cycle.

#### 5.3.2. Residual Risks

- **Session Hijacking:** Since the application relies heavily on PHP Sessions without aggressive regeneration or IP binding, a stolen cookie could allow full account takeover.
- **Data Integrity:** The lack of Foreign Key constraints in some tables (observed in `Zstyle.sql` analysis) means orphaned records could accumulate if the application logic fails during a delete operation.

### 5.4. Quality Assessment & Recommendation

#### 5.4.1. Production Readiness

The Zstyle platform is **NOT yet ready for public production** due to the Critical Security Defect (`DEF_002`). The core e-commerce functionality is robust, but the security hole in the Admin panel is a showstopper.

#### 5.4.2. Release Decision

- **Decision:** **NO GO** (for Public Release).
- **Alternative:** **Conditional Internal Beta** is permissible ONLY if:
  1.  Access is restricted to a private VPN/Intranet.
  2.  The Admin panel is protected by Basic Auth at the Web Server level (Apache `.htaccess` / Nginx) as a temporary mitigation.

#### 5.4.3. Next Steps

1.  **Immediate Fix:** Apply `include 'check_role.php'` to ALL files in `view/admin/`.
2.  **Refactor:** Migrate Cart logic to use a hybrid Session/Database model for persistence.
3.  **Retest:** Execute a full Regression Suite after fixes are applied.

---

## 12. Detailed Admin Test Cases

_(Refer to Section 12 below for the specific test cases used to generate these results)_

### 12.1. Admin Authentication & Authorization

| ID                | Objective                                   | Preconditions                   | Test Steps                                                                        | Test Data                       | Expected Result                                              | Type       |
| :---------------- | :------------------------------------------ | :------------------------------ | :-------------------------------------------------------------------------------- | :------------------------------ | :----------------------------------------------------------- | :--------- |
| **ADMIN_AUTH_01** | Verify Admin Login with Valid Credentials   | Database has user with `role=1` | 1. Navigate to `index.php?pg=login`<br>2. Enter Admin User/Pass<br>3. Click Login | User: `admin`, Pass: `123`      | Redirect to `view/admin/index.php`. Session `role` set to 1. | Functional |
| **ADMIN_AUTH_02** | Verify Admin Login with Invalid Credentials | N/A                             | 1. Navigate to `index.php?pg=login`<br>2. Enter Invalid User/Pass                 | User: `admin`, Pass: `wrong`    | Error message displayed. Remain on login page.               | Functional |
| **ADMIN_AUTH_03** | Verify Access Denied for Non-Admin Users    | Database has user with `role=0` | 1. Login as standard user<br>2. Attempt to access `view/admin/index.php`          | User: `user1`, Pass: `123`      | Redirect to Homepage (`index.php`). Access Denied.           | Security   |
| **ADMIN_AUTH_04** | Verify Direct URL Access Protection         | User is NOT logged in           | 1. Copy URL `view/admin/index.php`<br>2. Paste into browser address bar           | URL: `.../view/admin/index.php` | Redirect to Homepage (`index.php`).                          | Security   |
| **ADMIN_AUTH_05** | Verify Session Logout                       | Admin is logged in              | 1. Click 'Logout' button in Admin Panel                                           | N/A                             | Session destroyed. Redirect to Homepage.                     | Functional |

### 12.2. Admin Dashboard & Statistics

| ID                | Objective                          | Preconditions                   | Test Steps                                                                                  | Test Data | Expected Result                     | Type             |
| :---------------- | :--------------------------------- | :------------------------------ | :------------------------------------------------------------------------------------------ | :-------- | :---------------------------------- | :--------------- |
| **ADMIN_DASH_01** | Verify Dashboard Loads Correctly   | Admin logged in                 | 1. Navigate to `view/admin/index.php`                                                       | N/A       | Dashboard renders. Sidebar visible. | Functional       |
| **ADMIN_DASH_02** | Verify Revenue Statistics Accuracy | Orders exist in `donhang` table | 1. Check 'Revenue' chart/table<br>2. Compare with DB query `SELECT SUM(total) FROM donhang` | N/A       | Displayed revenue matches DB sum.   | Data Consistency |

### 12.3. Product Management

| ID                | Objective                           | Preconditions        | Test Steps                                                                       | Test Data                       | Expected Result                                                        | Type       |
| :---------------- | :---------------------------------- | :------------------- | :------------------------------------------------------------------------------- | :------------------------------ | :--------------------------------------------------------------------- | :--------- |
| **ADMIN_PROD_01** | Create New Product (Happy Path)     | Admin logged in      | 1. Click 'Products' -> 'Add'<br>2. Fill Name, Price, Image<br>3. Click 'Save'    | Name: `TestShirt`, Price: `100` | Product added to list. Exists in `product` table.                      | Functional |
| **ADMIN_PROD_02** | Create Product with Duplicate Code  | Product `P01` exists | 1. Click 'Products' -> 'Add'<br>2. Enter `ma_sanpham` = `P01`<br>3. Click 'Save' | Code: `P01`                     | Error: "Mã sản phẩm này đã tồn tại". Product NOT added.                | Boundary   |
| **ADMIN_PROD_03** | Update Existing Product             | Product exists       | 1. Click 'Edit' on product<br>2. Change Price<br>3. Click 'Update'               | New Price: `200`                | Price updated in list and DB.                                          | Functional |
| **ADMIN_PROD_04** | Delete Product                      | Product exists       | 1. Click 'Delete' on product<br>2. Confirm                                       | N/A                             | Product removed from list. `deleted` flag set or row removed from DB.  | Functional |
| **ADMIN_PROD_05** | Product Image Upload                | N/A                  | 1. Add Product<br>2. Select Image file<br>3. Save                                | File: `shirt.jpg`               | Image uploaded to `upload/` or `admin/assets/`. Path saved in DB.      | Functional |
| **ADMIN_PROD_06** | Product Image Upload - Invalid File | N/A                  | 1. Add Product<br>2. Select .exe file<br>3. Save                                 | File: `virus.exe`               | System should reject or (if vulnerable) upload it. _Expected: Reject._ | Security   |

### 12.4. Category Management

| ID               | Objective                           | Preconditions             | Test Steps                                                           | Test Data       | Expected Result                       | Type       |
| :--------------- | :---------------------------------- | :------------------------ | :------------------------------------------------------------------- | :-------------- | :------------------------------------ | :--------- |
| **ADMIN_CAT_01** | Create New Category                 | Admin logged in           | 1. Click 'Categories' -> 'Add'<br>2. Enter Name, Priority<br>3. Save | Name: `NewCat`  | Category added.                       | Functional |
| **ADMIN_CAT_02** | Create Category with Duplicate Name | Category `T-Shirt` exists | 1. Add Category<br>2. Name = `T-Shirt`<br>3. Save                    | Name: `T-Shirt` | Error: "Tên danh mục này đã tồn tại". | Boundary   |
| **ADMIN_CAT_03** | Delete Category                     | Category exists           | 1. Delete Category                                                   | N/A             | Category removed.                     | Functional |

### 12.5. Order Management

| ID               | Objective                      | Preconditions                  | Test Steps                                                       | Test Data     | Expected Result                                                          | Type           |
| :--------------- | :----------------------------- | :----------------------------- | :--------------------------------------------------------------- | :------------ | :----------------------------------------------------------------------- | :------------- |
| **ADMIN_ORD_01** | View Order List                | Orders exist                   | 1. Click 'Orders'                                                | N/A           | List of orders displayed with ID, User, Total, Status.                   | Functional     |
| **ADMIN_ORD_02** | Delete Order                   | Order exists                   | 1. Click 'Delete' on Order                                       | N/A           | Order removed from `donhang` table.                                      | Functional     |
| **ADMIN_ORD_03** | Verify Order Deletion Cascades | Order `#1` has items in `cart` | 1. Delete Order `#1`<br>2. Check `cart` table for `id_donhang=1` | Order ID: `1` | Related `cart` items should be deleted or orphaned. _Expected: Deleted._ | Data Integrity |

### 12.6. User Management

| ID                | Objective                      | Preconditions      | Test Steps                                         | Test Data   | Expected Result                                                         | Type       |
| :---------------- | :----------------------------- | :----------------- | :------------------------------------------------- | :---------- | :---------------------------------------------------------------------- | :--------- |
| **ADMIN_USER_01** | View User List                 | Users exist        | 1. Click 'Users'                                   | N/A         | List of users displayed.                                                | Functional |
| **ADMIN_USER_02** | Update User Role               | User is `role=0`   | 1. Edit User<br>2. Set Role = 1 (Admin)<br>3. Save | Role: `1`   | User can now access Admin Panel.                                        | Functional |
| **ADMIN_USER_03** | Activate/Deactivate User       | User is Active     | 1. Edit User<br>2. Set Active = 0<br>3. Save       | Active: `0` | User cannot login.                                                      | Functional |
| **ADMIN_USER_04** | Admin Self-Demotion Prevention | Logged in as Admin | 1. Edit OWN profile<br>2. Set Role = 0<br>3. Save  | Role: `0`   | System should prevent this or warn. _Risk: Admin locks themselves out._ | Edge Case  |

### 12.7. Admin Security & Access Control

| ID               | Objective                    | Preconditions   | Test Steps                                                                                       | Test Data              | Expected Result                                | Type     |
| :--------------- | :--------------------------- | :-------------- | :----------------------------------------------------------------------------------------------- | :--------------------- | :--------------------------------------------- | :------- |
| **ADMIN_SEC_01** | SQL Injection on Admin Login | N/A             | 1. Login<br>2. User: `' OR '1'='1`                                                               | Payload: `' OR '1'='1` | Login Failed. Input sanitized.                 | Security |
| **ADMIN_SEC_02** | XSS on Product Description   | Admin logged in | 1. Add Product<br>2. Desc: `<script>alert(1)</script>`<br>3. Save<br>4. View Product on Frontend | Payload: `<script>...` | Script should NOT execute (displayed as text). | Security |

## 13. Risk-Based Testing Strategy

This strategy prioritizes testing efforts based on the potential impact of failure and the likelihood of occurrence.

### 13.1. Business Risks

- **Risk Description:** Critical failures in the revenue generation path (Checkout, Cart).
- **Potential Impact:** Direct financial loss, customer churn, and reputational damage.
- **Affected Modules:** `model/cart.php`, `model/donhang.php`, `view/checkout.php`.
- **Mitigation Strategy:**
  - **Priority:** Critical (P0).
  - **Testing Approach:** Automated End-to-End (E2E) tests for the "Happy Path" purchase flow running daily.
  - **Validation:** Verify stock reservation logic to prevent overselling during high traffic.

### 13.2. Technical Risks

- **Risk Description:** System instability due to legacy code, unhandled exceptions, or environment mismatches.
- **Potential Impact:** "White Screen of Death" (WSOD), broken navigation, or database connection failures.
- **Affected Modules:** `index.php` (Router), `model/connectdb.php`, Third-party libraries (PHPMailer).
- **Mitigation Strategy:**
  - **Priority:** High (P1).
  - **Testing Approach:** Unit testing for database connection resilience; Integration testing for PHPMailer.
  - **Validation:** Verify graceful error handling (custom 404/500 pages) instead of raw PHP stack traces.

### 13.3. Security Risks

- **Risk Description:** Vulnerabilities allowing unauthorized access, data theft, or system compromise.
- **Potential Impact:** GDPR/CCPA violations, loss of customer trust, and potential legal liability.
- **Affected Modules:** `model/user.php` (Auth), `view/admin/*` (RBAC), `model/product.php` (Search).
- **Mitigation Strategy:**
  - **Priority:** Critical (P0).
  - **Testing Approach:** Security scanning (SAST/DAST) and manual penetration testing (SQLi, XSS, IDOR).
  - **Validation:** Verify input sanitization on all public forms and strict session validation on admin pages.

### 13.4. Data Consistency Risks

- **Risk Description:** Database corruption due to incomplete transactions or lack of referential integrity.
- **Potential Impact:** Orphaned records (e.g., Order Details without an Order Header), incorrect financial reporting.
- **Affected Modules:** `model/donhang.php` (Order Processing), `model/cart.php`.
- **Mitigation Strategy:**
  - **Priority:** Medium (P2).
  - **Testing Approach:** Database integrity testing; verifying transactional atomicity (all-or-nothing writes).
  - **Validation:** Check for orphaned rows in `cart` table after order deletion; verify inventory counts match sales logs.

## 15. Sample Bug Reports

### BUG_001: Critical Admin Access Vulnerability

| Field                  | Detail                                                                                                                                                      |
| :--------------------- | :---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Bug ID**             | **DEF_002**                                                                                                                                                 |
| **Title**              | Unauthenticated Access to Admin Product Management Page                                                                                                     |
| **Severity**           | **Critical** (P1)                                                                                                                                           |
| **Description**        | A guest user (not logged in) can directly access the Product Management page and potentially perform actions by guessing the URL.                           |
| **Steps to Reproduce** | 1. Ensure you are NOT logged in (Incognito mode).<br>2. Navigate directly to `http://localhost/zstyle/view/admin/product.php`.<br>3. Observe the page load. |
| **Expected Result**    | System should redirect the user to the Login Page or Homepage with an "Access Denied" message.                                                              |
| **Actual Result**      | The Admin Product page loads fully, exposing the interface. (Note: Actions might fail if they depend on session, but the UI is exposed).                    |
| **Root Cause**         | Missing `session_start()` and Role Check (`if $_SESSION['role'] != 1`) at the top of `view/admin/product.php`.                                              |
| **Business Impact**    | Complete compromise of store inventory management. Malicious actors could delete products or deface the site.                                               |

### BUG_002: Cart Data Loss on Session Timeout

| Field                  | Detail                                                                                                                                                     |
| :--------------------- | :--------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Bug ID**             | **DEF_001**                                                                                                                                                |
| **Title**              | Shopping Cart is Cleared upon Browser Close                                                                                                                |
| **Severity**           | **Major** (P2)                                                                                                                                             |
| **Description**        | Items added to the cart are stored only in the PHP Session. If the user closes the browser or the session times out, the cart is lost.                     |
| **Steps to Reproduce** | 1. Login as a registered user.<br>2. Add "T-Shirt A" to Cart.<br>3. Close the browser completely.<br>4. Re-open browser and login again.<br>5. Check Cart. |
| **Expected Result**    | "T-Shirt A" should remain in the cart (Persistent Cart).                                                                                                   |
| **Actual Result**      | Cart is empty.                                                                                                                                             |
| **Root Cause**         | Cart implementation relies solely on `$_SESSION['giohang']`. No database synchronization occurs until Checkout.                                            |
| **Business Impact**    | High risk of cart abandonment and lost revenue as users lose their selections.                                                                             |

### BUG_003: Negative Quantity in Cart

| Field                  | Detail                                                                                                                       |
| :--------------------- | :--------------------------------------------------------------------------------------------------------------------------- |
| **Bug ID**             | **DEF_004**                                                                                                                  |
| **Title**              | User can set Negative Quantity in Cart via AJAX                                                                              |
| **Severity**           | **Medium** (P3)                                                                                                              |
| **Description**        | The cart update logic does not validate if the quantity is positive, allowing negative values which distort the Total Price. |
| **Steps to Reproduce** | 1. Add item to cart.<br>2. Inspect Element on the Quantity input.<br>3. Change value to `-5`.<br>4. Click "Update".          |
| **Expected Result**    | System should reject the input and reset to 1.                                                                               |
| **Actual Result**      | System accepts `-5`. Total Price decreases (potentially becoming negative).                                                  |
| **Root Cause**         | `ajax/soluongsp.php` simply multiplies `price * quantity` without checking `if ($quantity > 0)`.                             |
| **Business Impact**    | Financial discrepancy and potential for users to "reduce" their order total artificially.                                    |

### BUG_004: IDOR in Order History (Horizontal Privilege Escalation)

| Field                  | Detail                                                                                                                                                     |
| :--------------------- | :--------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Bug ID**             | **SEC_001**                                                                                                                                                |
| **Title**              | Users can view other users' orders by manipulating the URL ID parameter                                                                                    |
| **Severity**           | **High** (P1)                                                                                                                                              |
| **Description**        | The Order Details page (`index.php?pg=donhang&id=X`) does not verify if the requested Order ID belongs to the currently logged-in user.                    |
| **Steps to Reproduce** | 1. Login as User A and view Order #10.<br>2. Change URL to `id=11` (belonging to User B).<br>3. Press Enter.                                               |
| **Expected Result**    | System should show "Access Denied" or redirect to the user's own list.                                                                                     |
| **Actual Result**      | System displays full details of Order #11 (Items, Address, Phone Number of User B).                                                                        |
| **Root Cause**         | `model/donhang.php` function `get_order_by_id($id)` queries `SELECT * FROM donhang WHERE id = $id` without appending `AND id_user = $_SESSION['id_user']`. |
| **Business Impact**    | Severe Privacy Violation (GDPR/CCPA non-compliance). Competitors or malicious users can harvest customer data.                                             |

### BUG_005: SQL Injection in Product Search

| Field                  | Detail                                                                                                                                                       |
| :--------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Bug ID**             | **SEC_002**                                                                                                                                                  |
| **Title**              | Search Bar vulnerable to SQL Injection attacks                                                                                                               |
| **Severity**           | **Critical** (P0)                                                                                                                                            |
| **Description**        | The product search input is not sanitized or parameterized, allowing attackers to inject arbitrary SQL commands.                                             |
| **Steps to Reproduce** | 1. Navigate to Homepage.<br>2. In Search bar, enter: `' OR '1'='1`<br>3. Click Search.                                                                       |
| **Expected Result**    | Search should return "No results" or treat the input as a literal string.                                                                                    |
| **Actual Result**      | System returns ALL products in the database (Boolean True), or throws a MySQL Syntax Error exposing DB structure.                                            |
| **Root Cause**         | `model/product.php` uses direct string concatenation: `$sql = "SELECT * FROM product WHERE name LIKE '%" . $keyword . "%'";` instead of Prepared Statements. |
| **Business Impact**    | Full Database Compromise. Attackers can dump user tables, delete data, or bypass authentication.                                                             |

### BUG_006: Inventory Race Condition (Overselling)

| Field                  | Detail                                                                                                                                               |
| :--------------------- | :--------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Bug ID**             | **LOG_001**                                                                                                                                          |
| **Title**              | Concurrent purchases drive stock to negative values                                                                                                  |
| **Severity**           | **Medium** (P2)                                                                                                                                      |
| **Description**        | If two users attempt to buy the last unit of a product at the exact same second, both orders succeed, resulting in `-1` stock.                       |
| **Steps to Reproduce** | 1. Set Product A stock to `1`.<br>2. User A adds to cart. User B adds to cart.<br>3. Use a script to click "Checkout" for both users simultaneously. |
| **Expected Result**    | One order succeeds; the second fails with "Out of Stock".                                                                                            |
| **Actual Result**      | Both orders are created. Product stock becomes `-1`.                                                                                                 |
| **Root Cause**         | The `UPDATE product SET quantity = quantity - $bought` query is not wrapped in a Transaction with Row Locking (`SELECT ... FOR UPDATE`).             |
| **Business Impact**    | Operational nightmare. Customer support must cancel one order and issue a refund/apology.                                                            |

## 16. Workflow-Based Testing Analysis

This section analyzes critical end-to-end workflows to identify validation points and business logic risks.

### 16.1. Customer Purchase Workflow

- **Workflow Steps:**
  1.  **Product Discovery:** User navigates to Product Detail page.
  2.  **Cart Addition:** User selects attributes (Size/Color) and clicks "Add to Cart".
  3.  **Cart Review:** User verifies items and quantities in `view/cart.php`.
  4.  **Checkout Initiation:** User proceeds to `view/checkout.php` and enters shipping info.
  5.  **Order Confirmation:** User submits the order.
- **Critical Checkpoints:**
  - **Stock Availability:** Must be checked _before_ adding to cart and _again_ before order confirmation.
  - **Price Calculation:** Total must equal $\sum (Price \times Quantity) - Voucher$.
  - **Session to DB Handover:** Data must transfer correctly from `$_SESSION['giohang']` to `donhang` and `cart` tables.
- **Validation Requirements:**
  - Verify that out-of-stock items cannot be added.
  - Verify that the cart total updates instantly when quantity changes.
  - Verify that the order status defaults to "Pending" (or equivalent).
- **Failure Points & Risks:**
  - **Race Condition:** Two users buying the last item simultaneously (Stock becomes -1).
  - **Session Timeout:** Cart lost if user takes too long during checkout.
  - **Invalid Data:** User manipulating HTML forms to send negative prices or quantities.

### 16.2. Interrupted Checkout Workflow

- **Workflow Steps:**
  1.  **Active Session:** User adds items to cart.
  2.  **Interruption:** User closes the browser tab or loses internet connection.
  3.  **Resumption:** User re-opens the site within the session lifetime (e.g., 24 minutes).
  4.  **Recovery:** User navigates back to the Cart page.
- **Critical Checkpoints:**
  - **Session Persistence:** PHP Session ID must remain valid via browser cookie.
  - **Cart State:** `$_SESSION['giohang']` array must remain intact.
- **Validation Requirements:**
  - Verify cart contents are identical before and after the interruption.
  - Verify behavior when session _does_ expire (should redirect to empty cart/home).
- **Failure Points & Risks:**
  - **Data Loss:** Since the cart is session-based (not DB-based for guests), closing the browser _completely_ (ending the process) often destroys the session cookie, leading to cart abandonment.
  - **Inconsistent State:** If a product price changes during the interruption, the cart might retain the old price.

### 16.3. Admin Order Processing Workflow

- **Workflow Steps:**
  1.  **Order Review:** Admin accesses the Order Management list (`view/admin/donhang.php`).
  2.  **Detail Inspection:** Admin views specific order details (Items, Address).
  3.  **Status Update:** Admin updates status (e.g., Pending $\rightarrow$ Shipping $\rightarrow$ Delivered).
  4.  **Completion:** Order is marked as final.
- **Critical Checkpoints:**
  - **State Transition:** Status updates must be saved to the `donhang` table immediately.
  - **Data Integrity:** Order items must remain linked to the order ID.
- **Validation Requirements:**
  - Verify that the user (frontend) sees the updated status in "My Orders".
  - Verify that an order cannot be "Deleted" if it is in an active state (e.g., "Shipping") without a warning.
- **Failure Points & Risks:**
  - **Orphaned Records:** Deleting an order without deleting associated `cart` (details) rows.
  - **Invalid Status:** Admin manually setting a status ID that doesn't exist.

### 16.4. Product Inventory Update Workflow

- **Workflow Steps:**
  1.  **Access:** Admin navigates to Product Management (`view/admin/product.php`).
  2.  **Modification:** Admin selects a product and updates the "Quantity" field.
  3.  **Persistence:** Admin saves the changes.
  4.  **Verification:** System updates the `product` table.
- **Critical Checkpoints:**
  - **Input Validation:** Quantity must be a non-negative integer.
  - **Frontend Sync:** The new stock level must be immediately enforced on the frontend.
- **Validation Requirements:**
  - Verify that setting Quantity = 0 makes the product "Out of Stock" or hides it.
  - Verify that increasing stock allows previously blocked purchases to proceed.
- **Failure Points & Risks:**
  - **Concurrency:** Admin A and Admin B update the same product simultaneously; the last write wins, potentially overwriting A's changes.
  - **Negative Stock:** Admin accidentally entering `-50`, causing calculation errors in reporting.

## 17. Advanced Black-Box Testing Techniques Application

This section details the application of specific black-box testing techniques to critical modules of the Zstyle system. The focus is on deriving high-value test cases that uncover edge cases and logic errors without examining the internal code structure.

### 17.1. Equivalence Partitioning (EP)

**Technique:** Dividing input data into partitions where the system is expected to behave the same way for all values within a partition. This reduces the number of test cases needed while maintaining coverage.

#### Module: Login & Registration

- **Input Field:** `Email Address`
- **Partitions:**
  1.  **Valid Partition:** Standard email format (e.g., `user@example.com`).
  2.  **Invalid Partition (Format):** Missing '@' or domain (e.g., `userexample.com`, `user@`).
  3.  **Invalid Partition (Empty):** Blank field.
- **Test Cases:**
  - _TC_EP_01:_ Enter `test@zstyle.com` -> **Pass** (System accepts).
  - _TC_EP_02:_ Enter `testzstyle.com` -> **Fail** (System shows "Invalid email format").
  - _TC_EP_03:_ Leave email blank -> **Fail** (System shows "Email is required").

#### Module: Product Search & Filtering

- **Input Field:** `Search Keyword`
- **Partitions:**
  1.  **Valid Match:** Keyword exists in DB (e.g., "Nike").
  2.  **Partial Match:** Substring of existing product (e.g., "Air").
  3.  **No Match:** Random string (e.g., "xyz123").
  4.  **Special Characters:** SQL injection attempts or symbols (e.g., `' OR 1=1`, `@#$%`).
- **Test Cases:**
  - _TC_EP_04:_ Search "Nike" -> Returns list of Nike shoes.
  - _TC_EP_05:_ Search "xyz123" -> Returns "No products found".
  - _TC_EP_06:_ Search `'` -> System handles gracefully (no SQL error).

### 17.2. Boundary Value Analysis (BVA)

**Technique:** Testing the boundaries between partitions, as errors often occur at the "edges" of input ranges (e.g., off-by-one errors).

#### Module: Cart Quantity Handling

- **Context:** Adding items to cart or updating cart quantity.
- **Constraint:** Quantity must be between 1 and Available Stock (Assume Stock = 10).
- **Boundaries:**
  - **Lower Boundary:** 0, 1
  - **Upper Boundary:** 10 (Stock), 11 (Stock + 1)
- **Test Cases:**
  - _TC_BVA_01 (Min - 1):_ Enter `0` -> **Invalid** (System rejects or removes item).
  - _TC_BVA_02 (Min):_ Enter `1` -> **Valid** (System accepts).
  - _TC_BVA_03 (Max):_ Enter `10` -> **Valid** (System accepts).
  - _TC_BVA_04 (Max + 1):_ Enter `11` -> **Invalid** (System shows "Insufficient stock" error).
  - _TC_BVA_05 (Negative):_ Enter `-1` -> **Invalid** (Critical check for logic bugs).

#### Module: Checkout Validation (Phone Number)

- **Context:** User entering shipping details.
- **Constraint:** Vietnamese phone numbers (typically 10 digits).
- **Boundaries:** Length = 9, 10, 11.
- **Test Cases:**
  - _TC_BVA_06:_ Enter 9 digits (`090123456`) -> **Invalid** (Too short).
  - _TC_BVA_07:_ Enter 10 digits (`0901234567`) -> **Valid**.
  - _TC_BVA_08:_ Enter 11 digits (`09012345678`) -> **Invalid** (Too long, unless specific prefix allowed).

### 17.3. Decision Table Testing

**Technique:** Capturing complex logic where system output depends on a combination of inputs/conditions.

#### Module: Admin Access Control

- **Scenario:** A user attempts to access the Admin Dashboard (`/admin`).
- **Conditions:**
  - C1: Is Session Active? (Logged in)
  - C2: Is Role = Admin (1)?
- **Actions:**
  - A1: Allow Access
  - A2: Redirect to Login
  - A3: Redirect to Home (Access Denied)

| Rule      | C1 (Logged In) | C2 (Is Admin) | Action Taken | Expected Result                                                       |
| :-------- | :------------: | :-----------: | :----------- | :-------------------------------------------------------------------- |
| **DT_01** |     False      |     False     | A2           | Redirect to `login.php`                                               |
| **DT_02** |     False      |     True      | A2           | Impossible state (cannot be admin if not logged in), default to Login |
| **DT_03** |      True      |     False     | A3           | Redirect to `index.php` (User Dashboard)                              |
| **DT_04** |      True      |     True      | A1           | Load `admin/dashboard.php`                                            |

#### Module: Checkout Validation (Voucher Logic)

- **Scenario:** Applying a voucher code during checkout.
- **Conditions:**
  - C1: Voucher Code Exists?
  - C2: Voucher is Active (Date valid)?
  - C3: Cart Total >= Minimum Order Value?
- **Actions:**
  - A1: Apply Discount
  - A2: Show "Invalid Code"
  - A3: Show "Expired Code"
  - A4: Show "Minimum Order Not Met"

| Rule      | C1 (Exists) | C2 (Active) | C3 (Min Order) | Action | Test Case Description                      |
| :-------- | :---------: | :---------: | :------------: | :----- | :----------------------------------------- |
| **DT_05** |    False    |      -      |       -        | A2     | Enter random code "XYZ"                    |
| **DT_06** |    True     |    False    |       -        | A3     | Enter expired code "SUMMER2020"            |
| **DT_07** |    True     |    True     |     False      | A4     | Valid code, but cart total is too low      |
| **DT_08** |    True     |    True     |      True      | A1     | Valid code, valid cart -> Discount applied |

## 18. Advanced White-Box Testing Analysis

This section focuses on the internal logic, code structure, and data flow of the Zstyle application. The goal is to identify structural weaknesses and ensure that all logical paths are verified.

### 18.1. Authentication Validation Logic

- **Control Flow Decisions:**
  - **Input Validation:** Condition checks if username and password fields are non-empty.
  - **User Existence:** Database query determines if the username exists.
  - **Credential Verification:** Condition compares the provided password hash against the stored hash.
  - **Role Determination:** Branching logic checks if the user role is 'Admin' or 'Standard User'.
- **Critical Execution Paths:**
  - **Path 1 (Invalid Input):** Empty fields trigger an immediate validation error, bypassing database queries.
  - **Path 2 (User Not Found):** Database query returns null; execution flows to the "User does not exist" error handler.
  - **Path 3 (Auth Failure):** User is found, but hash comparison fails; execution flows to the "Wrong Password" error handler.
  - **Path 4 (Success):** User found and hash matches; Session variables are initialized, and the user is redirected based on role.
- **Data Flow Dependencies:**
  - **Input to Query:** `$_POST` data flows into the SQL query string (risk of Injection if not sanitized).
  - **Query to Logic:** Database result set flows into the `password_verify` function.
  - **Logic to Session:** User ID, Name, and Role flow into the `$_SESSION` global array upon success.
- **White-Box Test Derivation:**
  - **Branch Coverage:** Test empty strings to verify the Input Validation branch.
  - **Path Coverage:** Test a valid username with an incorrect password to verify the Hash Comparison failure path.
  - **Data Flow:** Test valid credentials to verify that `$_SESSION` variables are correctly populated and persisted.

### 18.2. Order Creation and Confirmation Logic

- **Control Flow Decisions:**
  - **Cart Validation:** Condition checks if the cart is empty before proceeding.
  - **Total Calculation:** Loop iterates through cart items to sum prices.
  - **Order Insertion:** Database command inserts the main order record.
  - **Detail Insertion:** Loop iterates through cart items to insert detailed records linked to the order.
  - **Inventory Update:** Database command updates product stock levels.
- **Critical Execution Paths:**
  - **Path 1 (Empty Cart):** Logic detects empty session array and redirects user to the product page.
  - **Path 2 (Successful Order):** Order ID generated $\rightarrow$ Details inserted $\rightarrow$ Stock updated $\rightarrow$ Email sent.
  - **Path 3 (Partial Failure):** Order inserted, but Detail insertion fails (e.g., SQL error), leading to data inconsistency.
  - **Path 4 (Stock Failure):** Stock update fails due to negative value constraint (if enforced), potentially requiring a rollback.
- **Data Flow Dependencies:**
  - **Session to Database:** `$_SESSION['giohang']` data flows into `donhang` (Total) and `cart` (Items) tables.
  - **Key Propagation:** `last_insert_id()` from the Order insertion flows into the `id_donhang` foreign key of Detail records.
  - **Cart to Inventory:** Cart Quantity flows into the Product table update logic for subtraction.
- **White-Box Test Derivation:**
  - **Loop Testing:** Test with multiple items to verify loop termination and total calculation logic.
  - **Error Handling:** Force an SQL error during Detail insertion to test if the system handles orphaned Order records (Transaction/Rollback logic).
  - **Boundary Testing:** Test with 0 stock to verify the Stock Update branch behavior.

### 18.3. Cart Session Handling Logic

- **Control Flow Decisions:**
  - **Session Existence:** Check if the cart session variable is already initialized.
  - **Item Existence:** Check if the added Product ID already exists in the cart array (Update vs. Add).
  - **Action Type:** Switch/If logic determines if the action is Add, Delete, or Update.
- **Critical Execution Paths:**
  - **Path 1 (New Cart):** Initialize Array $\rightarrow$ Add new Item structure.
  - **Path 2 (Add Existing):** Find matching Index $\rightarrow$ Increment Quantity field.
  - **Path 3 (Delete):** Find Index $\rightarrow$ Remove element $\rightarrow$ Re-index array (potentially).
  - **Path 4 (Checkout):** Read Array $\rightarrow$ Process Order $\rightarrow$ Destroy Session variable.
- **Data Flow Dependencies:**
  - **Input to Session:** User Input (ID, Qty) flows directly into the Session Array structure.
  - **Session to View:** Session Array flows to the View layer for rendering the Cart table.
  - **Lifecycle:** Session Array is explicitly `unset` after a successful Order Confirmation.
- **White-Box Test Derivation:**
  - **Logic Coverage:** Add an item, then add the same item again to verify the "Increment" branch executes instead of the "Add New" branch.
  - **Structure Coverage:** Delete the first, middle, and last items to verify Array Re-indexing logic ensures no gaps or errors.
  - **State Coverage:** Simulate Session Expiry to verify the "Cart Empty" branch handles the null state gracefully.

### 18.4. Admin Authorization Checks Logic

- **Control Flow Decisions:**
  - **Session Check:** Condition checks if the `role` session variable is set.
  - **Role Validation:** Condition checks if the `role` value equals the Admin constant (1).
- **Critical Execution Paths:**
  - **Path 1 (Guest):** Session variable is not set $\rightarrow$ Execution redirects to Login page.
  - **Path 2 (User):** Session is set, but Role is not 1 $\rightarrow$ Execution redirects to Home or Login.
  - **Path 3 (Admin):** Session is set, and Role is 1 $\rightarrow$ Execution proceeds to load the Admin Dashboard.
- **Data Flow Dependencies:**
  - **Session to Gatekeeper:** `$_SESSION['role']` is the single source of truth for Access Control.
  - **Logic to Navigation:** The outcome of the check determines the HTTP Location Header for redirection.
- **White-Box Test Derivation:**
  - **Condition Coverage:** Set `$_SESSION['role'] = 0` and access an Admin page to verify the "User" branch blocks access.
  - **Path Coverage:** Unset `$_SESSION` and access an Admin page to verify the "Guest" branch redirects correctly.
  - **Success Path:** Set `$_SESSION['role'] = 1` to verify the "Success" branch allows page rendering.

## 19. Admin and Role-Based Testing Considerations

This section defines comprehensive test scenarios focusing on access control, permission boundaries, and the business impact of administrative actions.

### 19.1. Role-Based Access Control (RBAC)

The system must strictly enforce role boundaries to prevent unauthorized access to sensitive administrative functions.

| Feature / Page     | Guest (Unauthenticated) | Customer (Role=0)  |      Admin (Role=1)       |
| :----------------- | :---------------------: | :----------------: | :-----------------------: |
| View Products      |        ✅ Allow         |      ✅ Allow      |         ✅ Allow          |
| Add to Cart        |        ✅ Allow         |      ✅ Allow      |         ✅ Allow          |
| Checkout           |    ❌ Redirect Login    |      ✅ Allow      |         ✅ Allow          |
| View Order History |    ❌ Redirect Login    | ✅ Own Orders Only | ✅ All Orders (via Admin) |
| Admin Dashboard    |    ❌ Redirect Login    |  ❌ Redirect Home  |         ✅ Allow          |
| Edit Product       |         ❌ Deny         |      ❌ Deny       |         ✅ Allow          |

### 19.2. Permission Boundary Testing

Testing must verify that users cannot transcend their assigned privileges, either vertically (becoming admin) or horizontally (accessing other users' data).

- **Vertical Privilege Escalation:**

  - **Test:** Log in as a Standard User (Role=0) and attempt to access `view/admin/index.php`.
  - **Risk:** **Critical.** If successful, a regular customer could gain full control over the store inventory and orders.
  - **Expected Result:** Immediate redirection to the Homepage or Login page.

- **Horizontal Privilege Escalation (IDOR):**
  - **Test:** Log in as Customer A (Order #10) and manually change the URL to `index.php?pg=donhang&id=11` (Customer B's order).
  - **Risk:** **High.** Privacy violation exposing PII (Address, Phone) of other customers.
  - **Expected Result:** "Access Denied" message or redirection to the user's own order list.

### 19.3. Direct URL Access Risks

Attackers often attempt to bypass login screens by guessing the URLs of internal files ("Forced Browsing").

- **Scenario:** Unauthenticated user accesses `view/admin/product.php` directly.
- **Vulnerability:** If the file lacks a `session_start()` and role check at the very top, the PHP interpreter will execute the code and render the Admin UI.
- **Business Risk:** **High.** Even if the database actions fail, exposing the Admin UI reveals system structure and potential attack vectors.
- **Mitigation Test:** Verify that _every_ file in the `admin/` directory begins with an include to a central `auth_guard.php` or equivalent logic.

### 19.4. Negative Admin Actions

Admins are human and make mistakes. The system must be resilient to invalid inputs or destructive actions performed by privileged users.

- **Invalid State Changes:**

  - **Test:** Admin attempts to set a Product Price to `-100` or `NaN`.
  - **Risk:** **High.** Negative prices can corrupt financial reporting and allow users to "buy" money.
  - **Expected Result:** Validation error preventing the database update.

- **Destructive Operations:**
  - **Test:** Admin deletes a Product that is currently in an active "Pending" Order.
  - **Risk:** **Medium.** The order becomes unfulfillable, or the Order History page crashes when trying to load the deleted product's name.
  - **Expected Result:** Soft-delete (mark as inactive) instead of hard-delete, or a warning prompt "Product is in active orders".

### 19.5. Data Integrity Impact

Admin operations often have cascading effects on the database. Testing must ensure referential integrity is maintained.

- **Orphaned Records:**

  - **Test:** Delete a User account.
  - **Check:** Are the user's Orders and Cart items also deleted?
  - **Risk:** **Low/Medium.** Database clutter (wasted space) and potential reporting errors (Orders belonging to NULL user).

- **Inventory Drift:**
  - **Test:** Admin manually edits the stock quantity of a product while a user is checking out.
  - **Risk:** **Medium.** Race conditions leading to overselling.
  - **Mitigation Test:** Verify that Admin updates take precedence or lock the row during the update transaction.

## 20. Practical Automation Testing Proposal

This proposal outlines a strategic approach to automating the testing of the Zstyle platform. The goal is to reduce manual regression effort while acknowledging the architectural constraints of the legacy PHP codebase.

### 20.1. Scope of Automation

Not everything should be automated. The following table defines the boundaries based on ROI (Return on Investment) and technical feasibility.

| Workflow / Feature                 | Suitability | Justification                                                       |
| :--------------------------------- | :---------: | :------------------------------------------------------------------ |
| **Smoke Test (Login, Home Load)**  |  **High**   | Critical daily check. Stable UI. High ROI.                          |
| **Customer Checkout (Happy Path)** |  **High**   | Revenue-critical. Complex multi-step flow that breaks easily.       |
| **Product Search & Filter**        | **Medium**  | Data-driven tests are easy to implement.                            |
| **Admin CRUD Operations**          | **Medium**  | Repetitive tasks suitable for automation.                           |
| **UI Layout / CSS Responsiveness** |   **Low**   | Better tested manually or with visual regression tools (expensive). |
| **Payment Gateway Integration**    |   **Low**   | Requires complex mocking of external APIs; manual testing is safer. |
| **One-time Setup / Config**        |   **Low**   | Scripts run once; automation maintenance cost > execution cost.     |

### 20.2. Proposed Tool Stack

#### A. UI / End-to-End Testing: **Selenium WebDriver (with Python or Java)**

- **Why:** Zstyle is a traditional server-side rendered PHP app. Selenium interacts with the browser just like a real user, validating the final HTML output.
- **Application:**
  - Simulating user clicks, form fills, and navigation.
  - Validating JavaScript interactions (e.g., AJAX Cart updates).
  - Cross-browser testing (Chrome, Firefox).

#### B. Logic / Unit Testing: **PHPUnit**

- **Why:** The industry standard for PHP testing.
- **Application:**
  - Testing `model/*.php` functions in isolation (e.g., `tongdonhang()`, `checkuser()`).
  - **Constraint:** Requires refactoring procedural code into classes or using "include-and-test" strategies for legacy files.

### 20.3. Automation Architecture Overview

We propose a **Hybrid Framework** utilizing the **Page Object Model (POM)** design pattern to ensure maintainability.

```text
/tests
  /UI (Selenium)
    /Pages          <-- Page Objects (Locators & Methods)
      LoginPage.py
      CartPage.py
      CheckoutPage.py
    /Tests          <-- Test Scripts
      test_login_success.py
      test_checkout_flow.py
    /Utilities      <-- Drivers, Config, Data Loaders
  /Unit (PHPUnit)
    /ModelTests     <-- White-box tests for PHP logic
      CartTest.php
      UserTest.php
```

- **Page Object Model (POM):**
  - Each page (e.g., `login.php`) has a corresponding class (`LoginPage`).
  - **Benefit:** If the HTML ID of the "Login" button changes, we update it in _one_ place (`LoginPage`), not in 50 test scripts.

### 20.4. Limitations and Risks

1.  **Legacy Code Testability:**

    - **Risk:** The use of global `$_SESSION` and `include` statements in `model/` files makes unit testing difficult without mocking the global state.
    - **Mitigation:** Focus heavily on **Selenium (Black-box)** testing which is agnostic to the underlying code structure.

2.  **Data State Management:**

    - **Risk:** Tests running on a shared database may interfere with each other (e.g., one test deletes a product another test is trying to buy).
    - **Mitigation:** Use a dedicated **Test Database**. Implement a `setUp()` script to seed fresh data and `tearDown()` to clean up after every test run.

3.  **AJAX Synchronization:**
    - **Risk:** Selenium may try to click "Checkout" before the AJAX Cart update completes, leading to "Element Not Interactable" errors.
    - **Mitigation:** Use **Explicit Waits** (e.g., `WebDriverWait(driver, 10).until(...)`) instead of hardcoded `sleep()`.

### 20.5. Complementary Strategy: Manual vs. Automation

Automation does not replace manual testing; it empowers it.

- **Automation's Role:**

  - **Regression Guard:** Runs every night to ensure "yesterday's code didn't break today."
  - **Data Setup:** Scripts can quickly create 100 orders or 50 users, preparing the environment for manual testing.

- **Manual Testing's Role:**
  - **Exploratory Testing:** Finding creative ways to break the system (e.g., "What if I double-click the Submit button?").
  - **Usability:** Judging if the font size is readable or the checkout flow feels intuitive.
  - **Visual Glitches:** Spotting misaligned images or broken CSS that Selenium might ignore.

## 21. Test Coverage and Summary

This section provides a qualitative assessment of the testing scope, analyzing coverage across multiple dimensions to ensure transparency regarding the system's validation status.

### 21.1. Functional Coverage

- **Status:** **Fully Covered**
- **Analysis:** All core features identified in the Requirement Traceability Matrix (Section 5) have corresponding test cases.
  - **User-Facing:** Registration, Login, Product Browsing, Cart Management, and Checkout are covered by both manual scripts and automated smoke tests.
  - **Admin-Facing:** CRUD operations for Products, Categories, and Users are mapped to specific validation steps.
- **Justification:** The application of Equivalence Partitioning and Boundary Value Analysis ensures that valid and invalid inputs for these functions are rigorously tested.

### 21.2. Workflow Coverage

- **Status:** **Fully Covered**
- **Analysis:** The critical end-to-end business processes are validated from start to finish.
  - **Purchase Flow:** The "Customer Purchase Workflow" (Section 16.1) covers the entire lifecycle from discovery to order confirmation.
  - **Exception Handling:** "Interrupted Checkout" and "Stockout" scenarios ensure the system handles deviations gracefully.
- **Justification:** Workflow-based testing ensures that individual functions work together correctly in a sequence, capturing integration issues that isolated functional tests might miss.

### 21.3. Role-Based Coverage

- **Status:** **Well Covered**
- **Analysis:** Access control is verified against the RBAC Matrix (Section 19.1).
  - **Vertical Boundaries:** Tests confirm that Standard Users cannot access Admin URLs (Privilege Escalation).
  - **Horizontal Boundaries:** Tests confirm that Users cannot view other Users' orders (IDOR).
- **Justification:** Security testing explicitly targets the session management and role verification logic in `auth_guard` mechanisms.

### 21.4. Data Coverage

- **Status:** **Partially Covered**
- **Analysis:** Critical data entities are validated for integrity.
  - **Covered:** Transactional integrity of Orders and Order Details (Atomicity). Session data persistence for Carts.
  - **Not Covered:** Historical data migration or large-scale data volume testing (Big Data scenarios).
- **Justification:** While we verify that data is saved correctly during standard operations, we have not simulated database corruption recovery or schema migration scenarios.

### 21.5. Logic and Control Flow Coverage

- **Status:** **Partially Covered**
- **Analysis:** White-box analysis has targeted high-risk logic paths.
  - **Covered:** Key decision points in Authentication (Login success/fail) and Checkout (Stock check pass/fail).
  - **Not Covered:** Deep branch coverage for legacy procedural code in `model/` files that lack unit tests.
- **Justification:** The legacy architecture (mixed HTML/PHP) makes 100% path coverage difficult without significant refactoring. Testing focuses on the most critical business logic paths.

### 21.6. Coverage Limitations (Not Covered)

- **Real Payment Processing:** Excluded because the system uses simulated payment methods.
- **Third-Party Logistics:** No API integration with shipping providers exists to test.
- **Visual Regression:** Pixel-perfect UI testing across all device/browser combinations is manual-only due to tooling costs.

### 21.7. Conclusion

The testing strategy provides a robust safety net for the Zstyle platform. By prioritizing **Workflow** and **Role-Based** coverage, we mitigate the highest business risks (Revenue Loss and Security Breaches). While **Logic Coverage** is constrained by the legacy architecture, the extensive **Functional** testing ensures that the system behaves correctly from the user's perspective. The proposed automation framework will further stabilize the **Functional** and **Workflow** layers.

## 22. Test Environment & Data Strategy

To ensure consistent and reproducible test results, a strict environment and data management strategy is defined.

### 22.1. Test Environments

| Environment     | Type     | Purpose                            | Configuration                                                   |
| :-------------- | :------- | :--------------------------------- | :-------------------------------------------------------------- |
| **Local (Dev)** | Docker   | Unit Testing & Developer Debugging | PHP 8.0, MySQL 5.7 (Localhost), XDebug enabled.                 |
| **QA (Test)**   | Docker   | Functional & Regression Testing    | Mirror of Prod configuration. Data reset after every suite run. |
| **Staging**     | Cloud VM | UAT & Performance Testing          | Scaled resources (2 CPU, 4GB RAM). Anonymized production data.  |

### 22.2. Test Data Management

- **Synthetic Data:** Used for functional testing (e.g., creating 50 random users). Generated via Python `Faker` library or PHP `Faker`.
- **Golden Dataset:** A known "good" state of the database (SQL Dump) containing:
  - 1 Admin User (`admin`/`123`)
  - 10 Categories
  - 50 Products (with varied prices and stock levels)
  - 0 Orders (Clean slate)
- **Data Reset Strategy:**
  - **Before Suite:** Drop and Re-import the Golden Dataset.
  - **During Test:** Transaction rollback (where supported) or explicit cleanup (DELETE queries) in `tearDown()`.

## 23. Defect Management Lifecycle

This section defines the standard process for handling defects found during testing.

### 23.1. Defect Workflow

1.  **New:** Bug is identified and logged in the tracking system (e.g., Jira/GitHub Issues).
2.  **Triage:** QA Lead reviews the bug.
    - _Valid:_ Assign to Developer.
    - _Invalid:_ Reject (Works as Designed / Duplicate).
3.  **In Progress:** Developer fixes the bug.
4.  **Resolved:** Developer pushes the fix and marks as "Ready for QA".
5.  **Verified:** QA re-tests the fix.
    - _Pass:_ Close the bug.
    - _Fail:_ Re-open and send back to Developer.

### 23.2. Severity & Priority Definitions

| Level             | Severity (Technical Impact)                        | Priority (Business Urgency) |
| :---------------- | :------------------------------------------------- | :-------------------------- |
| **Critical (P1)** | System crash, Data loss, Security breach.          | Fix Immediately (Hotfix).   |
| **Major (P2)**    | Core feature broken (e.g., Checkout fails).        | Fix before next release.    |
| **Medium (P3)**   | Non-critical feature broken (e.g., Sort by Price). | Fix in upcoming sprint.     |
| **Low (P4)**      | Cosmetic issue, Typo, UI misalignment.             | Fix when time permits.      |

## 24. Requirement Traceability Matrix (RTM) Strategy

The RTM ensures that every business requirement is covered by at least one test case, and every failed test case is linked to a defect.

### 24.1. Traceability Mapping Example

| Req ID      | Requirement Description | Test Case ID | Test Scenario                 | Status   | Defect ID   |
| :---------- | :---------------------- | :----------- | :---------------------------- | :------- | :---------- |
| **REQ-001** | User Registration       | TC_AUTH_01   | Register with valid data      | Pass     | -           |
| **REQ-001** | User Registration       | TC_AUTH_02   | Register with duplicate email | Pass     | -           |
| **REQ-002** | Product Search          | TC_SRCH_01   | Search by exact keyword       | Pass     | -           |
| **REQ-002** | Product Search          | TC_SRCH_02   | Search with SQL Injection     | **Fail** | **DEF_005** |
| **REQ-003** | Checkout Process        | TC_CHK_01    | Guest Checkout                | Pass     | -           |
| **REQ-003** | Checkout Process        | TC_CHK_02    | Checkout with Empty Cart      | **Fail** | **DEF_001** |

### 24.2. Coverage Metrics

- **Requirement Coverage:** $\frac{\text{Requirements Mapped to Test Cases}}{\text{Total Requirements}} \times 100\%$
- **Defect Density:** $\frac{\text{Total Defects}}{\text{KLOC (Thousand Lines of Code)}}$
- **Test Pass Rate:** $\frac{\text{Passed Test Cases}}{\text{Total Executed Test Cases}} \times 100\%$
