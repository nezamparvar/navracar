<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StagingSafetyTest extends TestCase
{
    public function test_android_staging_builder_targets_the_canonical_subdomain(): void
    {
        $builder = file_get_contents(base_path('tools/build-android-variants.sh'));

        $this->assertStringContainsString("build_variant staging 'https://staging.nezamparvar.com'", $builder);
        $this->assertStringNotContainsString("build_variant staging 'https://navracar.com/staging'", $builder);
    }

    public function test_staging_candidate_can_use_an_exact_green_branch_head_without_unlocking_production(): void
    {
        $stagingWorkflow = file_get_contents(base_path('.github/workflows/cpanel-staging.yml'));
        $promotionWorkflow = file_get_contents(base_path('.github/workflows/cpanel-promote.yml'));

        $this->assertStringContainsString('SOURCE_REF: ${{ github.ref_name }}', $stagingWorkflow);
        $this->assertStringContainsString('[[ "$INPUT_COMMIT" == "$BRANCH_HEAD" ]]', $stagingWorkflow);
        $this->assertStringContainsString('ref: ${{ needs.verify-source.outputs.source_commit }}', $stagingWorkflow);
        $this->assertStringContainsString("if: github.ref == 'refs/heads/main'", $promotionWorkflow);
        $this->assertStringContainsString('Release tag does not identify the supplied source commit.', $promotionWorkflow);
    }

    public function test_cpanel_staging_deployment_repairs_schema_and_pdf_runtime_without_terminal(): void
    {
        $runtimeHelper = file_get_contents(base_path('deployment/cpanel-staging/ensure-runtime.sh'));
        $deployScript = file_get_contents(base_path('deployment/cpanel-staging/deploy.sh'));

        $this->assertStringContainsString("'storage/fonts'", $runtimeHelper);
        $this->assertStringContainsString('resolve_staging_php_bin()', $runtimeHelper);
        $this->assertStringContainsString('PHP_VERSION_ID >= 80300', $runtimeHelper);
        $this->assertStringContainsString('PHP_BIN="$(resolve_staging_php_bin)"', $deployScript);
        $this->assertStringContainsString('artisan migrate --force --no-interaction', $deployScript);
        $this->assertStringContainsString('artisan optimize:clear --no-interaction', $deployScript);
        $this->assertStringContainsString('artisan config:cache --no-interaction', $deployScript);
        $this->assertStringContainsString('artisan route:cache --no-interaction', $deployScript);
        $this->assertStringContainsString('artisan view:cache --no-interaction', $deployScript);
        $this->assertStringContainsString('find "$public_disk_root" -type d -exec chmod 0755', $runtimeHelper);
        $this->assertStringContainsString('find "$public_disk_root" -type f -exec chmod 0644', $runtimeHelper);
        $this->assertStringNotContainsString('/home/navrac/navracar-app/.env', $deployScript);
    }

    public function test_staging_subdirectory_root_is_explicitly_rewritten_to_laravel(): void
    {
        $htaccess = file_get_contents(base_path('deployment/cpanel-staging/public_html/.htaccess'));

        $rootRewrite = strpos($htaccess, 'RewriteRule ^$ index.php [L]');
        $directoryGuard = strpos($htaccess, 'RewriteCond %{REQUEST_FILENAME} !-d');

        $this->assertNotFalse($rootRewrite);
        $this->assertNotFalse($directoryGuard);
        $this->assertLessThan($directoryGuard, $rootRewrite);
    }

    public function test_staging_responses_are_marked_noindex_and_show_environment_indicator(): void
    {
        $this->app->detectEnvironment(fn () => 'staging');
        config(['navaracar.disable_outbound' => true]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->assertStringContainsString('STAGING', view('components.staging-banner')->render());
    }

    public function test_staging_response_exposes_deployed_candidate_identity(): void
    {
        $metadataPath = base_path('.cpanel-release.json');
        $this->assertFileDoesNotExist($metadataPath);
        file_put_contents($metadataPath, json_encode([
            'release_candidate' => 'rc-v1.3.0-4',
            'source_commit' => str_repeat('a', 40),
        ], JSON_THROW_ON_ERROR));

        try {
            $this->app->detectEnvironment(fn () => 'staging');
            $response = $this->get(route('login'));

            $response->assertHeader('X-Navracar-Candidate', 'rc-v1.3.0-4');
            $response->assertHeader('X-Navracar-Source', str_repeat('a', 40));
        } finally {
            @unlink($metadataPath);
        }
    }

    public function test_staging_subdirectory_configuration_is_isolated(): void
    {
        config([
            'app.env' => 'staging',
            'app.url' => 'https://navracar.com/staging',
            'session.cookie' => 'navracar_staging_session',
            'session.path' => '/staging',
            'cache.prefix' => 'navracar_staging_',
            'filesystems.disks.public.url' => 'https://navracar.com/staging/storage',
        ]);

        $this->assertSame('https://navracar.com/staging', config('app.url'));
        $this->assertSame('/staging', config('session.path'));
        $this->assertSame('navracar_staging_session', config('session.cookie'));
        $this->assertSame('navracar_staging_', config('cache.prefix'));
        $this->assertSame('https://navracar.com/staging/storage/vehicle.jpg', Storage::disk('public')->url('vehicle.jpg'));
    }

    public function test_staging_public_disk_can_write_directly_to_its_isolated_web_storage(): void
    {
        $root = storage_path('framework/testing/staging-public');
        config([
            'filesystems.disks.public.root' => $root,
            'filesystems.disks.public.url' => 'https://navracar.com/staging/storage',
        ]);
        Storage::disk('public')->put('car-listings/1/image.gif', 'gif-bytes');

        $this->assertFileExists($root.'/car-listings/1/image.gif');
        $this->assertStringStartsWith('https://navracar.com/staging/storage/', Storage::disk('public')->url('car-listings/1/image.gif'));
        $this->assertFileDoesNotExist(storage_path('../navracar-app/storage/app/public/car-listings/1/image.gif'));
    }
}
