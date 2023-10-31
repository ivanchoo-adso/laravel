<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Sdkconsultoria\Core\Exceptions\APIException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        APIException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if (($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) && ($request->is('api/*') || $request->ajax() or $request->wantsJson())) {
            return response([
                'message' => __('core::responses.404'),
                'details' => $e->getMessage(),
            ], 404);
        }

        if ($e instanceof APIException) {
            return response()->json(json_decode($e->getMessage()), $e->getCode());
        }

        if ((($e instanceof AuthenticationException || $e instanceof RouteNotFoundException) || $e instanceof AuthorizationException) && $request->is('api/*')) {
            return response()->json([
                'message' => __('core::responses.401'),
                'code' => 401,
            ], 401);
        }

        if ($request->is('api/*') || $request->ajax() || $request->wantsJson()) {
            return response()->json(json_decode($e->getMessage()), 500);
        }

        return parent::render($request, $e);
    }
}
