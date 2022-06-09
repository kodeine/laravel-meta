<?php

namespace Kodeine\Metable\Tests\Events;

use Kodeine\Metable\Tests\Models\EventTest;

class BaseEventTest
{
	/**
	 * @var EventTest
	 */
	public $model;
	public $meta;
	
	public function __construct(EventTest $model, $meta) {
		$this->model = $model;
		$this->meta = $meta;
	}
}