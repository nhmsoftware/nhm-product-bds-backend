<?php

namespace App\Core\Services;

use App\Core\Logs\Logging;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    /**
     * Thực thi một hành động với cơ chế transaction (nếu cần).
     *
     * @throws \Throwable
     */
    protected function execute(
        callable $callback,
        bool $useTransaction = false,
        ?string $actionName = null,
        ?callable $catchCallback = null,
        bool $logServiceError = false,
        ?callable $afterCommitCallback = null,
        ?callable $returnCatchCallback = null
    ): ServiceReturn {
        if ($useTransaction) {
            DB::beginTransaction();
        }

        try {
            $result = $callback();

            if ($useTransaction) {
                if ($afterCommitCallback) {
                    // Đăng ký callback chạy SAU KHI commit thành công
                    DB::afterCommit($afterCommitCallback);
                }
                DB::commit();
            }

            return $result instanceof ServiceReturn ? $result : ServiceReturn::success($result);

        } catch (\Throwable $e) {
            if ($useTransaction) {
                DB::rollBack();
            }

            if ($catchCallback) {
                $catchCallback($e);
            }

            // Tự động xác định Context lỗi
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $trace[1]['function'] ?? 'unknown';
            $context = $actionName ?? static::class.'::'.$caller;

            // Phân loại lỗi để Log và Return
            if ($e instanceof ServiceException) {
                if ($logServiceError) {
                    Logging::error("[{$context}] Service Error: ".$e->getMessage(), $e);
                }

                return ServiceReturn::error(
                    message: $e->getMessage(),
                    exception: $e,
                    code: $e->getCode(),
                );
            }

            if ($returnCatchCallback) {
                $catchResult = $returnCatchCallback($e);
                // Đảm bảo kết quả luôn là ServiceReturn
                return $catchResult instanceof ServiceReturn
                    ? $catchResult
                    : ServiceReturn::error(
                        message: 'Có lỗi xảy ra. Vui lòng thử lại sau.' . ' ' . $e->getMessage(),
                        exception: $e,
                        code: 500,
                    );
            }

            // Lỗi hệ thống nghiêm trọng
            Logging::error("[{$context}] Critical Error: " . ' ' . $e->getMessage(), $e);

            return ServiceReturn::error(
                message: 'Có lỗi xảy ra. Vui lòng thử lại sau.'.$e->getMessage(),
                exception: $e,
                code: 500,
            );
        }
    }

    /**
     * Validate điều kiện — ném ServiceException nếu sai.
     * Sử dụng thay thế cho `ServiceReturn::error()` trong Service.
     *
     * @param bool   $condition    Điều kiện cần thỏa mãn. Nếu false, sế throw.
     * @param string $errorMessage Thông điệp lỗi trả về cho client.
     * @param int    $code         HTTP status code (mặc định 400). VD: 404, 403, 422.
     * @throws ServiceException
     */
    protected function validate(bool $condition, string $errorMessage, int $code = 400): void
    {
        if (!$condition) {
            throw new ServiceException($errorMessage, $code);
        }
    }
    /**
     * Ném ServiceException
     * @param string $message
     * @param int $code
     * @throws ServiceException
     */
    protected function throw(string $message, int $code = 400) {
        throw new ServiceException($message, $code);
    }

    /**
     * Trả về kết quả thành công
     * @param $data
     * @param string $message
     * @return ServiceReturn
     */
    protected function success($data = null, string $message = 'Success'): ServiceReturn {
        return ServiceReturn::success($data, $message);
    }
}
