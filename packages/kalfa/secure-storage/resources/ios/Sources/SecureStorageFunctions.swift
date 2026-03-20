import Foundation
import Security

private let keychainService: String = Bundle.main.bundleIdentifier ?? "me.kalfa.rsvp"

enum SecureStorageFunctions {

    // MARK: - Get

    class Get: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let key = parameters["key"] as? String, !key.isEmpty else {
                return BridgeResponse.error(code: "INVALID_KEY", message: "key parameter is required and must be a non-empty string")
            }

            let query: [String: Any] = [
                kSecClass as String:       kSecClassGenericPassword,
                kSecAttrService as String: keychainService,
                kSecAttrAccount as String: key,
                kSecReturnData as String:  true,
                kSecMatchLimit as String:  kSecMatchLimitOne,
            ]

            var item: CFTypeRef?
            let status = SecItemCopyMatching(query as CFDictionary, &item)

            if status == errSecSuccess,
               let data = item as? Data,
               let value = String(data: data, encoding: .utf8) {
                return BridgeResponse.success(data: ["value": value])
            }

            // Not found or any other error — return empty string so PHP treats it as nil
            return BridgeResponse.success(data: ["value": ""])
        }
    }

    // MARK: - Set

    class Set: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let key = parameters["key"] as? String, !key.isEmpty else {
                return BridgeResponse.error(code: "INVALID_KEY", message: "key parameter is required and must be a non-empty string")
            }

            // If value is null or missing, treat as delete
            guard let value = parameters["value"] as? String else {
                let deleteQuery: [String: Any] = [
                    kSecClass as String:       kSecClassGenericPassword,
                    kSecAttrService as String: keychainService,
                    kSecAttrAccount as String: key,
                ]
                SecItemDelete(deleteQuery as CFDictionary)
                return BridgeResponse.success(data: ["success": true])
            }

            guard let data = value.data(using: .utf8) else {
                return BridgeResponse.error(code: "ENCODING_ERROR", message: "Could not encode value as UTF-8")
            }

            // Always delete first to handle update case cleanly
            let deleteQuery: [String: Any] = [
                kSecClass as String:       kSecClassGenericPassword,
                kSecAttrService as String: keychainService,
                kSecAttrAccount as String: key,
            ]
            SecItemDelete(deleteQuery as CFDictionary)

            let addQuery: [String: Any] = [
                kSecClass as String:          kSecClassGenericPassword,
                kSecAttrService as String:    keychainService,
                kSecAttrAccount as String:    key,
                kSecValueData as String:      data,
                kSecAttrAccessible as String: kSecAttrAccessibleWhenUnlockedThisDeviceOnly,
            ]

            let status = SecItemAdd(addQuery as CFDictionary, nil)
            let success = (status == errSecSuccess)
            return BridgeResponse.success(data: ["success": success])
        }
    }

    // MARK: - Delete

    class Delete: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let key = parameters["key"] as? String, !key.isEmpty else {
                return BridgeResponse.error(code: "INVALID_KEY", message: "key parameter is required and must be a non-empty string")
            }

            let query: [String: Any] = [
                kSecClass as String:       kSecClassGenericPassword,
                kSecAttrService as String: keychainService,
                kSecAttrAccount as String: key,
            ]

            let status = SecItemDelete(query as CFDictionary)
            // errSecItemNotFound is still a successful delete (idempotent)
            let success = (status == errSecSuccess || status == errSecItemNotFound)
            return BridgeResponse.success(data: ["success": success])
        }
    }
}
