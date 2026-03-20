/**
 * SecureStorage plugin for NativePHP Mobile
 *
 * Bridges Get/Set/Delete operations to the native Keychain (iOS) and
 * EncryptedSharedPreferences (Android) implementations via the NativePHP
 * bridge API.
 *
 * @example
 * import { get, set, del } from '@kalfa/secure-storage/secureStorage';
 *
 * await set('auth_token', 'abc123');
 * const { value } = await get('auth_token');
 * await del('auth_token');
 */

const BASE_URL = '/_native/api/call';

/**
 * Internal helper — POST a method call to the NativePHP bridge.
 *
 * @param {string} method  Bridge method name, e.g. "SecureStorage.Get"
 * @param {Object} params  Parameters forwarded to the native handler
 * @returns {Promise<Object>}
 */
async function bridgeCall(method, params = {}) {
    const response = await fetch(BASE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ method, params }),
    });

    return response.json();
}

/**
 * Retrieve a value from secure storage.
 *
 * @param {string} key  Storage key
 * @returns {Promise<{value: string}>}  `value` is an empty string when the key does not exist
 */
export async function get(key) {
    return bridgeCall('SecureStorage.Get', { key });
}

/**
 * Store a value in secure storage.
 * Passing `null` as the value deletes the entry (mirrors iOS behaviour).
 *
 * @param {string}      key    Storage key
 * @param {string|null} value  Value to store, or null to delete
 * @returns {Promise<{success: boolean}>}
 */
export async function set(key, value) {
    return bridgeCall('SecureStorage.Set', { key, value });
}

/**
 * Delete a value from secure storage.
 * Idempotent — deleting a non-existent key is not an error.
 *
 * @param {string} key  Storage key
 * @returns {Promise<{success: boolean}>}
 */
export async function del(key) {
    return bridgeCall('SecureStorage.Delete', { key });
}

export default { get, set, del };
