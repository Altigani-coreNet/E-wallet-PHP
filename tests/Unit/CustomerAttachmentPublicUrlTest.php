<?php

namespace Tests\Unit;

use Tests\TestCase;

class CustomerAttachmentPublicUrlTest extends TestCase
{
    public function test_profile_image_public_directory_url(): void
    {
        $url = customer_attachment_public_url('customer_profile_images/avatar.jpg');

        $this->assertIsString($url);
        $this->assertStringContainsString('customer_profile_images/avatar.jpg', $url);
        $this->assertStringNotContainsString('/storage/customer_profile_images/', $url);
    }

    public function test_passport_document_uses_storage_prefix(): void
    {
        $url = customer_attachment_public_url('customer_documents/customer-id/passport.pdf');

        $this->assertIsString($url);
        $this->assertStringContainsString('/storage/customer_documents/customer-id/passport.pdf', $url);
    }

    public function test_absolute_urls_are_returned_unchanged(): void
    {
        $absolute = 'https://cdn.example.com/passport.pdf';

        $this->assertSame($absolute, customer_attachment_public_url($absolute));
    }
}
