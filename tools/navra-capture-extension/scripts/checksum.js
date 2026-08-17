#!/usr/bin/env node

const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const dist = path.resolve(__dirname, '..', 'dist');
const packageVersion = JSON.parse(fs.readFileSync(path.resolve(__dirname, '..', 'package.json'), 'utf8')).version;
const expectedConfiguration = {
  staging: {
    apiUrl: 'https://staging.nezamparvar.com/api',
    hostPermission: 'https://staging.nezamparvar.com/*',
  },
  production: {
    apiUrl: 'https://navracar.com/api',
    hostPermission: 'https://navracar.com/*',
  },
};

for (const environment of ['staging', 'production']) {
  const worker = fs.readFileSync(path.join(dist, environment, 'src/background/service-worker.js'), 'utf8');
  if (!worker.includes(`const EXTENSION_ENVIRONMENT = '${environment}';`)) {
    throw new Error(`${environment} package points to the wrong API environment`);
  }
  if (!worker.includes(`apiUrl: '${expectedConfiguration[environment].apiUrl}'`)) {
    throw new Error(`${environment} package has the wrong API URL`);
  }

  const manifest = JSON.parse(fs.readFileSync(path.join(dist, environment, 'manifest.json'), 'utf8'));
  if (manifest.version !== packageVersion) {
    throw new Error(`${environment} package has version ${manifest.version}; expected ${packageVersion}`);
  }
  const expectedHost = expectedConfiguration[environment].hostPermission;
  const otherEnvironment = environment === 'staging' ? 'production' : 'staging';
  const otherHost = expectedConfiguration[otherEnvironment].hostPermission;
  if (!manifest.host_permissions.includes(expectedHost) || manifest.host_permissions.includes(otherHost)) {
    throw new Error(`${environment} package has unsafe API host permissions`);
  }

  const name = `navra-capture-${environment}.zip`;
  const file = path.join(dist, name);
  if (!fs.existsSync(file)) throw new Error(`Missing archive: ${name}`);
  const digest = crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
  fs.writeFileSync(`${file}.sha256`, `${digest}  ${name}\n`);
}
