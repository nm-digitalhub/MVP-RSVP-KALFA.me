package com.kalfa.plugins.secure_storage

import android.content.Context
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse

private const val PREFS_FILE = "kalfa_secure_storage"

private fun getPreferences(context: Context) = EncryptedSharedPreferences.create(
    context,
    PREFS_FILE,
    MasterKey.Builder(context)
        .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
        .build(),
    EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
    EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
)

object SecureStorageFunctions {

    class Get(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val key = parameters["key"] as? String
                ?: return BridgeResponse.error("Missing required parameter: key")

            val value = getPreferences(context).getString(key, null)

            return BridgeResponse.success(mapOf("value" to (value ?: "")))
        }
    }

    class Set(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val key = parameters["key"] as? String
                ?: return BridgeResponse.error("Missing required parameter: key")

            val value = parameters["value"] as? String

            val prefs = getPreferences(context)

            // Null value is treated as delete (mirrors iOS behaviour)
            if (value == null) {
                prefs.edit().remove(key).apply()
                return BridgeResponse.success(mapOf("success" to true))
            }

            prefs.edit().putString(key, value).apply()

            return BridgeResponse.success(mapOf("success" to true))
        }
    }

    class Delete(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val key = parameters["key"] as? String
                ?: return BridgeResponse.error("Missing required parameter: key")

            // Idempotent — removing a non-existent key is not an error
            getPreferences(context).edit().remove(key).apply()

            return BridgeResponse.success(mapOf("success" to true))
        }
    }
}
