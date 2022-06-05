<?php

namespace Kodeine\Metable\Tests\Listeners;

use Illuminate\Support\Str;
use Kodeine\Metable\Tests\Events\BaseEvent;

class BaseListener
{
	public function handle(BaseEvent $event) {
		$eventName = Str::camel( class_basename( $event ) );
		if ( ! isset( $event->model->classListenersChanges[$eventName] ) ) {
			$event->model->classListenersChanges[$eventName] = [];
		}
		$event->model->classListenersChanges[$eventName][] = $event->meta;
		if ( $event->model->classListenersShouldReturnFalse ) {
			return false;
		}
	}
}