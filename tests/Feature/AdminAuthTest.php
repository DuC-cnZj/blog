<?php

use App\Contracts\ArticleRepoImp;
use Illuminate\Http\UploadedFile;
use Laravel\Lumen\Testing\DatabaseMigrations;

class AdminAuthTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function user_can_not_login_with_incorrect_credentials()
    {
        create(\App\User::class, [
            'mobile'   => '18888780080',
            'password' => 'secret',
        ]);

        $this->json('POST', '/auth/login', [
            'mobile'   => '18888780080',
            'password' => 'error',
        ])->seeStatusCode(401)
            ->seeJson([
            'error' => [
                'code'    => 401,
                'message' => 'Unauthorized.',
            ],
        ]);
    }

    /** @test */
    public function user_can_login_with_credentials()
    {
        create(\App\User::class, [
            'mobile'   => '18888780080',
            'password' => 'secret',
        ]);

        $this->json('POST', '/auth/login', [
            'mobile'   => '18888780080',
            'password' => 'secret',
        ])->seeStatusCode(200)
            ->seeJsonStructure([
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'refresh_ttl',
            ],
        ]);
    }

    /** @test */
    public function authenticated_user_can_get_info()
    {
        $this->signIn();

        $this->post('/auth/me')->seeJsonStructure([
            'data' => [
                'access',
                'token',
                'id',
                'name',
                'email',
                'mobile',
                'avatar',
                'bio',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    /** @test */
    public function an_authenticated_user_can_update_info()
    {
        $user = $this->signIn(
            [
                'name'   => 'duc',
                'email'  => 'a.com',
                'bio'    => 'bio',
                'mobile' => '123456789',
                'avatar' => 'http://example.com/avatar.png',
            ]
        );

        $newBio = 'foobar...';
        $this->json('POST', '/admin/update_info', [
            'bio' => $newBio,
        ])->seeStatusCode(201);
        $this->assertEquals($user->fresh()->bio, $newBio);
        $this->assertNotEquals($user->fresh()->bio, 'blabla...');

        $newEmail = '1025434218@qq.com';
        $this->json('POST', '/admin/update_info', [
            'email' => $newEmail,
        ])->seeStatusCode(201);
        $this->assertEquals($user->fresh()->email, $newEmail);

        $newName = 'Mr. duc';
        $this->json('POST', '/admin/update_info', [
            'name' => $newName,
        ])->seeStatusCode(201);
        $this->assertEquals($user->fresh()->name, $newName);
        $this->assertNotEquals($user->fresh()->name, 'duc');

        $this->post('/admin/update_info', [
            'email'  => '1025434218@qq.com',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $this->assertEquals($user->fresh()->email, '1025434218@qq.com');
        $this->assertNotEquals($user->fresh()->avatar, 'http://example.com/avatar.png');
    }

    /** @test */
    public function all_articles_cache_of_authenticated_user_can_be_reset()
    {
        $user = $this->signIn(
            [
                'name'   => 'duc',
                'email'  => 'a.com',
                'bio'    => 'bio',
                'mobile' => '123456789',
                'avatar' => 'http://example.com/avatar.png',
            ]
        );

        create(\App\Article::class, ['author_id' => $user->id], 5);

        $i = 5;
        while ($i > 0) {
            $this->json('GET', "/articles/{$i}");
            $i--;
        }

        DB::enableQueryLog();
        app(ArticleRepoImp::class)->getMany(range(1, 5));
        DB::disableQueryLog();
        $this->assertEquals(0, count(DB::getQueryLog()));

        $this->post('/admin/update_info', [
            'email'  => '1025434218@qq.com',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])->seeStatusCode(201);

        DB::enableQueryLog();
        app(ArticleRepoImp::class)->getMany(range(1, 5));
        DB::disableQueryLog();
        $this->assertNotEquals(0, count(DB::getQueryLog()));
        $this->assertEquals($user->fresh()->email, '1025434218@qq.com');
        $this->assertNotEquals($user->fresh()->avatar, 'http://example.com/avatar.png');
    }
}
