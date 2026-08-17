#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');

const environment = process.argv[2];
if (!['staging', 'production'].includes(environment)) {
  throw new Error('Archive environment must be staging or production');
}

const dist = path.resolve(__dirname, '..', 'dist');
const source = path.join(dist, environment);
const archive = path.join(dist, `navra-capture-${environment}.zip`);

if (!fs.existsSync(source)) {
  throw new Error(`Missing extension build directory: ${source}`);
}
if (fs.existsSync(archive)) {
  fs.unlinkSync(archive);
}

const command = process.platform === 'win32' ? 'powershell.exe' : 'zip';
const args = process.platform === 'win32'
  ? [
      '-NoProfile',
      '-NonInteractive',
      '-Command',
      "Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::CreateFromDirectory((Get-Location).Path, $env:NAVRA_EXTENSION_ARCHIVE)",
    ]
  : ['-qr', archive, '.'];
const result = spawnSync(command, args, {
  cwd: source,
  env: { ...process.env, NAVRA_EXTENSION_ARCHIVE: archive },
  stdio: 'inherit',
});

if (result.status !== 0) {
  throw new Error(`Failed to create ${path.basename(archive)}`);
}

console.log(`✓ Created ${archive}`);
