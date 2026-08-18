#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const environment = process.argv[2];
if (!['staging', 'production'].includes(environment)) {
  throw new Error('Usage: node scripts/archive.js <staging|production>');
}

const dist = path.resolve(__dirname, '..', 'dist');
const source = path.join(dist, environment);
const destination = path.join(dist, `navra-capture-${environment}.zip`);

if (!fs.existsSync(source)) {
  throw new Error(`Missing build directory: ${source}`);
}

const output = fs.createWriteStream(destination);
const archive = archiver('zip', { zlib: { level: 9 } });

output.on('close', () => {
  console.log(`✓ Archived ${environment}: ${archive.pointer()} bytes`);
});
archive.on('warning', (error) => {
  if (error.code === 'ENOENT') console.warn(error.message);
  else throw error;
});
archive.on('error', (error) => {
  throw error;
});

archive.pipe(output);
archive.directory(source, false);
archive.finalize();
