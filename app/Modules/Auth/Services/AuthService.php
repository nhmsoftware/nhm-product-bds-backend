<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\ChangePasswordDTO;
use App\Modules\Auth\DTO\ForgotPasswordDTO;
use App\Modules\Auth\DTO\GetDepartmentRankingDTO;
use App\Modules\Auth\DTO\GetEmployeeKpiDTO;
use App\Modules\Auth\DTO\GetRewardPointHistoryDTO;
use App\Modules\Auth\DTO\GetTeamKpiDTO;
use App\Modules\Auth\DTO\GetTeamMembersDTO;
use App\Modules\Auth\DTO\LoginDTO;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\ResetPasswordDTO;
use App\Modules\Auth\DTO\UpdateEmployeeProfileDTO;
use App\Modules\Auth\DTO\UpdateFcmTokenDTO;
use App\Modules\Auth\DTO\UpdateProfileDTO;
use App\Modules\Auth\DTO\UploadEmployeeAvatarDTO;
use App\Modules\Auth\DTO\UploadEmployeeDocumentDTO;
use App\Modules\Auth\DTO\VerifyOtpDTO;
use App\Modules\Auth\Interfaces\AuthServiceInterface;

/**
 * Thin delegator — routes each call to the appropriate sub-service.
 * The public API (AuthServiceInterface) is unchanged so all callers remain intact.
 */
final class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthCoreService $authCoreService,
        private readonly ProfileService $profileService,
        private readonly EmployeeProfileService $employeeProfileService,
        private readonly RewardPointService $rewardPointService,
        private readonly TeamService $teamService,
        private readonly KpiService $kpiService,
    ) {
    }

    // ── Auth core ─────────────────────────────────────────────

    public function register(RegisterDTO $dto): ServiceReturn
    {
        return $this->authCoreService->register($dto);
    }

    public function login(LoginDTO $dto): ServiceReturn
    {
        return $this->authCoreService->login($dto);
    }

    public function forgotPassword(ForgotPasswordDTO $dto): ServiceReturn
    {
        return $this->authCoreService->forgotPassword($dto);
    }

    public function verifyOtp(VerifyOtpDTO $dto): ServiceReturn
    {
        return $this->authCoreService->verifyOtp($dto);
    }

    public function resetPassword(ResetPasswordDTO $dto): ServiceReturn
    {
        return $this->authCoreService->resetPassword($dto);
    }

    public function logout(): ServiceReturn
    {
        return $this->authCoreService->logout();
    }

    public function updateFcmToken(UpdateFcmTokenDTO $dto): ServiceReturn
    {
        return $this->authCoreService->updateFcmToken($dto);
    }

    // ── Profile ───────────────────────────────────────────────

    public function getProfile(string $userId): ServiceReturn
    {
        return $this->profileService->getProfile($userId);
    }

    public function getDepartments(string $userId, ?string $branchId = null): ServiceReturn
    {
        return $this->profileService->getDepartments($userId, $branchId);
    }

    public function updateProfile(UpdateProfileDTO $dto): ServiceReturn
    {
        return $this->profileService->updateProfile($dto);
    }

    public function changePassword(ChangePasswordDTO $dto): ServiceReturn
    {
        return $this->profileService->changePassword($dto);
    }

    // ── Employee profile ──────────────────────────────────────

    public function getEmployeeProfile(string $userId): ServiceReturn
    {
        return $this->employeeProfileService->getEmployeeProfile($userId);
    }

    public function updateEmployeeProfile(UpdateEmployeeProfileDTO $dto): ServiceReturn
    {
        return $this->employeeProfileService->updateEmployeeProfile($dto);
    }

    public function uploadEmployeeAvatar(UploadEmployeeAvatarDTO $dto): ServiceReturn
    {
        return $this->employeeProfileService->uploadEmployeeAvatar($dto);
    }

    public function uploadEmployeeDocument(UploadEmployeeDocumentDTO $dto): ServiceReturn
    {
        return $this->employeeProfileService->uploadEmployeeDocument($dto);
    }

    // ── Reward points ─────────────────────────────────────────

    public function getRewardPointOverview(string $userId): ServiceReturn
    {
        return $this->rewardPointService->getRewardPointOverview($userId);
    }

    public function getRewardPointHistory(GetRewardPointHistoryDTO $dto): ServiceReturn
    {
        return $this->rewardPointService->getRewardPointHistory($dto);
    }

    // ── Team ──────────────────────────────────────────────────

    public function getTeamOverview(string $userId): ServiceReturn
    {
        return $this->teamService->getTeamOverview($userId);
    }

    public function getTeamMembers(GetTeamMembersDTO $dto): ServiceReturn
    {
        return $this->teamService->getTeamMembers($dto);
    }

    // ── KPI ───────────────────────────────────────────────────

    public function getTeamKpiOverview(GetTeamKpiDTO $dto): ServiceReturn
    {
        return $this->kpiService->getTeamKpiOverview($dto);
    }

    public function getTeamKpiLeaderboard(GetTeamKpiDTO $dto): ServiceReturn
    {
        return $this->kpiService->getTeamKpiLeaderboard($dto);
    }

    public function getEmployeeKpiDetails(GetEmployeeKpiDTO $dto): ServiceReturn
    {
        return $this->kpiService->getEmployeeKpiDetails($dto);
    }

    public function getDepartmentRanking(GetDepartmentRankingDTO $dto): ServiceReturn
    {
        return $this->kpiService->getDepartmentRanking($dto);
    }

    public function getDepartmentKpiDetails(string $departmentName, GetDepartmentRankingDTO $dto): ServiceReturn
    {
        return $this->kpiService->getDepartmentKpiDetails($departmentName, $dto);
    }
}
