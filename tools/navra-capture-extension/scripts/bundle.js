#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const ROOT = path.dirname(path.dirname(__dirname)) + '/navra-capture-extension';
const DIST = path.join(ROOT, 'dist');

function createEnvironmentBuild(environment) {
  const envDist = path.join(DIST, environment);

  console.log(`[Navra Capture] Building ${environment} version...`);

  // Create directories
  ['src/background', 'src/content', 'src/popup', 'src/icons'].forEach((dir) => {
    const fullPath = path.join(envDist, dir);
    fs.mkdirSync(fullPath, { recursive: true });
  });

  // Copy manifest
  let manifest = fs.readFileSync(path.join(ROOT, 'manifest.json'), 'utf8');
  const manifestObj = JSON.parse(manifest);
  manifestObj.version = '1.0.0';
  if (environment === 'staging') {
    manifestObj.name = 'Navra Capture — Staging';
  }
  fs.writeFileSync(path.join(envDist, 'manifest.json'), JSON.stringify(manifestObj, null, 2));

  // Copy source files
  const srcFiles = [
    'src/background/service-worker.js',
    'src/content/content-script.js',
    'src/popup/popup.html',
    'src/popup/popup.css',
    'src/popup/popup.js',
  ];

  srcFiles.forEach((file) => {
    const src = path.join(ROOT, file);
    const dest = path.join(envDist, file);
    if (fs.existsSync(src)) {
      fs.copyFileSync(src, dest);
    }
  });

  // Create placeholder icons (1x1 PNG in base64)
  const iconBase64 =
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
  const iconBuffer = Buffer.from(iconBase64, 'base64');

  ['16', '48', '128'].forEach((size) => {
    fs.writeFileSync(path.join(envDist, `src/icons/icon-${size}.png`), iconBuffer);
  });

  console.log(`✓ Built ${environment} version to ${envDist}`);
}

// Clean dist directory
if (fs.existsSync(DIST)) {
  fs.rmSync(DIST, { recursive: true });
}

// Build both environments
createEnvironmentBuild('staging');
createEnvironmentBuild('production');

console.log('\n✓ Navra Capture extension build complete!');
console.log(`\nTo install in Chrome/Edge:`);
console.log(`1. Open chrome://extensions (or edge://extensions)`);
console.log(`2. Enable Developer mode`);
console.log(`3. Click "Load unpacked"`);
console.log(`4. Select: ${DIST}/staging (for testing)`);
console.log(`           ${DIST}/production (for production)`);
