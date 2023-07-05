<?php

namespace Kodeine\Metable\Tests\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;

abstract class UserCastedObject implements Castable
{
	public static $name;
	public $model;
	public $stateConfig;
	public $field;
	
	public function __construct($model) {
		$this->model = $model;
		$this->stateConfig = static::config();
	}
	
	public static function config() {
		return [
			'default' => null,
		];
	}
	
	public static function castUsing(array $arguments) {
		return new StateCaster( static::class );
	}
	
	public function setField(string $field): self {
		$this->field = $field;
		
		return $this;
	}
	
	public static function getMorphClass(): string {
		return static::$name ?? static::class;
	}
	
	public function make(?string $name, $model) {
		if ( is_null( $name ) ) {
			return null;
		}
		
		return new $name( $model );
	}
}