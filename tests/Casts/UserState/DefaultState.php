<?php

namespace Kodeine\Metable\Tests\Casts\UserState;

class DefaultState extends State
{
	public $description;
	
	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct() {
		$this->description = $this->description();
	}
	
	public function description(): string {
		return 'This is a default description.';
	}
}