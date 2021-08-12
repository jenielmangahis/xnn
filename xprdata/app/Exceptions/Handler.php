<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
        \Commissions\Exceptions\CommissionException::class,
        \Commissions\Exceptions\HyperwalletException::class,
        \Commissions\Exceptions\AlertException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        $exception = $this->prepareException($exception);

        if($request->expectsJson() && $exception instanceof ValidationException)
        {
            return response()->json([
                'message' => 'Validation error',
                'type' => 'ValidationException',
                'info' => [
                    'errors' => $exception->validator->errors()->getMessages()
                ]
            ], 422);
        }
        elseif($request->expectsJson() && $exception instanceof ModelNotFoundException)
        {
            return response()->json([
                'message' => 'Entry for '.str_replace('App\\', '', $exception->getModel()).' not found',
                'type' => 'ModelNotFoundException',
            ], 404);
        }
        elseif($request->expectsJson() && $exception instanceof \Commissions\Exceptions\AlertException)
        {
            return response()->json([
                'message' => $exception->getMessage(),
                'type' => 'AlertException',
                'info' => [
                    'alert_type' => $exception->getErrorType(),
                    'data' => $exception->getData(),
                ]
            ], 400);
        }
        elseif ($request->expectsJson())
        {
            $response = [];

            $statusCode = 500;
            if (method_exists($exception, 'getStatusCode')) {
                $statusCode = $exception->getStatusCode();
            }

            $response['type'] = get_class($exception);

            switch ($statusCode) {
                case 404:
                    $response['message'] = 'Not Found';
                    break;

                case 403:
                    $response['message'] = 'Forbidden';
                    break;

                default:
                    $response['message'] = $exception->getMessage();
                    break;
            }

            if (config('app.debug')) {
                $response['trace'] = $exception->getTrace();
                $response['code'] = $exception->getCode();
            }

            return response()->json($response, $statusCode);
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }
}
