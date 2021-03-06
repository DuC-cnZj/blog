<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use App\Jobs\RecordUser;
use Illuminate\Http\Request;
use App\Contracts\HistoryLogHandlerImp;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * Class HistoryLog
 * @package App\Http\Middleware
 */
class HistoryLog
{
    /**
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     *
     * @author duc <1025434218@qq.com>
     */
    public function handle(Request $request, \Closure $next)
    {
        return $next($request);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @author duc <1025434218@qq.com>
     */
    public function terminate(Request $request, Response $response)
    {
        /** @var HistoryLogHandlerImp $handler */
        $handler = app(HistoryLogHandlerImp::class);

        if (! $handler->shouldRecord($request)) {
            return;
        }

        $user = get_auth_user();
        $userableId = $user ? $user->id : 0;
        $userableType = $user ? get_class($user) : '';

        $data = [
            'ip'            => $request->getClientIp(),
            'url'           => $request->getPathInfo(),
            'method'        => $request->getMethod(),
            'content'       => $request->input(),
            'user_agent'    => $request->userAgent(),
            'visited_at'    => Carbon::now(),
            'userable_id'   => $userableId,
            'userable_type' => $userableType,
            'response'      => $response->getContent(),
            'status_code'   => $response->getStatusCode(),
        ];

        dispatch(new RecordUser($data));
    }
}
