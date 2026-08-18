import { existsSync, writeFileSync } from 'node:fs';
import { spawn, spawnSync } from 'node:child_process';
import path from 'node:path';

const root = process.cwd();
const defaultWindowsPhp = path.resolve(root, '..', 'runtime', 'php', 'php.exe');
const php = process.env.PLAYWRIGHT_PHP_BINARY || (existsSync(defaultWindowsPhp) ? defaultWindowsPhp : 'php');
const database = path.resolve(root, 'database', 'e2e.sqlite');
writeFileSync(database, '');

const env = {
    ...process.env,
    APP_ENV: 'local',
    APP_KEY: 'base64:MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDA=',
    APP_URL: 'http://127.0.0.1:8000',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: database,
    CACHE_STORE: 'array',
    MAIL_MAILER: 'array',
    QUEUE_CONNECTION: 'sync',
    SESSION_DRIVER: 'file',
};

const migrate = spawnSync(php, ['artisan', 'migrate:fresh', '--seed', '--force'], { cwd: root, env, stdio: 'inherit' });
if (migrate.status !== 0) {
    process.exit(migrate.status ?? 1);
}

const seedE2e = spawnSync(php, ['artisan', 'db:seed', '--class=Database\\Seeders\\E2eSeeder', '--force'], { cwd: root, env, stdio: 'inherit' });
if (seedE2e.status !== 0) {
    process.exit(seedE2e.status ?? 1);
}

// E2eSeeder attaches real cover images to demo listings; without this symlink
// Storage::disk('public')->url() points at a path the dev server can't serve.
spawnSync(php, ['artisan', 'storage:link'], { cwd: root, env, stdio: 'inherit' });

const server = spawn(php, [
    '-S', '127.0.0.1:8000',
    path.resolve(root, 'vendor', 'laravel', 'framework', 'src', 'Illuminate', 'Foundation', 'resources', 'server.php'),
], { cwd: path.resolve(root, 'public'), env, stdio: 'ignore' });
for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => server.kill(signal));
}
server.on('exit', (code) => process.exit(code ?? 0));
