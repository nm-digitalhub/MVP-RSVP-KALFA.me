# NativePHP Mobile Deployment Guide
## Kalfa RSVP Mobile Application

**Version:** 1.0.0
**Last Updated:** 2025-03-22
**Platform:** iOS 16.0+ / Android 14.0+

---

## Overview

The Kalfa RSVP mobile app is built using **NativePHP Mobile v3.0.4**, which allows running a Laravel application as a native iOS and Android app with full access to native device features.

### Key Features

- **Remote Authentication** - Login to Laravel API from mobile
- **Secure Storage** - Access tokens stored in iOS Keychain / Android Keystore
- **Organization Switching** - Switch between organizations
- **Event Management** - View events, guests, and RSVPs
- **Biometric Login** - Face ID / Touch ID / Fingerprint support
- **Deep Linking** - `kalfa://` scheme and `https://kalfa.me` verification

---

## Prerequisites

### For iOS Builds

| Requirement | Version | Notes |
|-------------|---------|-------|
| macOS | 14+ | Sonoma or later |
| Xcode | 14+ | From App Store |
| Ruby | 3.0+ | For Fastlane (optional) |
| CocoaPods | 1.12+ | If using plugins |
| Apple Developer Account | Active | $99/year |

### For Android Builds

| Requirement | Version | Notes |
|-------------|---------|-------|
| Java JDK | 17+ | OpenJDK or Oracle |
| Android SDK | 34.0+ | Platform & Build Tools |
| Gradle | 8.0+ | Bundled with project |
| Android Studio | Hedgehog | 2023.1.1+ (optional) |

### Server Requirements

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.4+ | Laravel 12 requirement |
| Composer | 2.x | Dependency management |
| Node.js | 20+ | For frontend build |

---

## Environment Configuration

### Required Environment Variables

Add to `.env`:

```env
# ============================================
# NativePHP Mobile Configuration
# ============================================

# App Identification (Required)
NATIVEPHP_APP_ID=me.kalfa.eventrsvp
NATIVEPHP_APP_VERSION=1.0.0
NATIVEPHP_APP_VERSION_CODE=1

# Deep Linking (Required)
NATIVEPHP_DEEPLINK_SCHEME=kalfa
NATIVEPHP_DEEPLINK_HOST=kalfa.me

# Entry Point (Required)
NATIVEPHP_START_URL=/mobile

# iOS Specific (Required for iOS builds)
NATIVEPHP_DEVELOPMENT_TEAM=ABC1234567  # Apple Developer Team ID

# API Configuration
VITE_MOBILE_API_BASE_URL=https://kalfa.me

# iOS Credentials (for packaging)
IOS_DISTRIBUTION_CERTIFICATE_PATH=credentials/distribution.p12
IOS_DISTRIBUTION_CERTIFICATE_PASSWORD="your-password"
IOS_DISTRIBUTION_PROVISIONING_PROFILE_PATH=credentials/profile.mobileprovision

# App Store Connect (for automated upload)
APP_STORE_API_KEY_PATH=credentials/AuthKey_6X5674BGPC.p8
APP_STORE_API_KEY_ID=6X5674BGPC
APP_STORE_API_ISSUER_ID=f6b7bf87-b2fb-4b05-812b-30efbdb54a3c
```

### Variable Explanations

| Variable | Purpose | Format |
|----------|---------|--------|
| `NATIVEPHP_APP_ID` | Unique app identifier | Reverse domain (e.g., `com.company.app`) |
| `NATIVEPHP_APP_VERSION` | Human-readable version | Semantic versioning (e.g., `1.0.0`) |
| `NATIVEPHP_APP_VERSION_CODE` | Internal version number | Integer (increments each release) |
| `NATIVEPHP_DEVELOPMENT_TEAM` | Apple Developer Team ID | 10-character alphanumeric string |

---

## Installation

### Step 1: Install NativePHP

```bash
php artisan native:install --force
```

This creates:
- `nativephp/android/` - Android project
- `nativephp/ios/` - iOS project (on macOS)
- `nativephp/resources/` - Shared resources

### Step 2: Verify Installation

```bash
php artisan native:version
# Output: NativePHP Mobile v3.0.4
```

### Step 3: Test Development Server

```bash
php artisan native:jump
```

This:
1. Starts a development server on port 3000
2. Opens browser with QR code
3. Serves mobile app at `http://localhost:3000/__native__`
4. Enables hot reload for development

**To test:**
1. Scan QR code with mobile device
2. Or open `http://your-ip:3000/__native__` in mobile browser

---

## Building for iOS

### Method 1: Using Xcode (macOS only)

```bash
# Build the iOS project
php artisan native:build ios

# Open in Xcode
php artisan native:open ios

# Package (create .ipa)
php artisan native:package ios

# Run on simulator
php artisan native:run ios
```

### Method 2: Using GitHub Actions (recommended)

**Setup:**

1. Add secrets to GitHub repository:
   - `IOS_DISTRIBUTION_CERTIFICATE` (base64 encoded .p12 file)
   - `IOS_DISTRIBUTION_CERTIFICATE_PASSWORD`
   - `IOS_PROVISIONING_PROFILE` (base64 encoded .mobileprovision)
   - `APP_STORE_CONNECT_API_KEY_ID`
   - `APP_STORE_CONNECT_ISSUER_ID`
   - `APPLE_DEVELOPMENT_TEAM`

2. Push a version tag:
```bash
git tag v1.0.0
git push origin v1.0.0
```

The workflow will:
- Build the iOS app
- Sign with distribution certificate
- Upload to TestFlight
- Create App Store Connect app (if needed)

### Manual IPA Creation

```bash
# Build
php artisan native:build ios

# Navigate to Xcode project
cd nativephp/ios

# Build archive
xcodebuild -scheme NativePHP \
  -sdk iphoneos \
  -configuration Release \
  -archivePath ~/Desktop/NativePHP.xcarchive

# Export IPA
xcodebuild -exportArchive \
  -archivePath ~/Desktop/NativePHP.xcarchive \
  -exportPath ~/Desktop/NativePHP.ipa \
  -exportOptionsPlist exportOptions.plist
```

---

## Building for Android

### Method 1: Using Gradle (Linux/macOS)

```bash
# Build the Android project
php artisan native:build android

# Package AAB for Play Store
php artisan native:package android

# Build APK for testing
cd nativephp/android
./gradlew assembleDebug

# Run on emulator
php artisan native:emulator
```

### Method 2: Using GitHub Actions

Push a version tag:
```bash
git tag v1.0.0
git push origin v1.0.0
```

The workflow will:
- Build the Android AAB
- Sign with upload key
- Upload to Play Console (internal testing track)

---

## Testing

### Development Testing

**Test with real device:**

1. Start dev server:
```bash
php artisan native:jump
```

2. Connect device via USB
3. Enable USB debugging
4. Open mobile browser and navigate to displayed URL

**Or use Android/iOS simulator:**
```bash
# iOS simulator (on macOS)
php artisan native:run ios

# Android emulator
php artisan native:emulator
```

### Production Build Testing

**Test APK/AAB:**

```bash
# Install APK on connected device
adb install nativephp/android/app/build/outputs/apk/debug/app-debug.apk

# Or install from GitHub Actions artifact
# Download from Actions tab and install via adb
```

**Test Flight Testing (iOS):**

1. Upload via GitHub Actions or Xcode
2. Add testers in App Store Connect
3. Install TestFlight app on device
4. Download app from TestFlight tab

---

## App Store Deployment

### iOS App Store

1. **App Store Connect Setup**
   - Login to [App Store Connect](https://appstoreconnect.apple.com)
   - Create new app with bundle ID `me.kalfa.eventrsvp`
   - Fill app information:
     - Name: "Kalfa RSVP"
     - SKU: `KALFA-RSVP-001`
     - Primary Language: Hebrew
     - Secondary Language: English

2. **App Information**
   - Category: Lifestyle (or Business)
   - Content Rating: Questionnaire to be completed
   - Pricing: Free (or paid if applicable)

3. **Upload Build**
   - Via GitHub Actions (automatic on tag)
   - Or via Xcode: Organizer → Validate App → Distribute App

4. **Screenshots Required**
   - iPhone 6.7" (6.5" display): 1290 x 2796 px
   - iPhone 6.7" (5.5" display): 1242 x 2208 px
   - iPad Pro 12.9" (3rd Gen): 2048 x 2732 px
   - iPad Pro 12.9" (2nd Gen): 2048 x 2732 px

5. **Review Information**
   - App privacy details
   - Data collection disclosure
   - Privacy policy URL

6. **Submit for Review**
   - Average review time: 1-3 days
   - Test thoroughly before submission

### Google Play Store

1. **Play Console Setup**
   - Login to [Google Play Console](https://play.google.com/console)
   - Create new app
   - Enter app details:
     - App name: "Kalfa RSVP"
     - Package name: `me.kalfa.eventrsvp`
     - Main language: Hebrew
     - Free or Paid

2. **Store Listing**
   - Description (Hebrew/English)
   - Screenshots (phone + tablet)
   - Icon (512x512 px)
   - Feature graphic (1024x500 px)

3. **Content Rating**
   - Complete questionnaire
   - Verify rating (should be suitable for all ages)

4. **Upload AAB**
   - Via GitHub Actions (automatic on tag)
   - Or manual upload from build artifact

5. **Release**
   - Internal testing track
   - Closed testing (up to 100 testers)
   - Production release

---

## App Store Metadata

### App Name
- **Hebrew:** Kalfa RSVP - ניהום אירועים
- **English:** Kalfa RSVP - Event Management

### Short Description
- **Hebrew:** ניהול השתתתות אירועים וניהול אורחים בקלות חדשות
- **English:** Manage event RSVPs and seating arrangements easily

### Full Description

**Hebrew:**
```
אפליקציית Kalfa RSVP מאפשרת לך לנהל אירועים, לנהל אורחים, ולעקוב אחר נתונים מצט.
עם ממשק קל וחוויה נוחה, תוכל לעדכן בזמן את כמות האורחים, להציג רשימות הושבה, ולנהל את תהליך ההרשמה.

תכונות מרכזיות:
• הצגה אירועים ורשימת אורחים
• ניהול RSVP פשוט ומהיר
• מעקב אחר הושבה בזמן אמת
• שינוי ארגונים בקלות חדשות
• התחברות ביומטריה (Face ID / Touch ID)
```

**English:**
```
Kalfa RSVP makes it easy to manage events, track guests, and follow up with RSVPs.
With an intuitive interface, you can check guest counts, view seating charts, and manage event invitations on the go.

Key features:
• View events and guest lists
• Quick RSVP with real-time sync
• Track response status instantly
• Switch between multiple organizations
• Biometric login (Face ID / Fingerprint)
```

### Keywords (Hebrew)
```
אירועים, רשימות, השתתתות, הזמנה, ניהול מוזמן, חתונה, אורחים, אירוח, ניהולי השתתתות
```

### Keywords (English)
```
events, rsvp, wedding, seating, guests, invitation, response, party, management, planner
```

---

## Privacy Policy URL

Host at: `https://kalfa.me/privacy-policy`

Create page with:
- Data collection practices
- Third-party services used
- User rights
- Contact information

---

## Support URL

Host at: `https://kalfa.me/support` or `mailto:support@kalfa.me`

---

## Troubleshooting

### Build Issues

**Issue: "Team ID not found"**
- **Solution:** Set `NATIVEPHP_DEVELOPMENT_TEAM` in `.env`
- **Value:** Found in Apple Developer account under Membership

**Issue: "Provisioning profile expired"**
- **Solution:** Download new profile from Apple Developer portal
- **Path:** Update `credentials/profile.mobileprovision`

**Issue: "Code signing failed"**
- **Solution:** Verify certificate is valid and not revoked
- **Check:** Keychain access and certificate trust

### Runtime Issues

**Issue: "API calls failing with 401 Unauthorized"**
- **Solution:** Check `VITE_MOBILE_API_BASE_URL` is correct
- **Verify:** Laravel API is accessible from device

**Issue: "Deep links not opening app"**
- **Solution:** Verify `apple-app-site-association` file on server
- **Android:** Check `assetlinks.json` is hosted correctly

### Device-Specific Issues

**iOS: "App crashes on launch"**
- Check device compatibility (iOS 16+)
- Verify provisioning profile includes device UDID
- Check crash logs in Xcode Organizer

**Android: "App won't install"**
- Verify Android version (14.0+ / API 34+)
- Check for APK signature issues
- Verify Google Play Protect is not blocking

---

## Version Management

### Bumping Version

**Automatic (via command):**
```bash
php artisan native:release
# Bumps NATIVEPHP_APP_VERSION_CODE and commits changes
```

**Manual:**
1. Update `.env`:
```env
NATIVEPHP_APP_VERSION=1.0.1
NATIVEPHP_APP_VERSION_CODE=2
```
2. Commit and tag:
```bash
git add .env
git commit -m "Bump version to 1.0.1"
git tag v1.0.1
git push origin main --tags
```

### Semantic Versioning

- **Major (X.0.0):** Breaking changes, new features requiring user action
- **Minor (0.X.0):** New features, backward compatible
- **Patch (0.0.X):** Bug fixes, minor improvements

---

## CI/CD Pipeline

### GitHub Actions Workflows

**Triggered by:**
- Push to `main` branch
- Tag creation (`v*.*.*`)
- Manual workflow dispatch

**Workflows:**
- `.github/workflows/build-ios.yml` - iOS builds
- `.github/workflows/build-android.yml` - Android builds

### Automated Process

```
┌─────────────────┐
│  Git Push / Tag  │
└────────┬────────┘
         │
    ┌────▼─────┐
    │ GitHub   │
    │ Actions   │
    └────┬─────┘
         │
    ┌────▼────────────┐
    │  Install       │
    │  Dependencies   │
    └────┬────────────┘
         │
    ┌────▼────────────┐
    │  NativePHP      │
    │  Install       │
    └────┬────────────┘
         │
    ┌────▼────────────┐
    │  Build &        │
    │  Package        │
    └────┬────────────┘
         │
    ┌────▼────────────┐
    │  Upload to      │
    │  Stores        │
    └─────────────────┘
```

---

## Rollback Procedure

### If Critical Bug Found

1. **Hotfix Branch**
```bash
git checkout -b hotfix/critical-bug
# Fix the bug
git commit -m "Fix critical crash"
git push origin hotfix/critical-bug
```

2. **Tag New Version**
```bash
git tag v1.0.1-hotfix
git push origin v1.0.1-hotfix
```

3. **Submit to Stores**
- iOS: Submit as new version to TestFlight
- Android: Upload new AAB to Play Console

### If Store Rejection

1. **Read Rejection Reason**
   - Usually email with specific issue
   - Common: metadata, content rating, permissions

2. **Fix Issue**
   - Update metadata in app store console
   - Or fix code and rebuild

3. **Resubmit**
   - Reply to rejection message
   - Or submit new build

---

## Success Metrics

### Launch Targets

| Metric | Target | Timeframe |
|--------|--------|-----------|
| TestFlight beta testers | 10+ | Week 1 |
| Play Store internal testing | 5 users | Week 1 |
| App Store approval | Approved | Week 2 |
| Play Store approval | Approved | Week 2 |
| First 100 downloads | Achieved | Month 1 |

### Post-Launch Monitoring

- Crash rate < 2%
- API success rate > 99%
- App load time < 3 seconds
- User rating > 4.0 stars

---

**End of Deployment Guide**
