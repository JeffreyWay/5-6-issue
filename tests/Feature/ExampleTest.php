<?php

namespace Tests\Feature;

use App\Reply;
use App\Thread;
use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ExampleTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();

        Schema::create('replies', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('thread_id');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('threads', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->unsignedInteger('best_reply_id')->nullable();
            $table->timestamps();

            $table->foreign('best_reply_id')
                ->references('id')
                ->on('replies')
                ->onDelete('set null');
        });
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_bug()
    {
        $thread = Thread::forceCreate([
            'title' => 'Foo Title'
        ]);

        $reply = Reply::forceCreate([
            'body' => 'Foo body.',
            'thread_id' => $thread->id
        ]);

        // Set the best_reply_id on the thread.
        $thread->forceFill(['best_reply_id' => $reply->id])->save();

        // Prove that it has been applied...
        $this->assertEquals($reply->id, $thread->fresh()->best_reply_id);

        // If we delete that reply, the foreign constraint from the schema above should take effect...
        $reply->delete();

        // And the best_reply_id column should be reset to null.
        // In Laravel 5.5, this test passes for all db drivers.
        // In Laravel 5.6, it works with the mysql driver, but fails when using sqlite.
        $this->assertNull($thread->fresh()->best_reply_id);
    }
}
