<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use ModStart\Core\Exception\BizException;
use ModStart\Core\Exception\ExceptionReportHandleTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ExceptionReportHandleTrait;

    
    protected $dontReport = [
        HttpException::class,
        ModelNotFoundException::class,
        BizException::class,
    ];

    
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function report(Throwable $e)
    {
        $this->errorReportCheck($e);
        parent::report($e);
    }

    protected function renderExceptionResponse($request, Throwable $e)
    {
        $t = $this->getExceptionResponse($e);
        if (null !== $t) {
            return $t;
        }
        if (env('APP_DEBUG', true)) {
            return parent::renderExceptionResponse($request, $e);
        }
        return response()->view('errors.500', ['exception' => $e], 500);
    }

}
