<?php

namespace Mesak\LaravelApiResponse\Http\Controllers;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function callAction($method, $parameters)
    {
        $result =  $this->{$method}(...array_values($parameters));
        if ($result instanceof SymfonyResponse) {
            return $result;
        }
        // Illuminate\Contracts\Routing\ResponseFactory
        $httpStatus = SymfonyResponse::HTTP_OK;
        if (!config('api-response.everything_is_ok', true)) {
            $httpStatus = ($method  === 'store') ?
                SymfonyResponse::HTTP_CREATED :
                SymfonyResponse::HTTP_OK;
        }
        return response()->success($result, $httpStatus);
    }
}
