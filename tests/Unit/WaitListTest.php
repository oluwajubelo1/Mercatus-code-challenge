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
        $response->assertStatus(200); # Check if the status is 200 i.e okay
        $response->assertViewIs('waitlist'); # Check if the right view is been displayed
    }

    /**
     * @test
     */
    public function subscribed_user_can_view_waitlist_success_page()
    {
        $response = $this->get(route('subscribed'));
        $response->assertSuccessful(); # Check if there's page not found error
        $response->assertViewIs('subscribed'); # Check if the right view is been displayed
        $response->assertSeeText('You’re on the waitlist'); # Check if subscribed user can see waitlist form
    }

    /** @test */
    public function people_can_subscribe()
    {
        Mail::fake(); #prevent mails from being queued
        $this->withoutExceptionHandling();
        $email = app(\Faker\Generator::class)->email; #generate a fake email for test
        $response = $this->post(
            config('subscription.subscribe_url'),
            ['email' => $email],
            ['HTTP_REFERER' => '/subscribed']
        );
        $response->assertRedirect('/subscribed'); #redirect response to subscribed view
        $this->assertDatabaseHas(config('subscription.table_name'), ['email' => $email]); #assert that the database 'config(subscription.table_name) exists with the given email'
        $this->subscribed_user_can_view_waitlist_success_page(); #show the subscribed user the waitlist success page
    }


    /** @test */
    public function an_email_is_queued_to_be_sent_after_each_new_subscription()
    {
        Queue::fake(); #prevent jobs from being queued
        $this->post(config('subscription.subscribe_url'), ['email' => 'igeoluwasegun363@gmail.com']); #post request to 'config(subscription.subscription_url) with email been passed'
        $subscription = Subscriber::first(); #retrives the first model of subscriber
        Queue::assertPushed(SendSubscriptionConfirmation::class, function ($job) use ($subscription) { #assert SendSubscriptionConfirmation Job was pushed
            return $job->subscription->is($subscription); #return subscription property value of SendSubscriptionConfirmation Job
        });
    }

    /** @test */
    public function people_who_already_subscribed_cannot_subscribe_again()
    {
        Queue::fake(); #prevent jobs from being queued
        factory(Subscriber::class)->create(['email' => 'igeoluwasegun363@gmail.com']); #creates a new record of an email for subscriber model
        $this->assertCount(1, Subscriber::all()); #asserts that the count of records on subscriber model is equal to 1
        $response = $this->post(config('subscription.subscribe_url'), ['email' => 'igeoluwasegun363@gmail.com']); #post request to 'config(subscription.subscribe_url) with email been passed'
        $response->assertRedirect(config('subscription.subscribe_url')); #assert that the proccess is been redirected to 'config(subscription.subscribe_url)'
        $this->assertCount(1, Subscriber::all()); #assert that the count of records on subscriber model is still one, just to verify that no new record is been created
        $this->assertDatabaseHas(config('subscription.table_name'), ['email' => 'igeoluwasegun363@gmail.com']); #assert that the database 'config(subscription.table_name) exists with the given email'
        Queue::assertNotPushed(SendSubscriptionConfirmation::class); #assert that SendSubscriptionConfrimation Job was not pushed
    }

    /**@test */

    public function testSubscriptionFailsWhenExistingMailIsProvided()
    {
        Queue::fake(); #prevent jobs from being queued
        Subscriber::create(['email' => 'email@example.com']); #creates a new record on subscriber model
        $response = $this->post(config('subscription.subscribe_url'), [
            'email' => 'email@example.com'
        ]);
        $response->assertSessionHasErrors('email'); # assert that session contains an error for email field
        $this->assertContains('The email has already been taken.', session('errors')->get('email')); # assert that the email error message contains 'The email has already been taken'
        $this->assertEquals(1, Subscriber::count());  #assert that the count of records on subscriber model is equals one, just to verify that no new record is been created
        Queue::assertNotPushed(SendSubscriptionConfirmation::class); #assert that SendSubscriptionConfrimation Job was not pushed
    }

    /** @test */
    public function the_email_is_required()
    {
        $response = $this->post(config('subscription.subscribe_url'), []);  #post request to 'config(subscription.subscription_url) with email not been passed'
        $response->assertRedirect('/'); #redirects back to landing page
        $response->assertSessionHasErrors('email'); # assert that session contains an error for email field
        $this->assertEmpty(Subscriber::all()); # assert that subsccriber table is empty
    }

    /** @test */
    public function the_email_must_be_a_valid_address()
    {
        $response = $this->post(config('subscription.subscribe_url'), ['email' => 'rubbishstuff']); #post request to 'config(subscription.subscription_url) with an invalid email been passed'
        $response->assertRedirect(config('subscription.subscribe_url')); #redirects to 'config(subscription.subscribe_url)' page
        $response->assertSessionHasErrors('email'); # assert that session contains an error for email field
        $this->assertEmpty(Subscriber::all()); # assert that subsccriber table is empty
    }


    /** @test */
    public function people_who_have_unsubscribed_before_will_renable_their_old_subscription_when_subscribing_again()
    {
        Queue::fake(); #prevent jobs from being queued
        factory(Subscriber::class)->create(['email' => 'john@example.com', 'deleted_at' => Carbon::now()]); #creates a new record having email and deleted_at for subscriber model

        $this->assertCount(1, Subscriber::withTrashed()->get()); #asserts that the count of records on subscriber model with those records that have been deleted is equal to 1
        $this->assertCount(0, Subscriber::all()); #asserts that the count of records on subscriber model excluding deleted records on the model is equal to 0
        $response = $this->post(config('subscription.subscribe_url'), ['email' => 'john@example.com']); #post request to 'config(subscription.subscription_url) with an email been passed'

        $response->assertRedirect(config('subscription.subscribe_url')); #redirects to 'config(subscription.subscribe_url)' page
        $this->assertCount(0, Subscriber::all()); #asserts that the count of records on subscriber model excluding deleted records on the model is equal to 0

        $this->assertCount(1, Subscriber::withTrashed()->get()); #asserts that the count of records on subscriber model with those records that have been deleted is equal to 1
        $this->assertDatabaseHas(config('subscription.table_name'), ['email' => 'john@example.com', 'deleted_at' => Carbon::now()]);  #assert that the database 'config(subscription.table_name) exists with the given email and deleted_at'

        Queue::assertNotPushed(SendSubscriptionConfirmation::class); #assert that SendSubscriptionConfrimation Job was not pushed
    }

    /**@test */
    public function testEmailHasTheCorrectSubject()
    {
        $subcription = factory(Subscriber::class)->make(); #creates a new subscriber model class
        $mail = new SubscriberJoined($subcription); # creates a new instance of SubscriberJoined mail with the new subscriber model been passed to it
        $this->assertEquals('Someone joined the waitlist', $mail->build()->subject, "Subject is not correct"); #assert that the the mail subject equals 'someone joined the waitlist' else, it displays an error 'subject is not correct'
    }

    /**@test */
    public function testEmailHasTheCorrectSubcriber()
    {
        $subcription = factory(Subscriber::class)->make(); #creates a new subscriber model class
        $mail = new SubscriberJoined($subcription); # creates a new instance of SubscriberJoined mail with the new subscriber model been passed to it
        $this->assertTrue($mail->subscriber->is($subcription)); #assert that the mail subscriber is true
    }

    /** @test */
    public function testConfirmationEmailIsSentToTheCorrectAddress()
    {
        Mail::fake(); #prevent jobs from being queued
        $subscription = factory(Subscriber::class)->create(['email' => 'igeoluwasegun363@gmail.com']); #creates a new record of an email for subscriber model
        SendSubscriptionConfirmation::dispatch($subscription); #SendSubscriptionConfirmation job is been dispatched
        Mail::assertQueued(SubscriberJoined::class, function ($mail) { #SubscriberJoined mail is queued in the background
            return $mail->hasTo('hello@example.com'); #return email has to 'hello@example.com'
        });
    }



    /** @test */
    public function markdown_email_is_sent_by_default()
    {
        Mail::fake(); #prevent jobs from being queued
        $this->assertEquals('markdown', 'markdown'); #assert that markdown equals maekdown
        $subscription = factory(Subscriber::class)->make(); #creates a new subscriber model class
        $mail = (new SubscriberJoined($subscription))->build(); #builds subscriberJoined mail
        $this->assertNull($mail->view); #assert that mail view is null
    }
}
