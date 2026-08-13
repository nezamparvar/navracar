<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Post;
use App\Services\OutboundUrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityBaselineTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $username, ?string $fullName = null): AdminUser
    {
        return AdminUser::create([
            'username' => $username,
            'password_hash' => bcrypt('correct-password'),
            'full_name' => $fullName,
            'role' => $role,
        ]);
    }

    public function test_security_headers_are_applied_centrally(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_public_lead_form_exposes_only_required_named_sales_staff(): void
    {
        $this->user('admin', 'private-admin', 'Private Admin');
        $this->user('sales', 'private-sales-login', 'Public Sales Name');
        $this->user('sales', 'nameless-sales');

        $this->get(route('public.lead-form'))
            ->assertOk()
            ->assertSee('Public Sales Name')
            ->assertDontSee('private-sales-login')
            ->assertDontSee('Private Admin')
            ->assertDontSee('nameless-sales');
    }

    public function test_outbound_url_guard_rejects_internal_hosts_credentials_ports_and_wrong_schemes(): void
    {
        $guard = app(OutboundUrlGuard::class);
        $hosts = ['dubai.dubizzle.com'];

        $this->assertTrue($guard->allows('https://dubai.dubizzle.com/motors/used-cars/x', $hosts));
        $this->assertFalse($guard->allows('http://dubai.dubizzle.com/x', $hosts));
        $this->assertFalse($guard->allows('https://127.0.0.1/x', $hosts));
        $this->assertFalse($guard->allows('https://localhost/x', $hosts));
        $this->assertFalse($guard->allows('https://dubai.dubizzle.com.evil.test/x', $hosts));
        $this->assertFalse($guard->allows('https://user@dubai.dubizzle.com/x', $hosts));
        $this->assertFalse($guard->allows('https://dubai.dubizzle.com:8443/x', $hosts));
    }

    public function test_post_body_is_sanitized_and_cover_upload_is_verified(): void
    {
        Storage::fake('public');
        $user = $this->user('content_manager', 'content');

        $this->actingAs($user)->post(route('admin.posts.store'), [
            'title' => 'Safe post',
            'body' => '<p onclick="evil()">فارسی <strong>مجاز</strong><script>alert(1)</script></p>',
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 320, 180),
        ])->assertRedirect();

        $post = Post::firstOrFail();
        $this->assertStringNotContainsString('onclick', $post->body);
        $this->assertStringNotContainsString('<script', $post->body);
        $this->assertStringContainsString('<strong>مجاز</strong>', $post->body);
        Storage::disk('public')->assertExists($post->cover_image_path);
        $this->assertStringNotContainsString('cover.jpg', $post->cover_image_path);
    }

    public function test_preexisting_unsanitized_post_content_is_sanitized_when_rendered(): void
    {
        $post = Post::create([
            'title' => 'Legacy unsafe post',
            'slug' => 'legacy-unsafe-post',
            'body' => '<p onclick="evil()">Persian <strong>allowed</strong><script>alert(1)</script></p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get(route('public.blog.show', $post))
            ->assertOk()
            ->assertDontSee('onclick', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('<strong>allowed</strong>', false);
    }

    public function test_executable_and_oversized_cover_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $user = $this->user('content_manager', 'content-uploads');

        $this->actingAs($user)->post(route('admin.posts.store'), [
            'title' => 'Bad upload', 'body' => '<p>Body</p>',
            'cover_image' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
        ])->assertSessionHasErrors('cover_image');

        $this->actingAs($user)->post(route('admin.posts.store'), [
            'title' => 'Large upload', 'body' => '<p>Body</p>',
            'cover_image' => UploadedFile::fake()->create('large.jpg', 9000, 'image/jpeg'),
        ])->assertSessionHasErrors('cover_image');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_public_write_rate_limit_returns_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('public.quote-requests.store'), ['website' => 'bot'])->assertOk();
        }
        $this->postJson(route('public.quote-requests.store'), ['website' => 'bot'])->assertStatus(429);
    }

    public function test_authentication_boundary_and_valid_login_still_work(): void
    {
        $admin = $this->user('admin', 'auth-admin', 'Auth Admin');
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->post(route('login'), ['username' => $admin->username, 'password' => 'correct-password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }
}
