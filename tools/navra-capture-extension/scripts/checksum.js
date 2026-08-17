#!/usr/bin/env node

const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const dist = path.resolve(__dirname, '..', 'dist');
for (const environment of ['staging', 'production']) {
  const worker = fs.readFileSync(path.join(dist, environment, 'src/background/service-worker.js'), 'utf8');
  if (!worker.includes(`const EXTENSION_ENVIRONMENT = '${environment}';`)) {
    throw new Error(`${environment} package points to the wrong API environment`);
  }
  const name = `navra-capture-${environment}.zip`;
  const file = path.join(dist, name);
  if (!fs.existsSync(file)) throw new Error(`Missing archive: ${name}`);
  const digest = crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
  fs.writeFileSync(`${file}.sha256`, `${digest}  ${name}\n`);
}
