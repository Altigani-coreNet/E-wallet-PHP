<?php

namespace App\Services;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\UsersOtp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

interface UserService
{
    public function createUser($request);

    public function getProfile(int $id);

    public function updateUser(UserRequest $request, User $user): bool;

    public function deleteUser(User $user): bool;

    public function data(Request $request): JsonResponse;

    public function changeStatus(User $user): RedirectResponse;

    public function show(User $user): View;

    public function getUsersSections(User $user, string $type = 'overview');

    public function showWithTab(User $user, string $tab = 'overview'): View;

    public function CheckIfUserIsExistAndGenerateOtp(string $phone): UsersOtp|bool;

    public function getUserDataWithOtp(\App\Http\Requests\VerifiedRequest $request): ?User;

    public function mapFromJsonToUserData(array $userData);

    public function mapUserLinksFromJson(array $userdata);

    public function getCompanyInCategories(array $preference);

    public function GenerateOtpToNewUser(Request $request): UsersOtp;

    public function VerifyTheOtp(Request $request): string|null;

    public function resetUserToNormalType(int $user_id);

    public function updateUserProfileImage(Request $request, int $userId);

    // public function getUserIndexData(): array;

    public function getCityFromGeoLocation(UserRequest $request);

    public function exportTemplate();

    public function export(Request $request);

    public function bulkDelete(array $ids);

    public function importPreview($file, $merchantId);

    public function import($file, $merchantId);

    /**
     * Update authenticated user's basic profile info and image
     */
    public function updateProfile(Request $request, User $user): bool;

    /**
     * Change authenticated user's password
     */
    public function changePassword(User $user, string $newPassword): bool;

}
