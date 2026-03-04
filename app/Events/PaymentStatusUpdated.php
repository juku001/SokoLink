<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;
    public $oldStatus;
    public $newStatus;
    public $callbackData;

    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment, string $oldStatus, string $newStatus, ?array $callbackData = null)
    {
        $this->payment = $payment;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->callbackData = $callbackData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('payment.' . $this->payment->reference),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'payment.status.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'amount' => $this->payment->amount,
            'transaction_id' => $this->payment->transaction_id,
            'callback_data' => $this->callbackData,
            'updated_at' => $this->payment->updated_at->toISOString(),
            'message' => $this->getStatusMessage(),
        ];
    }

    /**
     * Get a user-friendly message based on the new status
     */
    private function getStatusMessage(): string
    {
        return match ($this->newStatus) {
            'successful' => 'Payment completed successfully! Your order is being processed.',
            'failed' => 'Payment failed. Please try again or use a different payment method.',
            'cancelled' => 'Payment was cancelled.',
            'pending' => 'Payment is still pending. Please complete the payment on your device.',
            default => 'Payment status updated to: ' . $this->newStatus,
        };
    }
}
