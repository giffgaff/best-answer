<?php

/*
 * This file is part of fof/best-answer.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\BestAnswer\Listeners;

use Carbon\Carbon;
use Flarum\Discussion\Event\Saving;
use Flarum\Foundation\ValidationException;
use Flarum\Notification\Notification;
use Flarum\Notification\NotificationSyncer;
use Flarum\User\Exception\PermissionDeniedException;
use FoF\BestAnswer\Events\BestAnswerSet;
use FoF\BestAnswer\Helpers;
use FoF\BestAnswer\Notification\SelectBestAnswerBlueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;

class SelectBestAnswer
{
    private $key = 'attributes.bestAnswerPostId';

    /**
     * @var NotificationSyncer
     */
    private $notifications;

    private $bus;

    public function __construct(NotificationSyncer $notifications, Dispatcher $bus)
    {
        $this->notifications = $notifications;
        $this->bus = $bus;
    }

    public function handle(Saving $event)
    {
        if (!Arr::has($event->data, $this->key)) {
            return;
        }

        $discussion = $event->discussion;
        /** @var int|null $id */
        $id = Arr::get($event->data, $this->key);

        if (!$discussion->exists || $discussion->best_answer_post_id === $id) {
            return;
        }

        $post = $event->discussion->posts()->find($id);

        // If the best answer post isn't part of the current discussion, throw a friendly validation error.
        // Using $post hereon after to determine whether we are selecting a best answer.
        if ($id && ! $post) {
            throw new ValidationException(
                [
                    'error' => app('translator')->trans('fof-best-answer.forum.errors.mismatch'),
                ]
            );
        }

        if ($post && !Helpers::canSelectPostAsBestAnswer($event->actor, $post) || !$post->isVisibleTo($event->actor)) {
            throw new PermissionDeniedException();
        }

        if ($id) {
            $discussion->best_answer_post_id = $post->id;
            $discussion->best_answer_user_id = $event->actor->id;
            $discussion->best_answer_set_at = Carbon::now();

            Notification::where('type', 'selectBestAnswer')->where('subject_id', $discussion->id)->delete();
            $this->notifyUsersOfBestAnswerSet($event);
        } else {
            $discussion->best_answer_post_id = null;
            $discussion->best_answer_user_id = null;
            $discussion->best_answer_set_at = null;
        }

        $this->notifications->delete(new SelectBestAnswerBlueprint($discussion));
    }

    public function notifyUsersOfBestAnswerSet(Saving $event)
    {
        $actor = $event->actor;

        $event->discussion->afterSave(function ($discussion) use ($actor) {
            $this->bus->dispatch(new BestAnswerSet($discussion, $actor));
        });
    }
}
