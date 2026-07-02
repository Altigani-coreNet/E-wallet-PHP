<?php

namespace App\Modules\CustomerAuth\Services;

use App\Models\Attachments;
use App\Models\Customer;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class CustomerAttachmentService
{
    use HasFiles;

    public const URL_TYPE_PROFILE_IMAGE = 'profile_image';

    public const URL_TYPE_PASSPORT_DOCUMENT = 'passport_document';

    public const MISSING_ATTACHMENT_PICTURE = 'picture';

    public const MISSING_ATTACHMENT_PASSPORT = 'passport';

    /** @var list<string> */
    public const ALLOWED_MISSING_ATTACHMENTS = [
        self::MISSING_ATTACHMENT_PICTURE,
        self::MISSING_ATTACHMENT_PASSPORT,
    ];

    /** @var array<string, string> */
    private const LEGACY_MISSING_ATTACHMENT_TO_API_KEY = [
        self::URL_TYPE_PROFILE_IMAGE => self::MISSING_ATTACHMENT_PICTURE,
        self::URL_TYPE_PASSPORT_DOCUMENT => self::MISSING_ATTACHMENT_PASSPORT,
    ];

    /** @var array<string, string> */
    private const MISSING_ATTACHMENT_API_KEY_TO_URL_TYPE = [
        self::MISSING_ATTACHMENT_PICTURE => self::URL_TYPE_PROFILE_IMAGE,
        self::MISSING_ATTACHMENT_PASSPORT => self::URL_TYPE_PASSPORT_DOCUMENT,
    ];

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function normalizeMissingAttachmentsList(array $keys): array
    {
        return array_values(array_unique(array_map(
            fn (string $key) => self::normalizeMissingAttachmentKey($key),
            $keys,
        )));
    }

    public static function normalizeMissingAttachmentKey(string $key): string
    {
        if (isset(self::LEGACY_MISSING_ATTACHMENT_TO_API_KEY[$key])) {
            return self::LEGACY_MISSING_ATTACHMENT_TO_API_KEY[$key];
        }

        if (in_array($key, self::ALLOWED_MISSING_ATTACHMENTS, true)) {
            return $key;
        }

        throw new \InvalidArgumentException('Invalid attachment key: '.$key);
    }

    public static function missingAttachmentKeyToUrlType(string $key): string
    {
        $apiKey = self::normalizeMissingAttachmentKey($key);

        return self::MISSING_ATTACHMENT_API_KEY_TO_URL_TYPE[$apiKey];
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function missingAttachmentsToUrlTypes(array $keys): array
    {
        return array_map(
            fn (string $key) => self::missingAttachmentKeyToUrlType($key),
            self::normalizeMissingAttachmentsList($keys),
        );
    }

    private const PROFILE_IMAGE_DIR = 'customer_profile_images';

    private const DOCUMENTS_DIR = 'customer_documents';

    /**
     * @return list<string>
     */
    public function resolveRejectUploadKeys(string $missingAttachment): array
    {
        return match ($missingAttachment) {
            self::URL_TYPE_PASSPORT_DOCUMENT => ['passport'],
            self::URL_TYPE_PROFILE_IMAGE => ['picture', 'profile_image'],
            default => [],
        };
    }

    public function requestHasRejectUpload(Request $request, string $missingAttachment): bool
    {
        foreach ($this->resolveRejectUploadKeys($missingAttachment) as $key) {
            if ($request->hasFile($key)) {
                return true;
            }
        }

        return false;
    }

    public function uploadProfileImageFromRequest(Request $request, Customer $customer, string $fileKey = 'picture'): string
    {
        if (! $request->hasFile($fileKey)) {
            throw new \InvalidArgumentException('Profile image file is required.');
        }

        return $this->uploadProfileImage($customer, $request->file($fileKey));
    }

    public function uploadProfileImage(Customer $customer, UploadedFile $file): string
    {
        if ($customer->profile_image) {
            $oldImagePath = public_path($customer->profile_image);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        $directory = public_path(self::PROFILE_IMAGE_DIR);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $imageName = uniqid('', true).'.'.$file->getClientOriginalExtension();
        $file->move($directory, $imageName);
        $path = self::PROFILE_IMAGE_DIR.'/'.$imageName;

        $customer->update(['profile_image' => $path]);
        $this->upsertAttachment($customer, self::URL_TYPE_PROFILE_IMAGE, $path, $file);

        return $path;
    }

    public function uploadPassportDocumentFromRequest(Request $request, Customer $customer): Attachments
    {
        if (! $request->hasFile('passport')) {
            throw new \InvalidArgumentException('Passport document is required.');
        }

        return $this->uploadPassportDocument($customer, $request->file('passport'));
    }

    public function uploadPassportDocument(Customer $customer, UploadedFile $file): Attachments
    {
        $path = $file->store(self::DOCUMENTS_DIR.'/'.$customer->id, 'public');

        return $this->upsertAttachment($customer, self::URL_TYPE_PASSPORT_DOCUMENT, $path, $file);
    }

    public function storePassportForChangeRequest(Customer $customer, UploadedFile $file): string
    {
        return $file->store(self::DOCUMENTS_DIR.'/'.$customer->id.'/pending', 'public');
    }

    public function syncAttachmentFromPath(Customer $customer, string $urlType, string $path): void
    {
        if ($urlType === self::URL_TYPE_PROFILE_IMAGE) {
            if ($customer->profile_image && $customer->profile_image !== $path) {
                $oldImagePath = public_path($customer->profile_image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $customer->update(['profile_image' => $path]);
        }

        $attachment = $customer->attachments()
            ->where('url_type', $urlType)
            ->latest()
            ->first();

        if ($attachment) {
            $attachment->update([
                'url' => $path,
                'type' => $this->checkFileType($path),
            ]);

            return;
        }

        $customer->attachments()->create([
            'url' => $path,
            'url_type' => $urlType,
            'type' => $this->checkFileType($path),
        ]);
    }

    /**
     * @return array{profile_image: ?string, passport: ?string}
     */
    public function getAttachmentUrls(Customer $customer): array
    {
        $customer->loadMissing('attachments');

        $profileAttachment = $customer->attachments
            ->where('url_type', self::URL_TYPE_PROFILE_IMAGE)
            ->sortByDesc('created_at')
            ->first();

        $passportAttachment = $customer->attachments
            ->where('url_type', self::URL_TYPE_PASSPORT_DOCUMENT)
            ->sortByDesc('created_at')
            ->first();

        $profileUrl = $customer->getProfileImageApi();
        if (! $profileUrl && $profileAttachment) {
            $profileUrl = $this->resolvePublicUrl($profileAttachment->url);
        }

        return [
            'profile_image' => $profileUrl,
            'passport' => $passportAttachment
                ? $this->resolvePublicUrl($passportAttachment->url)
                : null,
        ];
    }

    /**
     * @return list<array{id: string, url_type: string, url: string, created_at: ?string}>
     */
    public function getAttachmentsForAdmin(Customer $customer): array
    {
        $customer->loadMissing('attachments');

        return $customer->attachments
            ->whereIn('url_type', [self::URL_TYPE_PROFILE_IMAGE, self::URL_TYPE_PASSPORT_DOCUMENT])
            ->sortByDesc('created_at')
            ->unique('url_type')
            ->values()
            ->map(fn (Attachments $attachment) => [
                'id' => $attachment->id,
                'url_type' => $attachment->url_type,
                'url' => $this->resolvePublicUrl($attachment->url),
                'created_at' => $attachment->created_at?->toIso8601String(),
            ])
            ->all();
    }

    public function processMissingAttachmentUploads(Customer $customer, array $missingAttachments, Request $request): void
    {
        foreach ($missingAttachments as $attachmentKey) {
            if ($attachmentKey === self::URL_TYPE_PROFILE_IMAGE && $this->requestHasRejectUpload($request, $attachmentKey)) {
                $fileKey = $request->hasFile('picture') ? 'picture' : 'profile_image';
                $this->uploadProfileImageFromRequest($request, $customer, $fileKey);
            }

            if ($attachmentKey === self::URL_TYPE_PASSPORT_DOCUMENT && $request->hasFile('passport')) {
                $this->uploadPassportDocumentFromRequest($request, $customer);
            }
        }
    }

    private function upsertAttachment(
        Customer $customer,
        string $urlType,
        string $path,
        UploadedFile $file,
    ): Attachments {
        $attachment = $customer->attachments()
            ->where('url_type', $urlType)
            ->latest()
            ->first();

        if ($attachment) {
            $attachment->update([
                'url' => $path,
                'type' => $this->checkFileType($path),
            ]);

            return $attachment->fresh();
        }

        return $customer->attachments()->create([
            'url' => $path,
            'url_type' => $urlType,
            'type' => $this->checkFileType($path),
        ]);
    }

    private function resolvePublicUrl(?string $path): ?string
    {
        if (function_exists('customer_attachment_public_url')) {
            return customer_attachment_public_url($path);
        }

        if (! $path) {
            return null;
        }

        return asset($path);
    }
}
