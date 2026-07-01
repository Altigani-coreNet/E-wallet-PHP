<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Support\CustomerEventMessageBuilder;
use Tests\TestCase;

class CustomerEventMessageBuilderTest extends TestCase
{
    public function test_rejected_message_lists_flagged_fields_and_reason(): void
    {
        $message = CustomerEventMessageBuilder::rejected(
            'Name must be corrected.',
            ['name', 'email'],
            ['passport_document'],
            'Admin User',
        );

        $this->assertStringContainsString('Admin User rejected KYC profile', $message);
        $this->assertStringContainsString('Incorrect fields: Name and Email', $message);
        $this->assertStringContainsString('Missing documents: Passport Document', $message);
        $this->assertStringContainsString('Name must be corrected.', $message);
    }

    public function test_change_request_submitted_describes_field_transition(): void
    {
        $customer = new Customer([
            'name' => 'Ahmed',
            'birth_date' => '1990-05-15',
            'gender' => 'male',
        ]);

        $message = CustomerEventMessageBuilder::changeRequestSubmitted($customer, [
            'name' => 'Corrected Name',
            'birth_date' => '1991-07-14',
        ]);

        $this->assertStringContainsString('Customer requested to update profile', $message);
        $this->assertStringContainsString('Name (Ahmed → Corrected Name)', $message);
        $this->assertStringContainsString('Date of Birth (1990-05-15 → 1991-07-14)', $message);
    }

    public function test_password_changed_message(): void
    {
        $this->assertSame(
            'Ahmed changed account password.',
            CustomerEventMessageBuilder::passwordChanged('Ahmed'),
        );
    }
}
