#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const ROOT = path.dirname(path.dirname(__dirname)) + '/navra-capture-extension';
const DIST = path.join(ROOT, 'dist');
const API_HOST_PERMISSIONS = {
  staging: 'https://staging.nezamparvar.com/*',
  production: 'https://navracar.com/*',
};

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
  const apiHostPermissions = new Set(Object.values(API_HOST_PERMISSIONS));
  manifestObj.host_permissions = manifestObj.host_permissions.filter(
    (permission) => !apiHostPermissions.has(permission),
  );
  manifestObj.host_permissions.push(API_HOST_PERMISSIONS[environment]);
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
      if (file === 'src/background/service-worker.js') {
        const worker = fs.readFileSync(src, 'utf8').replace(
          /const EXTENSION_ENVIRONMENT = '(?:staging|production)';/,
          `const EXTENSION_ENVIRONMENT = '${environment}';`,
        );
        fs.writeFileSync(dest, worker);
      } else {
        fs.copyFileSync(src, dest);
      }
    }
  });

  ['16', '48', '128'].forEach((size) => {
    fs.copyFileSync(
      path.join(ROOT, `assets/icon-${size}.png`),
      path.join(envDist, `src/icons/icon-${size}.png`),
    );
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
