# NativePHP Mobile - Pre-Deployment Check Report
**Generated:** 2025-03-22
**Environment:** Linux (Ubuntu 24.04)

---

## Executive Summary

| Component | Status | Notes |
|-----------|--------|-------|
| **NativePHP Package** | ✅ Installed | v3.0.4 |
| **Configuration** | ✅ Complete | .env variables set |
| **iOS Credentials** | ✅ Present | distribution.p12, profile.mobileprovision |
| **iOS Build Capability** | ❌ Not Available | Requires macOS |
| **Android Build Capability** | ⚠️ Partial | Java 21 OK, SDK missing |
| **Mobile Routes** | ✅ Configured | 7 routes active |
| **Mobile UI** | ✅ Exists | `/mobile` shell with Alpine.js |

---

## 1. Package & Installation

### NativePHP Mobile
```bash
composer show nativephp/mobile
# versions : * 3.0.4
# released : 2026-02-17, 1 month ago
```

**Status:** ✅ Latest stable version

### Available Artisan Commands
```
✅ native:build           - Build the mobile app bundle
✅ native:check-build-number - Check app store build numbers
✅ native:credentials     - Generate platform credentials
✅ native:emulator        - List/launch emulators
✅ native:install         - Install NativePHP resources
✅ native:jump            - Development server (hot reload)
✅ native:open            - Open in Android Studio/Xcode
✅ native:package         - Package signed apps
✅ native:run             - Build + package + run
✅ native:tail            - Tail logs from Android app
✅ native:version         - Display package version
✅ native:watch           - Watch for file changes
```

---

## 2. Configuration Analysis

### Environment Variables (.env)

| Variable | Set | Value | Notes |
|----------|-----|-------|-------|
| `NATIVEPHP_APP_ID` | ✅ | `me.kalfa.eventrsvp` | Reverse domain format ✅ |
| `NATIVEPHP_DEEPLINK_SCHEME` | ✅ | `kalfa` | For kalfa:// URLs ✅ |
| `NATIVEPHP_DEEPLINK_HOST` | ✅ | `kalfa.me` | HTTPS deep linking ✅ |
| `NATIVEPHP_START_URL` | ✅ | `/mobile` | Entry point ✅ |
| `NATIVEPHP_APP_VERSION` | ❌ | Not set | Default: "DEBUG" |
| `NATIVEPHP_APP_VERSION_CODE` | ❌ | Not set | Default: 1 |

**Missing Required Variables:**
- `NATIVEPHP_APP_VERSION` - Should be "1.0.0" for production
- `NATIVEPHP_APP_VERSION_CODE` - Integer for Play Store (1, 2, 3...)
- `NATIVEPHP_DEVELOPMENT_TEAM` - Apple Team ID (10 chars, e.g., "ABC1234567")

### iOS Credentials (`/credentials/`)

```
✅ distribution.p12 (3.1 KB) - Distribution certificate
✅ distribution.cer (1.5 KB) - Certificate file
✅ profile.mobileprovision (12 KB) - Provisioning profile
✅ AuthKey_6X5674BGPC.p8 (257 B) - App Store Connect API key
✅ ios-private-key.key (1.7 KB) - Private key
✅ ios-certificate-request.csr (1 KB) - CSR file
```

**Status:** ✅ All iOS credentials present and valid

**Certificate Details:**
- Type: Distribution (for App Store)
- Password: Set in .env (`IOS_DISTRIBUTION_CERTIFICATE_PASSWORD`)
- Provisioning: Includes devices/capabilities

---

## 3. Platform Build Capability

### iOS Builds

**Requirement:** macOS with Xcode 14+

**Current Environment:** Linux Ubuntu 24.04

**Status:** ❌ **Cannot build iOS on Linux**

**Options:**
1. **MacStadium / macOS CI** - Rent macOS in cloud (~$1-2/hour)
2. **GitHub Actions macos-latest** - Free for public repos
3. **Transfer to Mac** - Manual build on Mac machine
4. **Cross-compile (not recommended)** - Experimental, unstable

### Android Builds

**Requirements:**
- Java JDK 17+
- Android SDK (Platform, Build Tools)
- Gradle

**Current Environment:**

| Requirement | Status | Version |
|-------------|--------|---------|
| Java | ✅ Installed | OpenJDK 21.0.10 |
| Android SDK | ❌ Not Found | Need installation |
| Gradle | ❌ Not Found | Bundled with NativePHP |

**Status:** ⚠️ **Requires Android SDK setup**

---

## 4. Mobile Routes & API

### Registered Routes

```
POST    api/mobile/auth/login           → Api\MobileAuthController@login
POST    api/mobile/auth/logout          → Api\MobileAuthController@logout
POST    api/mobile/auth/logout/others   → Api\MobileAuthController@logoutOthers
GET|HEAD  mobile                        → mobile.entry (shell)
GET|HEAD  mobile/session                 → Mobile\MobileSecureStorageController@status
PUT      mobile/session                 → Mobile\MobileSecureStorageController@store
DELETE   mobile/session                 → Mobile\MobileSecureStorageController@destroy
```

**Status:** ✅ 7 routes active

### Mobile API Config (`config/mobile.php`)

```php
'api' => [
    'base_url' => 'https://kalfa.me',
    'endpoints' => [
        'login' => '/api/mobile/auth/login',
        'logout' => '/api/mobile/auth/logout',
        'logout_others' => '/api/mobile/auth/logout/others',
        'bootstrap' => '/api/bootstrap',
    ],
],
```

**Status:** ✅ Configured for production API

---

## 5. Mobile UI Structure

### Views

```
resources/views/mobile/
└── shell.blade.php  (49 KB) - Main mobile shell with Alpine.js state machine
```

**Shell States:**
- `unauthenticated` - Login screen with credential flow
- `authenticated` - Main app interface
- `loading` - Transition state
- `error` - Error display

**Features:**
- ✅ Remote authentication (login to Laravel API)
- ✅ Secure storage (access token in KeyStore/Keychain)
- ✅ Session management (status/store/destroy)
- ✅ Organization switching
- ✅ Bootstrap payload caching
- ✅ Read-only cache mode

**Status:** ✅ Mobile UI implemented

---

## 6. iOS Build Requirements (for macOS)

When on macOS, verify:

```bash
# Xcode version
xcodebuild -version

# Installed simulators
xcrun simctl list devices

# CocoaPods (if using)
pod --version

# Fastlane (for automation)
fastlane --version
```

**Required:**
- Xcode 14+ / 15+
- iOS Simulator 16+
- CocoaPods 1.12+
- Ruby 3.0+ (for Fastlane)

---

## 7. Android Build Requirements

### Android SDK Setup

```bash
# Install command line tools
wget https://dl.google.com/android/repository/commandlinetools-linux-9477386_latest.zip
unzip commandlinetools-linux-*.zip -d $HOME/Android/cmdline-tools

# Set environment
export ANDROID_SDK_ROOT=$HOME/Android
export PATH=$PATH:$ANDROID_SDK_ROOT/cmdline-tools/latest/bin:$ANDROID_SDK_ROOT/platform-tools

# Accept licenses
yes | sdkmanager --licenses

# Install required packages
sdkmanager "platform-tools" "platforms;android-34" "build-tools;34.0.0"
```

### Gradle Wrapper

NativePHP bundles Gradle, but verify:

```bash
# Check if Gradle wrapper exists
ls -la vendor/nativephp/mobile/android/gradlew

# Make executable
chmod +x vendor/nativephp/mobile/android/gradlew
```

---

## 8. Build Verification Checklist

### Pre-Build

- [ ] Update `NATIVEPHP_APP_VERSION` to "1.0.0"
- [ ] Update `NATIVEPHP_APP_VERSION_CODE` to 1 (or next integer)
- [ ] Set `NATIVEPHP_DEVELOPMENT_TEAM` to Apple Team ID
- [ ] Run `php artisan native:install --force`
- [ ] Verify mobile routes: `php artisan route:list --path=mobile`
- [ ] Test mobile shell locally: `php artisan native:jump`

### iOS Build (on macOS)

- [ ] Verify certificates: `php artisan native:credentials ios`
- [ ] Check provisioning profile expiration
- [ ] Build: `php artisan native:build ios`
- [ ] Package: `php artisan native:package ios`
- [ ] Test on simulator: `php artisan native:run ios`

### Android Build (on Linux/macOS)

- [ ] Install Android SDK
- [ ] Accept licenses
- [ ] Build: `php artisan native:build android`
- [ ] Package: `php artisan native:package android`
- [ ] Test on emulator: `php artisan native:emulator`

---

## 9. Production Deployment

### App Stores

**Apple App Store:**
- Bundle ID: `me.kalfa.eventrsvp`
- Team ID: Set via `NATIVEPHP_DEVELOPMENT_TEAM`
- API Key: `AuthKey_6X5674BGPC.p8`
- Key ID: `6X5674BGPC`
- Issuer ID: Set via `APP_STORE_API_ISSUER_ID`

**Google Play Store:**
- Package: `me.kalfa.eventrsvp`
- Version Code: Integer from `NATIVEPHP_APP_VERSION_CODE`
- Upload: Via Google Play Console

### Release Notes Template

```
Version 1.0.0 - Initial Release

Features:
- Remote authentication to Kalfa RSVP platform
- View events and guest lists
- Manage RSVP responses
- Organization switching
- Secure biometric login (Face ID / Touch ID)

Requirements:
- iOS 16.0+ / Android 8.0+
- Internet connection
- Kalfa account
```

---

## 10. Known Limitations

### Platform-Specific

| Feature | iOS | Android | Notes |
|---------|-----|---------|-------|
| Build on Linux | ❌ | ✅ | iOS requires macOS |
| WebAuthn | ✅ | ✅ | Both supported |
| Biometrics | ✅ | ✅ | Face ID / Fingerprint |
| Background Refresh | ⚠️ | ⚠️ | Depends on OS settings |
| Push Notifications | ❌ | ❌ | Not yet implemented |
| Offline Mode | ❌ | ❌ | Configured but disabled |

### Configuration Gaps

1. **App Version** - Still "DEBUG", needs production version
2. **iPad Support** - Disabled (`ipad: false`)
3. **Orientation** - Portrait only (no landscape)
4. **Permissions** - None configured (camera, storage, etc.)

---

## 11. Recommendations

### Immediate (Before Build)

1. **Set Production Version:**
   ```env
   NATIVEPHP_APP_VERSION="1.0.0"
   NATIVEPHP_APP_VERSION_CODE="1"
   ```

2. **Configure Team ID:**
   ```env
   NATIVEPHP_DEVELOPMENT_TEAM="ABC1234567"  # From Apple Developer account
   ```

3. **Run Install Command:**
   ```bash
   php artisan native:install --force
   ```

4. **Test Development Server:**
   ```bash
   php artisan native:jump
   # Scan QR code with mobile device
   ```

### Short-Term (After Initial Build)

1. **Android SDK Setup** - Install on Linux for Android builds
2. **iOS CI Setup** - Configure GitHub Actions with macos-latest
3. **Test Automation** - Add mobile UI tests
4. **Crash Reporting** - Integrate Sentry/Firebase Crashlytics
5. **Analytics** - Add Firebase Analytics or Mixpanel

### Long-Term

1. **Multi-Platform** - Add Windows (Desktop) via Tauri or Electron
2. **Offline Support** - Enable offline mutations queue
3. **Push Notifications** - Implement FCM/APNs
4. **App Clips** - iOS App Clip for quick RSVP
5. **Multiple Languages** - RTL support improvements

---

## 12. Next Steps

1. **Fix environment variables** → Add version numbers
2. **Choose build platform**:
   - Option A: Build iOS on GitHub Actions (free)
   - Option B: Build iOS on manual Mac (if available)
   - Option C: Build Android on Linux (requires SDK setup)
3. **Test development flow** → `php artisan native:jump`
4. **Create build pipeline** → CI/CD for automated builds
5. **Deploy to stores** → App Store Connect + Google Play Console

---

**Report End**
