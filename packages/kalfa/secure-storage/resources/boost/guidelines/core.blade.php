## kalfa/secure-storage

Secure key/value storage for NativePHP Mobile apps — backed by Keychain on iOS and EncryptedSharedPreferences on Android.

### Installation

```bash
composer require kalfa/secure-storage
```

### PHP Usage (Livewire / Blade)

The plugin exposes its bridge via `Native\Mobile\Facades\SecureStorage`. The `Kalfa\SecureStorage\Facades\SecureStorage` facade delegates to the same binding.

@verbatim
<code-snippet name="SecureStorage — PHP facade" lang="php">
use Kalfa\SecureStorage\Facades\SecureStorage;

// Store a value
SecureStorage::set('auth_token', 'abc123');

// Retrieve a value (returns '' when the key does not exist)
$token = SecureStorage::get('auth_token');

// Delete a value (idempotent)
SecureStorage::delete('auth_token');
</code-snippet>
@endverbatim

### Available Methods

| Method | Description |
|---|---|
| `SecureStorage::get(string $key): string` | Return the stored value, or `''` when the key does not exist |
| `SecureStorage::set(string $key, ?string $value): bool` | Store a value; passing `null` deletes the entry |
| `SecureStorage::delete(string $key): bool` | Delete a value (idempotent) |

### Events

`Kalfa\SecureStorage\Events\SecureStorageCompleted` is dispatched after a bridge call completes.

@verbatim
<code-snippet name="SecureStorage — listening for events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Kalfa\SecureStorage\Events\SecureStorageCompleted;

#[OnNative(SecureStorageCompleted::class)]
public function handleCompleted(string $result, ?string $id = null): void
{
    // Handle the completed event
}
</code-snippet>
@endverbatim

### JavaScript Usage (Vue / React / Inertia)

@verbatim
<code-snippet name="SecureStorage — JavaScript" lang="javascript">
import { get, set, del } from '@kalfa/secure-storage/secureStorage';

// Store a value
await set('auth_token', 'abc123');

// Retrieve a value ({ value: '' } when the key does not exist)
const { value } = await get('auth_token');

// Delete a value (idempotent)
await del('auth_token');
</code-snippet>
@endverbatim
