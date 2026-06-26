<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\UpdateEmployeeProfileDTO;
use App\Modules\Auth\DTO\UploadEmployeeAvatarDTO;
use App\Modules\Auth\DTO\UploadEmployeeDocumentDTO;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Handles employee profile: view, update, avatar upload, document upload.
 */
final class EmployeeProfileService extends BaseService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
    ) {
    }

    public function getEmployeeProfile(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $user = $this->authRepository->findById($userId, ['*'], ['employeeProfile']);

            $this->validate($user !== null, 'Không thể tải thông tin hồ sơ. Vui lòng thử lại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            $profileData = $this->buildProfileData($user);

            return $this->success($profileData, 'Tải hồ sơ nhân viên thành công.');
        });
    }

    public function updateEmployeeProfile(UpdateEmployeeProfileDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId, ['*'], ['employeeProfile']);

            $this->validate($user !== null, 'Không thể cập nhật hồ sơ. Vui lòng thử lại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            $userData = [
                'name'    => $dto->name,
                'phone'   => $dto->phone,
                'email'   => $dto->email,
                'avatar'  => $dto->avatar,
                'address' => $dto->address,
            ];

            if ($dto->hasCccd) {
                $userData['cccd'] = $dto->cccd;
            }

            $userUpdated = $this->authRepository->updateById($dto->userId, $userData);

            $this->validate(
                $userUpdated !== false && $userUpdated !== null,
                'Không thể cập nhật hồ sơ. Vui lòng thử lại.',
                500
            );

            $epData = [
                'employee_title'      => $dto->employeeTitle,
                'dob'                 => $dto->dob,
                'bank_account_name'   => $dto->bankAccountName,
                'bank_account_number' => $dto->bankAccountNumber,
                'bank_name'           => $dto->bankName,
                'education'           => $dto->education,
                'major'               => $dto->major,
                'experience'          => $dto->experience,
            ];

            if ($dto->hasCccd) {
                $epData['identity_card'] = $dto->cccd;
            }

            if ($user->employeeProfile) {
                $epUpdated = $user->employeeProfile->update($epData);
            } else {
                $epUpdated = $user->employeeProfile()->create($epData);
            }

            $this->validate(
                $epUpdated !== false && $epUpdated !== null,
                'Không thể cập nhật hồ sơ. Vui lòng thử lại.',
                500
            );

            $freshUser = $this->authRepository->findById($dto->userId, ['*'], ['employeeProfile']);
            $profileData = $this->buildProfileData($freshUser);

            return $this->success($profileData, 'Cập nhật hồ sơ thành công.');
        }, useTransaction: true);
    }

    public function uploadEmployeeAvatar(UploadEmployeeAvatarDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate($user !== null, 'Không thể cập nhật ảnh đại diện. Vui lòng thử lại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            $path = $dto->avatar->store('avatars', 'public');
            $this->validate($path !== false && $path !== null, 'Không thể tải ảnh đại diện lên. Vui lòng thử lại.', 500);

            $avatarUrl = Storage::url($path);
            $oldAvatar = is_string($user->avatar) ? $user->avatar : '';

            $updated = $this->authRepository->updateById($dto->userId, ['avatar' => $avatarUrl]);

            $this->validate($updated !== false && $updated !== null, 'Không thể cập nhật ảnh đại diện. Vui lòng thử lại.', 500);

            $oldAvatarPath = parse_url($oldAvatar, PHP_URL_PATH);
            if (is_string($oldAvatarPath) && str_starts_with($oldAvatarPath, '/storage/avatars/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldAvatarPath));
            }

            return $this->success([
                'avatar' => $avatarUrl,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $avatarUrl,
                ],
            ], 'Cập nhật ảnh đại diện thành công.');
        }, useTransaction: true);
    }

    public function uploadEmployeeDocument(UploadEmployeeDocumentDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId, ['*'], ['employeeProfile']);

            $this->validate($user !== null, 'Không thể tải tài liệu lên. Vui lòng thử lại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            $path = $dto->file->store('employee_documents', 'public');
            $this->validate($path !== false && $path !== null, 'Không thể tải tài liệu lên. Vui lòng thử lại.', 500);

            if (!$user->employeeProfile) {
                $user->employeeProfile()->create([]);
                $user->load('employeeProfile');
            }

            $attachments = $user->employeeProfile->attachments ?? [];
            $newDocument = [
                'type' => $dto->type,
                'name' => $dto->file->getClientOriginalName(),
                'url'  => Storage::url($path),
                'created_at' => now()->toIso8601String(),
            ];
            $attachments[] = $newDocument;

            $epUpdated = $user->employeeProfile->update(['attachments' => $attachments]);

            $this->validate(
                $epUpdated !== false && $epUpdated !== null,
                'Không thể tải tài liệu lên. Vui lòng thử lại.',
                500
            );

            return $this->success([
                'document' => $newDocument,
                'list' => $attachments,
            ], 'Tải tài liệu thành công.');
        }, useTransaction: true);
    }

    private function buildProfileData(object $user): array
    {
        $ep = $user->employeeProfile;

        $hasBankDetails = $ep && $ep->bank_account_name && $ep->bank_account_number && $ep->bank_name;
        $bankMessage = $hasBankDetails ? null : 'Chưa cập nhật thông tin ngân hàng.';

        $hasEducationDetails = $ep && ($ep->education || $ep->major || $ep->experience);
        $educationMessage = $hasEducationDetails ? null : 'Chưa cập nhật thông tin học vấn hoặc kinh nghiệm.';

        $hasAttachments = $ep && !empty($ep->attachments);
        $attachmentsMessage = $hasAttachments ? null : 'Chưa có tài liệu đính kèm.';
        $identityCard = $user->cccd ?: ($ep?->identity_card ?: null);

        return [
            'user' => array_merge($user->toArray(), [
                'cccd'    => $identityCard ?: 'Chưa cập nhật.',
                'phone'   => $user->phone ?: 'Chưa cập nhật.',
                'address' => $user->address ?: 'Chưa cập nhật.',
            ]),
            'employee_details' => [
                'employee_title' => $ep?->employee_title ?: 'Chưa cập nhật.',
                'identity_card'  => $identityCard ?: 'Chưa cập nhật.',
                'dob'            => $ep?->dob ? $ep->dob->toDateString() : 'Chưa cập nhật.',
            ],
            'bank_info' => [
                'bank_account_name'   => $ep?->bank_account_name ?: 'Chưa cập nhật.',
                'bank_account_number' => $ep?->bank_account_number ?: 'Chưa cập nhật.',
                'bank_name'           => $ep?->bank_name ?: 'Chưa cập nhật.',
                'status_message'      => $bankMessage,
            ],
            'education_experience' => [
                'education'      => $ep?->education ?: 'Chưa cập nhật.',
                'major'          => $ep?->major ?: 'Chưa cập nhật.',
                'experience'     => $ep?->experience ?: 'Chưa cập nhật.',
                'status_message' => $educationMessage,
            ],
            'attachments' => [
                'list'           => $ep?->attachments ?: [],
                'status_message' => $attachmentsMessage,
            ],
        ];
    }
}
