<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Branch\Models\Branch;
use App\Modules\Recruitment\Models\RecruitmentApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RecruitmentApplicationController extends BaseController
{
    /**
     * Lấy danh sách chi nhánh phục vụ việc chọn chi nhánh ứng tuyển.
     */
    public function getBranches(): JsonResponse
    {
        $branches = Branch::select('id', 'name')->get();
        return response()->json([
            'data' => $branches
        ]);
    }

    /**
     * Lấy danh sách vị trí ứng tuyển.
     */
    public function getPositions(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'value' => UserRole::EMPLOYEE->value,
                    'label' => 'Nhân viên'
                ],
                [
                    'value' => UserRole::MANAGER->value,
                    'label' => 'Trưởng phòng'
                ],
                [
                    'value' => UserRole::DIRECTOR->value,
                    'label' => 'Giám đốc'
                ]
            ]
        ]);
    }

    /**
     * Đăng ký ứng tuyển nội bộ.
     */
    public function apply(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'applied_position' => 'required|in:1,2,3',
            'applied_branch_id' => 'required|exists:branches,id',
            'education' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'introduction' => 'nullable|string',
            'profile_url' => 'nullable|string|max:255',
            'cv' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240', // 10MB
        ], [
            'applied_position.required' => 'Vui lòng chọn vị trí ứng tuyển.',
            'applied_position.in' => 'Vui lòng chọn vị trí hợp lệ.',
            'applied_branch_id.required' => 'Vui lòng chọn chi nhánh ứng tuyển.',
            'applied_branch_id.exists' => 'Chi nhánh không hợp lệ.',
            'cv.mimes' => 'Định dạng CV không hợp lệ (hỗ trợ PDF, DOC, DOCX, JPG, PNG).',
            'cv.max' => 'CV có dung lượng không vượt quá 10MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = auth()->id();

        // Kiểm tra xem đã có đơn đang chờ duyệt hay chưa
        $existing = RecruitmentApplication::where('user_id', $userId)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Bạn đang có một hồ sơ ứng tuyển đang chờ duyệt.'
            ], 400);
        }

        $cvUrl = null;
        if ($request->hasFile('cv')) {
            $cvUrl = $request->file('cv')->store('recruitment_cvs', 'public');
        }

        $application = RecruitmentApplication::create([
            'user_id' => $userId,
            'applied_position' => (int) $request->input('applied_position'),
            'applied_branch_id' => $request->input('applied_branch_id'),
            'education' => $request->input('education'),
            'experience' => $request->input('experience'),
            'introduction' => $request->input('introduction'),
            'profile_url' => $request->input('profile_url'),
            'cv_url' => $cvUrl,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Nộp đơn ứng tuyển thành công, vui lòng chờ duyệt.',
            'data' => $application
        ], 201);
    }

    /**
     * Danh sách đơn ứng tuyển dành cho Director / Admin.
     */
    public function indexApplications(Request $request): JsonResponse
    {
        $currentUser = auth()->user();
        $query = RecruitmentApplication::with(['user', 'appliedBranch']);

        // Nếu là Director, chỉ lấy đơn ứng tuyển của chi nhánh mình quản lý
        if ($currentUser->role === UserRole::DIRECTOR) {
            if (!$currentUser->branch_id) {
                return response()->json(['data' => []]);
            }
            $query->where('applied_branch_id', $currentUser->branch_id);
        } elseif ($currentUser->role === UserRole::MANAGER) {
            // Manager không có quyền xem
            return response()->json(['data' => []], 403);
        }

        $applications = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $applications
        ]);
    }

    /**
     * Chi tiết đơn ứng tuyển.
     */
    public function showApplication($id): JsonResponse
    {
        $currentUser = auth()->user();
        $application = RecruitmentApplication::with(['user', 'appliedBranch', 'approver'])->find($id);

        if (!$application) {
            return response()->json(['message' => 'Không tìm thấy đơn ứng tuyển.'], 404);
        }

        if ($currentUser->role === UserRole::DIRECTOR) {
            if ($application->applied_branch_id !== $currentUser->branch_id) {
                return response()->json(['message' => 'Không có quyền truy cập đơn ứng tuyển này.'], 403);
            }
        } elseif ($currentUser->role === UserRole::MANAGER) {
            return response()->json(['message' => 'Không có quyền truy cập.'], 403);
        }

        return response()->json([
            'data' => $application
        ]);
    }

    /**
     * Xử lý duyệt hoặc từ chối đơn ứng tuyển.
     */
    public function processApplication(Request $request, $id): JsonResponse
    {
        $currentUser = auth()->user();
        $application = RecruitmentApplication::find($id);

        if (!$application) {
            return response()->json(['message' => 'Không tìm thấy đơn ứng tuyển.'], 404);
        }

        if ($currentUser->role === UserRole::DIRECTOR) {
            if ($application->applied_branch_id !== $currentUser->branch_id) {
                return response()->json(['message' => 'Không có quyền xử lý đơn ứng tuyển này.'], 403);
            }
        } elseif ($currentUser->role !== UserRole::SUPER_ADMIN && $currentUser->role !== UserRole::CEO) {
            return response()->json(['message' => 'Không có quyền xử lý đơn ứng tuyển.'], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json(['message' => 'Đơn ứng tuyển này đã được xử lý trước đó.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'rejected_reason' => 'required_if:status,rejected|string|nullable',
        ], [
            'status.required' => 'Trạng thái xử lý là bắt buộc.',
            'status.in' => 'Trạng thái xử lý không hợp lệ.',
            'rejected_reason.required_if' => 'Vui lòng cung cấp lý do từ chối.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $status = $request->input('status');

        if ($status === 'approved') {
            $application->status = 'approved';
            
            // Cập nhật vai trò và chi nhánh cho ứng viên
            $user = $application->user;
            if ($user) {
                $user->role = $application->applied_position;
                $user->branch_id = $application->applied_branch_id;
                $user->save();
            }
        } else {
            $application->status = 'rejected';
            $application->rejected_reason = $request->input('rejected_reason');
        }

        $application->approved_by = $currentUser->id;
        $application->processed_at = now();
        $application->save();

        $actionText = $status === 'approved' ? 'phê duyệt' : 'từ chối';

        return response()->json([
            'message' => "Đã {$actionText} đơn ứng tuyển thành công.",
            'data' => $application
        ]);
    }
}
