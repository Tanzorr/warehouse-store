<?php

namespace App\Jobs;

use App\Mail\CancelUnpaidOrderMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class CancelUnpaidOrderJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Order $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->order->status === 'pending') {
            $user = User::where('id', $this->order->user_id)->first();

            Mail::to($user->email)->send(new CancelUnpaidOrderMail($this->order));
        }
    }
}
