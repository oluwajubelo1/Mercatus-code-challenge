<?php

namespace App\Http\Controllers;

use App\Subscriber;
use App\Mail\SubscriberJoined;
use App\Http\Requests\WaitlistRequest;
use App\Jobs\SendSubscriptionConfirmation;
use Illuminate\Support\Facades\Mail;

class WaitlistController extends Controller
{
    public function index()
    {
        return view('waitlist');
    }
    public function subscribe(WaitlistRequest $request)
    {

        $existingSubscription = Subscriber::withTrashed()->whereEmail($request->email)->first();
        if ($existingSubscription) {
            if ($existingSubscription->trashed()) {
                $existingSubscription->restore();
                SendSubscriptionConfirmation::dispatch($existingSubscription);
            }
        } else {
            $subscriber = Subscriber::create([
                'email' => $request->email,
            ]);
            SendSubscriptionConfirmation::dispatch($subscriber);
        }

        // Mail::to($request->email)->send(
        //     new SubscriberJoined($subscriber)
        // );
        // return 'success';
        return redirect()->route('subscribed');
    }
    public function subscribed()
    {
        return view('subscribed');
    }
}
