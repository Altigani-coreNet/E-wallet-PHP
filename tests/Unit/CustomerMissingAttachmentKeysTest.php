<?php

namespace Tests\Unit;

use App\Modules\CustomerAuth\Services\CustomerAttachmentService;
use Tests\TestCase;

class CustomerMissingAttachmentKeysTest extends TestCase
{
    public function test_normalize_legacy_attachment_keys_to_api_keys(): void
    {
        $this->assertSame(
            ['picture', 'passport'],
            CustomerAttachmentService::normalizeMissingAttachmentsList([
                'profile_image',
                'passport_document',
            ]),
        );
    }

    public function test_api_keys_map_to_attachment_url_types(): void
    {
        $this->assertSame(
            CustomerAttachmentService::URL_TYPE_PROFILE_IMAGE,
            CustomerAttachmentService::missingAttachmentKeyToUrlType('picture'),
        );
        $this->assertSame(
            CustomerAttachmentService::URL_TYPE_PASSPORT_DOCUMENT,
            CustomerAttachmentService::missingAttachmentKeyToUrlType('passport'),
        );
    }
}
