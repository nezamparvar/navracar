# ناوراکار Android App - Capacitor Setup

This is the Capacitor configuration for the ناوراکار Android mobile app MVP.

## Overview

The app provides a mobile-optimized interface for the vehicle pricing calculator with:
- Floating action button for quick access
- Auto-extraction of car details from product pages
- Local calculation history stored in device storage
- Offline-capable interface with online calculation sync

## Setup Instructions

### Prerequisites
- Node.js 16+ and npm
- Android Studio (for building Android APK)
- Java Development Kit (JDK) 11+
- Gradle

### Installation

1. **Install Capacitor globally:**
```bash
npm install -g @capacitor/cli
```

2. **Install project dependencies:**
```bash
npm install
```

3. **Initialize Capacitor (if not already done):**
```bash
npx cap init --web-dir public
```

4. **Add Android platform:**
```bash
npx cap add android
```

### Building for Android

1. **Build web assets:**
```bash
npm run build
```

2. **Copy assets to native project:**
```bash
npx cap sync android
```

3. **Open Android Studio:**
```bash
npx cap open android
```

4. **Build and run:**
- Select your device/emulator
- Click "Run" in Android Studio or use:
```bash
npx cap run android
```

## Mobile App Features

### Floating Action Button (FAB)
- Located at bottom-right of screen
- Tap to open the pricing calculator
- Accesses local calculation history

### Auto-Extraction
The app attempts to auto-extract car details from:
- Dubizzle product pages
- YallaMotor listings
- Other vehicle listing sites

Extracted data includes:
- Car make/model
- Year
- Engine size
- Approximate pricing

### Local History
Calculation results are stored locally using browser storage:
- Last 10 calculations cached
- Persistent across app restarts
- Synced with server when online

### Offline Support
- Core calculator functions work offline
- Results cached for recently calculated vehicles
- Online sync when connection available

## API Endpoints

The app uses the following endpoints:
- `POST /api/vehicle-pricing/calculate` - Calculate pricing (JSON response)
- `GET /app` - Mobile app interface

## Configuration

### App Settings
Edit `capacitor.config.json` to modify:
- App ID: `com.navracar.mobile`
- App name: `ناوراکار`
- Web directory: `public`

### URL Rewriting
The app uses HTML5 routing. Ensure your server routes requests to `index.html` for the `/app` path.

## Deployment

### Create Release Build
```bash
npx cap build android --keystorePath [path] --keystoreAlias [alias]
```

### Google Play Store
1. Build signed APK in Android Studio
2. Create app listing in Google Play Console
3. Upload APK and configure store listing
4. Submit for review

## Troubleshooting

### App crashes on launch
- Check browser console for errors
- Verify API endpoints are accessible
- Ensure CSRF token is properly set

### Calculations not working
- Verify server is accessible from device
- Check network proxy settings
- Ensure rate limiting is not triggered

### History not persisting
- Check browser storage permissions in Android manifest
- Clear app data and retry

## Development

### Local Testing
```bash
npx cap run android --livereload
```

This enables live reload during development.

### Debugging
1. Use Chrome DevTools: `chrome://inspect`
2. Connect device via USB with debugging enabled
3. View console logs and debug JavaScript

## Performance Optimization

- Lazy load heavy components
- Minimize API calls with local caching
- Use service workers for offline support
- Optimize images for mobile screens

## Security Considerations

- HTTPS only for all API calls
- CSRF token validation on forms
- Local storage encryption for sensitive data
- Never store authentication tokens in localStorage

## Future Enhancements

- [ ] Push notifications for pricing updates
- [ ] Barcode scanning for VIN lookup
- [ ] Camera integration for vehicle photos
- [ ] Share calculation results via messaging
- [ ] Offline PDF report generation
