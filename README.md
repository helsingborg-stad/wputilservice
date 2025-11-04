# WpUtilService

`WpUtilService` is a lightweight, developer-friendly service layer for WordPress that wraps `WpService`. It provides a **clear, controlled interface** to common WordPress operations such as enqueuing scripts, adding translations, and more — all while keeping the main service clean and testable.

---

## Why This Design?

- **Single Public Entrypoint per Feature:** Each feature (like enqueueing scripts or translations) is encapsulated in a **trait**. Traits expose exactly **one public method**, preventing API pollution.
- **Feature Managers:** Each public method returns a **manager object** that handles the feature's operations (e.g., `EnqueueManager` for scripts). Managers can contain multiple private/protected methods, helpers, or additional classes (like `CacheBuster`) without exposing internal logic.
- **Fluent API:** Methods on the manager can be **chained**, allowing concise and readable code.
- **Separation of Concerns:** The main service (`WpUtilService`) remains simple, while complex logic is handled by feature-specific classes.
- **Testable and Extensible:** The service depends on `WpService`, which can be swapped for a mock or custom implementation in tests. Additional helpers (like cache-busting, minifiers, etc.) can be injected into managers.

---

## Installation

Add `WpUtilService` to your project using PSR-4 autoloading. Example composer setup:

```json
"autoload": {
    "psr-4": {
        "WpUtilService\\\\": "src/"
    }
}
```

---

## Usage

### Basic Setup

```php
use WpService\\NativeWpService;
use WpUtilService\\WpUtilService;

$wpService = new NativeWpService();
$wpUtilService = new WpUtilService($wpService);
```

---

### Enqueue Scripts and Add Translations
- `enqueue()` returns an `EnqueueManager`.
- `add()` enqueues a script.
- `with()` may be chained with data or translation functions. 
- `and()` is a synonym to `with()` but cannot be called before `with()`.
- You can chain multiple add calls fluently. There are no need to call multiple enqueue. 
- Enqueue implements the singleton pattern. This means that when you call enqueue() multiple times in succession, it will reuse the previously stored configuration instead of creating a new instance. 

#### Example 1
```php
$wpUtilService
    ->enqueue(__DIR__)
    ->add('main.js', ['jquery'])
    ->with()->translation(
        'objectName',
        ['localization_a' => __('Test', 'testdomain')]
    )->and()->data(
        'objectName', 
        ['id' => 1]
    );
```

#### Example 2 (alternative sytax)
```php
$wpUtilService
    ->enqueue(__DIR__)
    ->add('main.js', ['jquery'])
    ->with('translation', 'objectName', ['localization_a' => __('Test', 'testdomain')])
    ->and('data', 'objectName', ['id' => 1]);
```
---

#### Example 3 (alternative sytax)
```php
$wpUtilService
    ->enqueue(__DIR__)
    ->add('main.js', ['jquery'])
    ->with('translation', 'objectName', ['localization_a' => __('Test', 'testdomain')])
    ->with('data', 'objectName', ['id' => 1]);
```
---

#### Example 4 (chaining)
```php
$wpUtilService
    ->enqueue(__DIR__)
    ->add('main.js', ['jquery'])
    ->with('data', 'objectName', ['id' => 1]);
    ->add('main.css')
    ->add('styleguide.css');
```
---

#### Example 5
```php
$enqueue = $wpUtilService->enqueue(__DIR__); 
$enqueue->add('main.js', ['jquery']); 
$enqueue->add('main.css'); 
```
---

### Adding New Features

To add a new feature:

1. **Create a trait** with a single public method representing the entrypoint.
2. **Create a manager class** for that feature with all operations and private helpers.
3. **Use the trait** in `WpUtilService`.
4. Consumers interact only through the public entrypoint and manager API.

---

### Extending Managers with Helpers

Managers can leverage additional helper classes. For example, `EnqueueManager` uses the `CacheBustManager` helper:

```php
$cacheBustManager = new CacheBustManager();

```

The enqueue manager keeps this **internal**, so the main service API remains clean. Helpers may reside the features folder, but should not have any publicly avabile api:s.

---

## Diagram of Service Structure

```mermaid
flowchart TD
    A[WpUtilService] --> B[Enqueue Trait]
    A --> C[Translation Trait]
    B --> D[EnqueueManager]
    C --> E[TranslationManager]
    D --> F[CacheBuster Helper]
    D --> G[Other internal private helpers]
    E --> H[Internal private helpers]
    D --> I[WpService]
    E --> I
```

**Explanation:**

- `WpUtilService` acts as the **facade**.  
- Each trait contributes **one public entrypoint** (`enqueue()`, `translation()`).  
- Each public method returns a **manager** object, which handles the feature and contains private helpers.  
- Managers can call **helpers** like `CacheBuster` for extra functionality.  
- Ultimately, the manager delegates to `WpService` to perform the actual WordPress operations.  

---

### Advantages

- Clear, controlled interface.
- One public method per trait; easy to discover.
- Supports **fluent API** and chaining.
- Testable: swap `WpService` for mocks.
- Extensible: add helpers like cache-busting, minification, etc., without changing the service.

---

## Folder Structure

```
src/
├─ WpUtilService.php          # Main service (facade)
├─ Traits/
│  ├─ Enqueue.php             # Trait for enqueue feature
│  ├─ Translation.php         # Trait for translation feature
├─ Features/
│  ├─ EnqueueManager.php      # Manager class for enqueue
│  ├─ TranslationManager.php  # Manager class for translation
│  ├─ CacheBuster.php         # Optional helper
├─ Contracts/
   ├─ Enqueue.php             # Interface
   ├─ Translation.php         # Interface
```