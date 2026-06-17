<?php

namespace App\Modules\Leave\Http\Requests;

use App\Core\Traits\HandleApi;
use App\Modules\Leave\Enums\LeaveType;
use Illuminate\Foundation\Http\FormRequest;

final class CreateLeaveRequest extends FormRequest
{
    use HandleApi;

    /**
     * Xác thực quyền của người dùng đối với request này.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Các quy tắc xác thực dữ liệu gửi đơn xin nghỉ phép.
     *
     * @return array
     */
    public function rules(): array
    {
        $validLeaveTypes = [];
        foreach (LeaveType::cases() as $case) {
            $validLeaveTypes[] = $case->value;
            $validLeaveTypes[] = (string) $case->value;
            $validLeaveTypes[] = $case->serialize();
        }
        $types = implode(',', $validLeaveTypes);
        return [
            'leave_type' => ['required', "in:{$types}"],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5'],
        ];
    }

    /**
     * Các thông điệp lỗi tùy chỉnh khi xác thực thất bại.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // A1: Employee không nhập đầy đủ thông tin
            'leave_type.required' => 'Vui lòng nhập đầy đủ thông tin nghỉ phép.',
            'leave_type.in' => 'Loại nghỉ phép không hợp lệ.',
            'start_date.required' => 'Vui lòng nhập đầy đủ thông tin nghỉ phép.',
            'end_date.required' => 'Vui lòng nhập đầy đủ thông tin nghỉ phép.',
            'reason.required' => 'Vui lòng nhập đầy đủ thông tin nghỉ phép.',
            'reason.min' => 'Lý do nghỉ phép phải có ít nhất 5 ký tự.',
            
            // A2: Ngày nghỉ không hợp lệ
            'start_date.date_format' => 'Ngày nghỉ không hợp lệ.',
            'start_date.after_or_equal' => 'Ngày bắt đầu nghỉ không được nằm trong quá khứ.',
            'end_date.date_format' => 'Ngày nghỉ không hợp lệ.',
            
            // A3: Ngày kết thúc nhỏ hơn ngày bắt đầu
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
        ];
    }
}
