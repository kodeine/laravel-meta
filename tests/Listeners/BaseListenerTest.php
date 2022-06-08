<?php

namespace Kodeine\Metable\Tests\Listeners;

use Illuminate\Support\Str;
use Kodeine\Metable\Tests\Events\BaseEventTest;

class BaseListenerTest
{
	public function handle(BaseEventTest $event) {
		$eventName = Str::before( class_basename( $event ), 'TestEvent' );
		$eventName = Str::camel( $eventName );
		if ( ! isset( $event->model->classListenersChanges[$eventName] ) ) {
			$event->model->classListenersChanges[$eventName] = [];
		}
		$event->model->classListenersChanges[$eventName][] = $event->meta;
		if ( $event->model->classListenersShouldReturnFalse[$eventName] ?? false ) {
			return false;
		}
	}
}