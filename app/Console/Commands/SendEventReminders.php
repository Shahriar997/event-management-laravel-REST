<?php

namespace App\Console\Commands;

use App\Notifications\EventReminderNotification;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use \App\Models\Event;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-event-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends notifications to all event attendees that start in the upcoming 24 hours.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $events = Event::with('attendees.user')
            ->whereBetween('start_time', [now(), now()->addDay()])
            ->get();

        $eventCount = $events->count();
        $eventLabel = Str::plural('event', $eventCount);

        $this->info("Found {$eventCount} ${eventLabel}");

        try{
            $events->each(
                fn($event) => $event->attendees->each(
                    fn($attendee) => $attendee->user->notify(
                        new EventReminderNotification($event)
                    )
                )
            );
            $this->info('Reminder notification sent successfully!');
        } catch(Exception $e) {
            $this->info($e->getMessage());
        }
    }
}
