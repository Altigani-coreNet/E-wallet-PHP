<?php



namespace App\Modules\CustomerAuth\Services;



use App\Models\ChangeRequest;

use App\Models\Customer;

use App\Models\CustomerRejection;

use App\Modules\AdminKyc\Notifications\AdminKycNotificationType;

use App\Modules\AdminKyc\Services\AdminKycNotificationService;

use App\Modules\CustomerAuth\Resources\CustomerAuthResource;

use App\Services\CustomerService;

use App\Support\CustomerEventMessageBuilder;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;



class CustomerProfileService

{

    /** @var array<string, string> */

    private const FIELD_COLUMN_MAP = [

        'name' => 'name',

        'email' => 'email',

        'phone' => 'phone',

        'national_id' => 'national_id',

        'birth_date' => 'birth_date',

        'gender' => 'gender',

        'country' => 'country_id',

        'city' => 'city_id',

    ];



    public function __construct(

        private readonly CustomerAttachmentService $customerAttachmentService,

        private readonly CustomerService $customerService,

        private readonly AdminKycNotificationService $adminKycNotificationService,

    ) {}



    public function getRejectedFields(Customer $customer): array

    {

        if ($customer->status !== Customer::STATUS_REJECTED) {

            throw new BadRequestHttpException('Customer is not in rejected status');

        }



        $rejection = CustomerRejection::query()

            ->where('customer_id', $customer->id)

            ->latest()

            ->first();



        if (! $rejection) {

            throw new NotFoundHttpException('No rejection information found');

        }



        $customer->load(['country', 'city', 'wallet', 'attachments']);



        return [

            'customer' => CustomerAuthResource::make($customer)->resolve(),

            'attachments' => $this->customerAttachmentService->getAttachmentUrls($customer),

            'rejection' => [

                'id' => $rejection->id,

                'rejection_reason' => $rejection->rejection_reason,

                'invalid_fields' => $rejection->invalid_fields ?? [],

                'missing_attachments' => $rejection->missing_attachments ?? [],

                'created_at' => $rejection->created_at?->toIso8601String(),

            ],

        ];

    }



    public function updateRejectedFields(Customer $customer, array $data, Request $request): array

    {

        $rejection = CustomerRejection::query()

            ->where('customer_id', $customer->id)

            ->latest()

            ->first();



        if (! $rejection) {

            throw new NotFoundHttpException('No rejection information found');

        }



        return DB::transaction(function () use ($customer, $rejection, $data, $request) {

            $payload = $this->buildRejectedFieldPayload($rejection, $data);

            $missingAttachments = $rejection->missing_attachments ?? [];

            $beforeValues = $customer->only(array_keys($payload));

            if (! empty($payload)) {

                if (isset($payload['email'])) {

                    $this->assertEmailAvailable($payload['email'], $customer->id);

                }



                if (isset($payload['national_id'])) {

                    $this->assertNationalIdAvailable($payload['national_id'], $customer->id);

                }



                $customer->update($payload);

            }



            $this->customerAttachmentService->processMissingAttachmentUploads(

                $customer,

                $missingAttachments,

                $request,

            );



            $customer->update(['status' => Customer::STATUS_PENDING]);

            $uploadedAttachments = array_values(array_filter(
                $missingAttachments,
                fn (string $key) => $request->hasFile($key),
            ));

            $this->customerService->logCustomerEvent(
                $customer->fresh(['country', 'city', 'wallet', 'attachments']),
                'profile_resubmitted',
                [
                    'message' => CustomerEventMessageBuilder::profileResubmitted(
                        $beforeValues,
                        $payload,
                        $uploadedAttachments,
                    ),
                    'performed_by' => $customer->name ?: $customer->phone,
                ],
                $beforeValues,
                $payload,
            );

            $this->adminKycNotificationService->send(
                $customer->fresh(['country', 'city', 'wallet', 'attachments']),
                AdminKycNotificationType::CustomerProfileResubmitted,
            );

            $customer->load(['country', 'city', 'wallet', 'attachments']);



            return [

                'profile_completed' => (bool) $customer->profile_completed,

                'customer' => CustomerAuthResource::make($customer->fresh(['country', 'city', 'wallet', 'attachments']))->resolve(),

            ];

        });

    }



    public function hasPendingChangeRequest(Customer $customer): bool

    {

        return ChangeRequest::query()

            ->where('changeable_type', Customer::class)

            ->where('changeable_id', $customer->id)

            ->where('status', 'pending')

            ->exists();

    }



    public function latestRejection(Customer $customer): ?CustomerRejection

    {

        if ($customer->status !== Customer::STATUS_REJECTED) {

            return null;

        }



        return CustomerRejection::query()

            ->where('customer_id', $customer->id)

            ->latest()

            ->first();

    }



    /**

     * @return array<string, mixed>

     */

    private function buildRejectedFieldPayload(CustomerRejection $rejection, array $data): array

    {

        $payload = [];

        $invalidFields = $rejection->invalid_fields ?? [];



        foreach ($invalidFields as $field) {

            $column = self::FIELD_COLUMN_MAP[$field] ?? $field;



            if (array_key_exists($field, $data)) {

                $payload[$column] = $data[$field];

            } elseif (array_key_exists($column, $data)) {

                $payload[$column] = $data[$column];

            }

        }



        return $payload;

    }



    private function assertEmailAvailable(string $email, string $customerId): void

    {

        if (Customer::query()->where('email', $email)->where('id', '!=', $customerId)->exists()) {

            throw new ConflictHttpException('Email is already in use');

        }

    }



    private function assertNationalIdAvailable(string $nationalId, string $customerId): void

    {

        if (Customer::query()->where('national_id', $nationalId)->where('id', '!=', $customerId)->exists()) {

            throw new ConflictHttpException('National ID is already in use');

        }

    }

}


