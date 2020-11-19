<?php

namespace FoF\BestAnswer\Listeners;

use Flarum\Event\ConfigureDiscussionGambits;
use FoF\BestAnswer\Gambit\HasBestAnswerGambit;
use Illuminate\Contracts\Events\Dispatcher;

class AddGambits
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureDiscussionGambits::class, [$this, 'hasBestAnswerGambit']);
    }

    public function hasBestAnswerGambit(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(HasBestAnswerGambit::class);
    }
}
