<?php

namespace Kodeine\Metable\Tests\Casts\UserState;

use Kodeine\Metable\Tests\Casts\UserCastedObject;

abstract class State extends UserCastedObject
{
	abstract public function description(): string;

	public static function config()
	{
		return [
			'default' => DefaultState::class,
		];
	}
}