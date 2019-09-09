<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Mail\SubscriberJoined;
use App\Subscriber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mail;

class ExampleTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function testItCanSendAMailWithoutPassingValues()
    {
        Mail::fake();
        Subscriber::create(['email' => 'igeoluwasegun363@gmail.com']);
        // Artisan::call('mail:send-test', [
        //     'mailableClass' => TestMailable::class,
        //     'recipient' => 'recepient@mail.com',
        // ]);
        Mail::assertSent(SubscriberJoined::class, function (SubscriberJoined $mail) {
            $this->assertCount(1, $mail->to);
            // $this->assertEquals('igeoluwasegun363@gmail.com', $mail->to[0]['address']);
            // $this->assertCount(0, $mail->cc);
            // $this->assertCount(0, $mail->bcc);
            return true;
        });
    }
}
