import Alpine from 'alpinejs'
import { SecureStorage } from '#nativephp'

window.Alpine = Alpine
window.NativePHPMobile = { secureStorage: SecureStorage }

Alpine.start()
