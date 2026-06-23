<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_register()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'mobile' => '+971512345678',
            'device' => [
                'device_id' => 'test_device_123',
                'manufacturer' => 'Test Manufacturer',
                'model' => 'Test Model',
                'serial_no' => 'SN:123456789',
            ],
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'mobile',
                            'profile_image',
                            'is_approved',
                        ],
                        'token',
                        'token_type',
                    ],
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'mobile' => '+971512345678',
            'device_id' => 'test_device_123',
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_approved' => true,
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_id' => 'test_device_123',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'mobile',
                            'profile_image',
                            'is_approved',
                        ],
                        'token',
                        'token_type',
                    ],
                ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_id' => 'test_device_123',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'status' => false,
                    'message' => 'Invalid credentials',
                ]);
    }

    public function test_user_cannot_login_if_not_approved()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_approved' => false,
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_id' => 'test_device_123',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(403)
                ->assertJson([
                    'status' => false,
                    'message' => 'Account is not approved',
                ]);
    }

    public function test_user_can_get_profile()
    {
        $user = User::factory()->create([
            'is_approved' => true,
        ]);

        $token = $user->createToken('API Token')->accessToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/profile');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'mobile',
                            'phone',
                            'gender',
                            'profile_image',
                            'is_approved',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create([
            'is_approved' => true,
        ]);

        $token = $user->createToken('API Token')->accessToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => [],
                ]);

        // Verify token is revoked
        $this->assertDatabaseMissing('oauth_access_tokens', [
            'id' => $token,
        ]);
    }
} 