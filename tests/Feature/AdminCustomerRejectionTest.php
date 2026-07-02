<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerRejection;
use App\Modules\CustomerAuth\Notifications\CustomerNotificationType;
use App\Notifications\TestUserNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class AdminCustomerRejectionTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private Admin $admin;

    private Country $country;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->configureCustomerAuthTesting();
        [$this->country, $this->city] = $this->seedCountryAndCity();
        $this->admin = Admin::factory()->active()->create();

        $dir = public_path('customer_profile_images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        \Illuminate\Support\Facades\Storage::fake('public');
    }

    public function test_admin_can_reject_pending_customer(): void
    {
        $customer = $this->createPendingCustomer();

        $response = $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/reject",
            [
                'rejection_reason' => 'National ID document is unclear and must be resubmitted.',
                'invalid_fields' => ['national_id'],
                'missing_attachments' => ['profile_image'],
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.status', Customer::STATUS_REJECTED);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'status' => Customer::STATUS_REJECTED,
        ]);

        $this->assertDatabaseHas('customer_rejections', [
            'customer_id' => $customer->id,
            'rejected_by' => $this->admin->id,
        ]);

        $notification = DatabaseNotification::query()
            ->where('notifiable_id', $customer->id)
            ->where('data->meta->event_type', CustomerNotificationType::ProfileRejected->value)
            ->first();

        $this->assertNotNull($notification);
        Mail::assertSent(\App\Mail\CustomerRejectionMail::class);
    }

    public function test_reject_validation_requires_minimum_reason_length(): void
    {
        $customer = $this->createPendingCustomer();

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/reject",
            ['rejection_reason' => 'too short']
        )->assertUnprocessable();
    }

    public function test_reject_already_rejected_customer_returns_422(): void
    {
        $customer = $this->createPendingCustomer();

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/reject",
            ['rejection_reason' => 'First rejection reason with enough detail.']
        )->assertOk();

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/reject",
            ['rejection_reason' => 'Second rejection reason with enough detail.']
        )->assertUnprocessable();
    }

    public function test_admin_can_approve_pending_customer(): void
    {
        $customer = $this->createPendingCustomer();

        $response = $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/approve"
        );

        $response->assertOk()
            ->assertJsonPath('data.status', Customer::STATUS_ACTIVE);

        $notification = DatabaseNotification::query()
            ->where('notifiable_id', $customer->id)
            ->where('data->meta->event_type', CustomerNotificationType::ProfileApproved->value)
            ->first();

        $this->assertNotNull($notification);
        Mail::assertSent(\App\Mail\CustomerApprovalMail::class);
    }

    public function test_approval_notification_appears_in_customer_inbox(): void
    {
        $customer = $this->createPendingCustomer();

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/approve"
        )->assertOk();

        $auth = app(\App\Modules\CustomerAuth\Support\CustomerJwtService::class)->createToken(
            $customer->id,
            $customer->email ?: $customer->phone,
        );

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
            'Accept' => 'application/json',
        ])->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', 'Your account is approved');
    }

    public function test_status_endpoint_pending_to_active_sends_approval_notification(): void
    {
        $customer = $this->createPendingCustomer();

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/status",
            ['status' => Customer::STATUS_ACTIVE]
        )->assertOk()
            ->assertJsonPath('data.status', Customer::STATUS_ACTIVE);

        $notification = DatabaseNotification::query()
            ->where('notifiable_id', (string) $customer->id)
            ->where('data->meta->event_type', CustomerNotificationType::ProfileApproved->value)
            ->first();

        $this->assertNotNull($notification);
        Mail::assertSent(\App\Mail\CustomerApprovalMail::class);
    }

    public function test_approve_active_customer_returns_422(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'already-active@example.com',
            'phone' => '+249911100099',
            'status' => Customer::STATUS_ACTIVE,
            'profile_completed' => true,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/approve"
        )->assertUnprocessable();
    }

    public function test_generic_status_endpoint_cannot_set_rejected(): void
    {
        $customer = $this->createPendingCustomer();

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/status",
            ['status' => Customer::STATUS_REJECTED]
        )->assertUnprocessable();
    }

    public function test_show_includes_latest_rejection_payload(): void
    {
        $customer = $this->createPendingCustomer();

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/reject",
            [
                'rejection_reason' => 'Please upload a valid profile photo for verification.',
                'invalid_fields' => ['name'],
            ]
        )->assertOk();

        $response = $this->actingAsAdminApi()->getJson("/api/v2/admin/customers/{$customer->id}");

        $response->assertOk()
            ->assertJsonPath('data.latest_rejection.rejection_reason', 'Please upload a valid profile photo for verification.')
            ->assertJsonPath('data.latest_rejection.invalid_fields.0', 'name');
    }

    public function test_reject_with_both_attachment_flags_persists_in_rejection(): void
    {
        $customer = $this->createPendingCustomer();

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/reject",
            [
                'rejection_reason' => 'Profile photo and passport must be resubmitted for verification.',
                'missing_attachments' => ['profile_image', 'passport_document'],
            ]
        )->assertOk();

        $this->assertDatabaseHas('customer_rejections', [
            'customer_id' => $customer->id,
        ]);

        $rejection = CustomerRejection::query()->where('customer_id', $customer->id)->first();
        $this->assertSame(['profile_image', 'passport_document'], $rejection->missing_attachments);
    }

    public function test_show_includes_attachments_payload(): void
    {
        $customer = $this->createPendingCustomer();

        app(\App\Modules\CustomerAuth\Services\CustomerAttachmentService::class)->uploadProfileImage(
            $customer,
            \Illuminate\Http\UploadedFile::fake()->image('profile.jpg')
        );
        app(\App\Modules\CustomerAuth\Services\CustomerAttachmentService::class)->uploadPassportDocument(
            $customer,
            \Illuminate\Http\UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf')
        );

        $response = $this->actingAsAdminApi()->getJson("/api/v2/admin/customers/{$customer->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'attachments' => [
                        ['id', 'url_type', 'url', 'created_at'],
                    ],
                ],
            ]);

        $urlTypes = collect($response->json('data.attachments'))->pluck('url_type')->all();
        $this->assertContains('profile_image', $urlTypes);
        $this->assertContains('passport_document', $urlTypes);

        $attachments = collect($response->json('data.attachments'))->keyBy('url_type');
        $this->assertStringContainsString('customer_profile_images/', $attachments->get('profile_image')['url']);
        $this->assertStringContainsString('/storage/customer_documents/', $attachments->get('passport_document')['url']);
    }

    private function createPendingCustomer(): Customer
    {
        return Customer::factory()->create([
            'email' => 'reject-test-'.uniqid().'@example.com',
            'phone' => '+24991'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_PENDING,
            'profile_completed' => true,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);
    }

    private function actingAsAdminApi(): self
    {
        Passport::actingAs($this->admin, [], 'admin-api');

        return $this->withHeaders([
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'X-App-Locale' => 'en',
        ]);
    }
}
