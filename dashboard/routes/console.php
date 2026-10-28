<?php

use App\Http\Controllers\CardController;
use App\Models\StreamAnnouncement;
use App\Models\StreamSource;
use App\Models\WebhookDelivery;
use App\Services\ItemFrames;
use App\Services\ProfileCardData;
use App\Services\Streams\Announcer;
use App\Services\Streams\SourceManager;
use App\Services\Streams\Twitch;
use App\Services\Streams\YouTube;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('streams:maintain', function (SourceManager $manager) {
    foreach (StreamSource::query()->withCount('subscriptions')->get() as $source) {
        if ($source->subscriptions_count === 0) {
            $manager->dropSource($source);

            continue;
        }

        $manager->ensureHooks($source);
    }

    WebhookDelivery::query()->where('created_at', '<', now()->subDays(2))->delete();
    StreamAnnouncement::query()->where('kind', 'upcoming')->whereNull('message_id')
        ->where('created_at', '<', now()->subDays(14))->delete();
    StreamAnnouncement::query()->where('created_at', '<', now()->subDays(30))->delete();

    $this->info('Stream sources checked.');
})->purpose('Renew YouTube push subscriptions and repair Twitch EventSub subscriptions');

Artisan::command('streams:check-upcoming', function (YouTube $youtube, Announcer $announcer) {
    $pending = StreamAnnouncement::query()
        ->with('subscription.source')
        ->where('kind', 'upcoming')
        ->whereNull('message_id')
        ->get();

    if ($pending->isEmpty() || ! $youtube->hasApiKey()) {
        return;
    }

    $videos = $youtube->videos($pending->pluck('item_id')->unique()->values()->all());

    foreach ($pending->unique('item_id') as $announcement) {
        $video = $videos[$announcement->item_id] ?? null;
        $source = $announcement->subscription?->source;

        if (! $video || ! $source) {
            continue;
        }

        if ($video['ended'] || $video['live'] === 'none') {
            StreamAnnouncement::query()->where('item_id', $announcement->item_id)->where('kind', 'upcoming')
                ->update(['kind' => 'missed', 'ended_at' => now()]);

            continue;
        }

        if ($video['live'] === 'live') {
            $announcer->publishYoutube($source, $announcement->item_id, $video['title'], 'live', $video['thumbnail']);
        }
    }
})->purpose('Announce scheduled YouTube livestreams once they start');

Artisan::command('streams:sync', function (Announcer $announcer, Twitch $twitch) {
    if ($twitch->configured()) {
        rescue(fn () => $announcer->syncTwitch());
    }
    rescue(fn () => $announcer->syncYoutube());
})->purpose('Correct the live status of followed streamers in case a webhook was missed');

Artisan::command('cards:prune', function () {
    $disk = Storage::disk('local');
    $deleted = 0;
    foreach ($disk->files(CardController::CACHE_DIR) as $file) {
        if ($disk->lastModified($file) < now()->subHours(6)->getTimestamp()) {
            $disk->delete($file);
            $deleted++;
        }
    }
    foreach ($disk->files(ProfileCardData::AVATAR_DIR) as $file) {
        if ($disk->lastModified($file) < now()->subDays(7)->getTimestamp()) {
            $disk->delete($file);
        }
    }
    foreach ($disk->directories(ItemFrames::DIR) as $dir) {
        $meta = "{$dir}/frames.json";
        if (! $disk->exists($meta) || $disk->lastModified($meta) < now()->subDays(30)->getTimestamp()) {
            $disk->deleteDirectory($dir);
        }
    }
    $this->info("Deleted {$deleted} cached cards.");
})->purpose('Delete cached animated profile cards and old prepared item frames');

Schedule::command('cards:prune')->hourly()->withoutOverlapping();
Schedule::command('streams:maintain')->hourly()->withoutOverlapping();
Schedule::command('streams:sync')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('streams:check-upcoming')->everyTwoMinutes()->withoutOverlapping();
