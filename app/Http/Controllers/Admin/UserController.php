<?php

namespace App\Http\Controllers\Admin;

use App\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

/**
 * Class UserController
 * @package App\Http\Controllers\Admin
 */
class UserController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @author duc <1025434218@qq.com>
     */
    public function index(Request $request)
    {
        $users = User::query()
            ->latest()
            ->paginate($request->input('page_size') ?? 10);

        return UserResource::collection($users);
    }

    /**
     * @param Request $request
     * @return UserResource
     * @throws \Illuminate\Validation\ValidationException
     *
     * @author duc <1025434218@qq.com>
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'     => 'required',
            'email'    => ['required', Rule::unique('users'), 'email'],
            'mobile'   => ['required', Rule::unique('users'), 'regex:"^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\\d{8}$"'],
            'password' => 'required',
            'avatar'   => 'nullable',
        ], [
            'mobile.regex' => '手机号格式不正确！',
        ]);

        $attributes = $request->only('name', 'email', 'mobile', 'bio', 'password');

        if ($request->has('avatar')) {
            $image = $request->avatar;
            $folder = base_path('public/images');
            $filename = date('Y_m_d', time()) . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

            if (! app()->environment('testing')) {
                $image->move($folder, $filename);
            }

            $attributes['avatar'] = (new \Laravel\Lumen\Routing\UrlGenerator(app()))->asset('images/' . $filename);
        }

        $user = User::query()->create($attributes);

        return new UserResource($user);
    }

    /**
     * @param int $id
     *
     * @return UserResource
     *
     * @author duc <1025434218@qq.com>
     */
    public function show(int $id)
    {
        return new UserResource(
            User::query()->findOrFail($id, ['id', 'name', 'email', 'avatar', 'bio'])
        );
    }

    /**
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     *
     * @author duc <1025434218@qq.com>
     */
    public function update(int $id, Request $request)
    {
        $this->validate($request, [
            'name'     => 'string',
            'email'    => ['email', Rule::unique('users')->ignore($id)],
            'bio'      => 'string',
        ]);

        $user = User::query()->findOrFail($id);

        $this->authorize('own', $user);

        $user->update($request->only('name', 'email', 'bio'));

        return response('', 204);
    }

    /**
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     *
     * @author duc <1025434218@qq.com>
     */
    public function destroy(int $id)
    {
        if ($id === 1) {
            return $this->fail('超级管理员不能删除', 403);
        }

        $user = User::query()->findOrFail($id);

        $this->authorize('own', $user);

        $user->delete();

        return response('', 204);
    }
}
