# Kalfa\SecureStorage Plugin for NativePHP Mobile

A NativePHP Mobile plugin Secure storage bridge plugin for NativePHP Mobile

## Installation

```bash
composer require kalfa/secure-storage
```

## Usage

```php
use Kalfa\SecureStorage\Facades\Kalfa\SecureStorage;

// Execute functionality
$result = Kalfa\SecureStorage::execute(['option1' => 'value']);

// Get status
$status = Kalfa\SecureStorage::getStatus();
```

## Listening for Events

```php
use Livewire\Attributes\On;

#[On('native:Kalfa\SecureStorage\Events\Kalfa\SecureStorageCompleted')]
public function handleKalfa\SecureStorageCompleted($result, $id = null)
{
    // Handle the event
}
```

## License

MIT