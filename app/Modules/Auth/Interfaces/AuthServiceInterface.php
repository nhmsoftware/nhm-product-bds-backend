<?php

namespace App\Modules\Auth\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\ChangePasswordDTO;
use App\Modules\Auth\DTO\ForgotPasswordDTO;
use App\Modules\Auth\DTO\GetRewardPointHistoryDTO;
use App\Modules\Auth\DTO\GetTeamMembersDTO;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\ResetPasswordDTO;
use App\Modules\Auth\DTO\UpdateEmployeeProfileDTO;
use App\Modules\Auth\DTO\UpdateProfileDTO;
use App\Modules\Auth\DTO\UploadEmployeeAvatarDTO;
use App\Modules\Auth\DTO\UploadEmployeeDocumentDTO;
use App\Modules\Auth\DTO\VerifyOtpDTO;

use App\Modules\Auth\DTO\GetTeamKpiDTO;
use App\Modules\Auth\DTO\GetEmployeeKpiDTO;
use App\Modules\Auth\DTO\GetDepartmentRankingDTO;
use App\Modules\Auth\DTO\UpdateFcmTokenDTO;

interface AuthServiceInterface
{
    /**
     * Đăng ký tài khoản mới.
     *
     * @param RegisterDTO $dto
     * @return ServiceReturn
     */
    public function register(RegisterDTO $dto): ServiceReturn;

    /**
     * Đăng nhập hệ thống.
     *
     * @param \App\Modules\Auth\DTO\LoginDTO $dto
     * @return ServiceReturn
     */
    public function login(\App\Modules\Auth\DTO\LoginDTO $dto): ServiceReturn;

    /**
     * Yêu cầu quên mật khẩu (Gửi OTP).
     *
     * @param ForgotPasswordDTO $dto
     * @return ServiceReturn
     */
    public function forgotPassword(ForgotPasswordDTO $dto): ServiceReturn;

    /**
     * Xác thực mã OTP.
     *
     * @param VerifyOtpDTO $dto
     * @return ServiceReturn
     */
    public function verifyOtp(VerifyOtpDTO $dto): ServiceReturn;

    /**
     * Đặt lại mật khẩu mới.
     *
     * @param ResetPasswordDTO $dto
     * @return ServiceReturn
     */
    public function resetPassword(ResetPasswordDTO $dto): ServiceReturn;

    /**
     * Đăng xuất hệ thống.
     *
     * @return ServiceReturn
     */
    public function logout(): ServiceReturn;

    /**
     * Lấy thông tin hồ sơ cá nhân của người dùng (UC-030).
     *
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getProfile(string $userId): ServiceReturn;

    /**
     * Lấy danh sách phòng ban đang hoạt động để chọn khi gửi yêu cầu nhân sự.
     *
     * @param string $userId
     * @param string|null $branchId
     * @return ServiceReturn
     */
    public function getDepartments(string $userId, ?string $branchId = null): ServiceReturn;

    /**
     * Cập nhật thông tin hồ sơ cá nhân của người dùng (UC-031).
     *
     * @param UpdateProfileDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function updateProfile(UpdateProfileDTO $dto): ServiceReturn;

    /**
     * Thay đổi mật khẩu tài khoản của người dùng (UC-032).
     *
     * @param ChangePasswordDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function changePassword(ChangePasswordDTO $dto): ServiceReturn;

    /**
     * Lấy thông tin hồ sơ nhân sự cá nhân (UC-033).
     *
     * @param string $userId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getEmployeeProfile(string $userId): ServiceReturn;

    /**
     * Cập nhật thông tin hồ sơ nhân sự cá nhân (UC-034).
     *
     * @param UpdateEmployeeProfileDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function updateEmployeeProfile(UpdateEmployeeProfileDTO $dto): ServiceReturn;

    /**
     * Cập nhật ảnh đại diện hồ sơ nhân sự.
     *
     * @param UploadEmployeeAvatarDTO $dto
     * @return ServiceReturn
     */
    public function uploadEmployeeAvatar(UploadEmployeeAvatarDTO $dto): ServiceReturn;

    /**
     * Lấy thông tin tổng quan điểm thưởng của nhân viên.
     *
     * @param string $userId
     * @return ServiceReturn
     */
    public function getRewardPointOverview(string $userId): ServiceReturn;

    /**
     * Lấy lịch sử điểm thưởng của nhân viên.
     *
     * @param GetRewardPointHistoryDTO $dto
     * @return ServiceReturn
     */
    public function getRewardPointHistory(GetRewardPointHistoryDTO $dto): ServiceReturn;

    /**
     * Lấy thông tin tổng quan phòng ban/khu vực (UC-106).
     *
     * @param string $userId
     * @return ServiceReturn
     */
    public function getTeamOverview(string $userId): ServiceReturn;

    /**
     * Lấy danh sách nhân viên trong phòng ban/khu vực (UC-106).
     *
     * @param GetTeamMembersDTO $dto
     * @return ServiceReturn
     */
    public function getTeamMembers(GetTeamMembersDTO $dto): ServiceReturn;

    /**
     * Lấy thông tin tổng quan KPI của phòng ban/khu vực (UC-107).
     *
     * @param GetTeamKpiDTO $dto
     * @return ServiceReturn
     */
    public function getTeamKpiOverview(GetTeamKpiDTO $dto): ServiceReturn;

    /**
     * Lấy bảng xếp hạng KPI của phòng ban/khu vực (UC-107).
     *
     * @param GetTeamKpiDTO $dto
     * @return ServiceReturn
     */
    public function getTeamKpiLeaderboard(GetTeamKpiDTO $dto): ServiceReturn;

    /**
     * Lấy chi tiết KPI và lịch sử điểm thưởng của một nhân viên (UC-107).
     *
     * @param GetEmployeeKpiDTO $dto
     * @return ServiceReturn
     */
    public function getEmployeeKpiDetails(GetEmployeeKpiDTO $dto): ServiceReturn;

    /**
     * Lấy bảng xếp hạng phòng ban (UC-108).
     *
     * @param GetDepartmentRankingDTO $dto
     * @return ServiceReturn
     */
    public function getDepartmentRanking(GetDepartmentRankingDTO $dto): ServiceReturn;

    /**
     * Lấy chi tiết KPI của phòng ban (UC-108).
     *
     * @param string $departmentName
     * @param GetDepartmentRankingDTO $dto
     * @return ServiceReturn
     */
    public function getDepartmentKpiDetails(string $departmentName, GetDepartmentRankingDTO $dto): ServiceReturn;

    /**
     * Tải lên tài liệu hồ sơ nhân sự (UC-035).
     *
     * @param UploadEmployeeDocumentDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function uploadEmployeeDocument(UploadEmployeeDocumentDTO $dto): ServiceReturn;

    /**
     * Cập nhật FCM Token của người dùng (Test case 5).
     *
     * @param UpdateFcmTokenDTO $dto
     * @return ServiceReturn
     */
    public function updateFcmToken(UpdateFcmTokenDTO $dto): ServiceReturn;
}
