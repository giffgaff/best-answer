<?php

namespace FoF\BestAnswer\Listeners;

use Flarum\Event\ConfigureDiscussionGambits;
use FoF\BestAnswer\Gambit\IsSolvedGambit;
use FoF\BestAnswer\Gambit\IsUnsolvedGambit;
use Illuminate\Contracts\Events\Dispatcher;

class AddGambits
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureDiscussionGambits::class, [$this, 'isSolvedGambit']);
        $events->listen(ConfigureDiscussionGambits::class, [$this, 'isUnsolvedGambit']);
    }

    public function isSolvedGambit(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(IsSolvedGambit::class);
    }

    public function isUnsolvedGambit(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(IsUnsolvedGambit::class);
    }
}
