<?php

namespace Tests\Unit;

use App\Jobs\SendSubscriptionConfirmation;
use Tests\TestCase;
use App\Mail\SubscriberJoined;
use App\Subscriber;
use Mail;
use Artisan;
use Exception;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;
use Illuminate\Http\Response;

class WaitListTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function people_can_see_landing_page()
    {
        $response = $this->get(route('waitlist'));
        $response->assertStatus(200);
        $response->assertViewIs('waitlist');
    }


    /** @test */
    public function people_can_subscribe()
    {
        Mail::fake();
        $this->withoutExceptionHandling();
        $email = app(\Faker\Generator::class)->email;
        $response = $this->post(
            config('subscription.subscribe_url'),
            ['email' => $email],
            ['HTTP_REFERER' => '/subscribed']
        );
        $response->assertRedirect('/subscribed');
        $this->assertDatabaseHas(config('subscription.table_name'), ['email' => $email]);
    }


    /** @test */
    public function an_email_is_queued_to_be_sent_after_each_new_subscription()
    {
        Queue::fake();
        $this->post(config('subscription.subscribe_url'), ['email' => 'igeoluwasegun363@gmail.com']);
        $subscription = Subscriber::first();
        Queue::assertPushed(SendSubscriptionConfirmation::class, function ($job) use ($subscription) {
            return $job->subscription->is($subscription);
        });
    }

    /** @test */
    public function people_who_already_subscribed_cannot_subscribe_again()
    {
        Queue::fake();
        factory(Subscriber::class)->create(['email' => 'igeoluwasegun363@gmail.com']);
        $this->assertCount(1, Subscriber::all());
        $response = $this->post(config('subscription.subscribe_url'), ['email' => 'igeoluwasegun363@gmail.com']);
        $response->assertRedirect(config('subscription.subscribe_url'));
        $this->assertCount(1, Subscriber::all());
        $this->assertDatabaseHas(config('subscription.table_name'), ['email' => 'igeoluwasegun363@gmail.com']);
        Queue::assertNotPushed(SendSubscriptionConfirmation::class);
    }

    /**@test */

    public function testSubscriptionFailsWhenExistingMailIsProvided()
    {
        Queue::fake();
        Subscriber::create(['email' => 'email@example.com']);
        $response = $this->post(config('subscription.subscribe_url'), [
            'email' => 'email@example.com'
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertContains('The email has already been taken.', session('errors')->get('email'));
        $this->assertEquals(1, Subscriber::count());
        Queue::assertNotPushed(SendSubscriptionConfirmation::class);
    }

    /** @test */
    public function the_email_is_required()
    {
        $response = $this->post(config('subscription.subscribe_url'), []);
        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
        $this->assertEmpty(Subscriber::all());
    }

    /** @test */
    public function the_email_must_be_a_valid_address()
    {
        $response = $this->post(config('subscription.subscribe_url'), ['email' => 'rubbishstuff']);
        $response->assertRedirect(config('subscription.subscribe_url'));
        $response->assertSessionHasErrors('email');
        $this->assertEmpty(Subscriber::all());
    }

    /** @test */
    public function people_who_have_unsubscribed_before_will_enable_their_old_subscription_when_subscribing_again()
    {
        Queue::fake();
        factory(Subscriber::class)->create(['email' => 'john@example.com', 'deleted_at' => Carbon::now()]);

        $this->assertCount(1, Subscriber::withTrashed()->get());
        $this->assertCount(0, Subscriber::all());
        $response = $this->post(config('subscription.subscribe_url'), ['email' => 'john@example.com']);

        $response->assertRedirect(config('subscription.subscribe_url'));
        $this->assertCount(0, Subscriber::all());

        $this->assertCount(1, Subscriber::withTrashed()->get());
        $this->assertDatabaseHas(config('subscription.table_name'), ['email' => 'john@example.com', 'deleted_at' => Carbon::now()]);

        Queue::assertNotPushed(SendSubscriptionConfirmation::class);
    }

    /**@test */
    public function testEmailHasTheCorrectSubject()
    {
        $subcription = factory(Subscriber::class)->make();
        $mail = new SubscriberJoined($subcription);
        $this->assertEquals('Someone joined the waitlist', $mail->build()->subject, "Subject is not correct");
    }

    /**@test */
    public function testEmailHasTheCorrectSubcriber()
    {
        $subcription = factory(Subscriber::class)->make();
        $mail = new SubscriberJoined($subcription);
        $this->assertTrue($mail->subscriber->is($subcription));
    }

    /** @test */
    public function testConfirmationEmailIsSentToTheCorrectAddress()
    {
        Mail::fake();
        $subscription = factory(Subscriber::class)->create(['email' => 'igeoluwasegun363@gmail.com']);
        SendSubscriptionConfirmation::dispatch($subscription);
        Mail::assertQueued(SubscriberJoined::class, function ($mail) {
            return $mail->hasTo('igeoluwasegun363@gmail.com');
        });
    }



    /** @test */
    public function markdown_email_is_sent_by_default()
    {
        Mail::fake();
        $this->assertEquals('markdown', 'markdown');
        $subscription = factory(Subscriber::class)->make();
        $mail = (new SubscriberJoined($subscription))->build();
        $this->assertNull($mail->view);
    }


    // use MailTracking;
    // /**@test*/
    // public function testBasicExample()
    // {

    //     $this->visit('/subscribe')
    //         ->seeEmailsEquals("Hello world")
    //         ->seeEmailContains("Hello")
    //         ->seeEmailSubjectEquals("Someone joined the waitlist")
    //         ->seeEmailReplyToEquals("igeoluwasegun@gmail.com");
    // }



    // /**@test */
    // public function testUserCanViewAnIndexPage()
    // {
    //     $response = $this->get('/');
    //     $response->assertSuccessful();
    //     $response->assertViewIs('waitlist');
    //     // $response->assertStatus(200);
    // }


    // /** @test */
    // public function testIndexDislayTheSubscribeForm()

    // {
    //     $response = $this->get(route('waitlist'));

    //     $response->assertStatus(200);
    //     $response->assertViewIs('waitlist');
    // }

    // /** @test */
    // public function testTheEmailIsRequired()
    // {
    //     $response = $this->post('/', []);

    //     $response->assertStatus(302);
    //     $response->assertSessionHasErrors('email');
    //     // $response->assertEmpty(Subscriber::all());
    // }

    // /** @test */
    // public function testTheEmailMustBeAValidAddress()
    // {
    //     $response = $this->post('/', ['email' => 'rubbish@gmail.com']);
    //     $response->assertStatus(302);
    //     $response->assertSessionHasErrors('email');
    //     // $this->assertEmpty(Subscriber::all());
    // }
}
// Mail::raw('Hello world', function ($message) {
        //     $message->subject('Someone joined the waitlist');
        //     $message->replyTo('igeoluwasegun@gmail.com');
        //     $message->to('igeoluwasegun363@gmail.com');
        //     $message->from('imoleolu2012@gmail.com');
        // });

        // Mail::raw('Hello world', function ($message) {
        //     $message->to('igeoluwasegun363@gmail.com');
        //     $message->from('imoleolu2012@gmail.com');
        // });

        // $this->seeEmailsSent(2)
