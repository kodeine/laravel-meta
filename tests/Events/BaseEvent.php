<?php

namespace Kodeine\Metable\Tests\Events;

use Kodeine\Metable\Tests\Models\Event;

class BaseEvent
{
	/**
	 * @var Event
	 */
	public $model;
	public $meta;
	
	public function __construct(Event $model, $meta) {
		$this->model = $model;
		$this->meta = $meta;
	}
}