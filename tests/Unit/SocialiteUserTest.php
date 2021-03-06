<?php

namespace Tests\Feature;

use TestCase;
use App\Comment;
use App\SocialiteUser;
use Laravel\Lumen\Testing\DatabaseMigrations;

class SocialiteUserTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function socialite_user_has_comments()
    {
        $user = create(SocialiteUser::class, ['name'=>'duc']);
        $comment = create(Comment::class, ['userable_id' => $user->id, 'userable_type'=>get_class($user)]);

        $this->assertInstanceOf(SocialiteUser::class, $comment->userable);
        $this->assertEquals('duc', $comment->userable->name);
    }

    /** @test */
    public function it_has_histories()
    {
        $user = create(\App\SocialiteUser::class);
        create(\App\History::class, ['userable_id' => $user->id, 'userable_type' => get_class($user)], 3);

        $this->assertEquals(3, $user->histories->count());
    }
}
