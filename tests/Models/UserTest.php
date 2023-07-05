<?php

namespace Kodeine\Metable\Tests\Models;

use Kodeine\Metable\Metable;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Kodeine\Metable\Tests\Casts\UserState\State;
use Kodeine\Metable\Tests\Traits\HasUserStates;

class UserTest extends Model
{
	use Metable;
	use HasUserStates;
	
	public $defaultMetaValues = [
		'default_meta_key' => 'default_meta_value',
	];
	
	public $hideMeta = false;
	
	public $disableFluentMeta = false;
	
	protected $casts = [
		'state' => State::class,
	];
	
	public function getNullCastAttribute() {
		return null;
	}
	
	/**
	 * This is dummy relation to itself.
	 *
	 * @return HasOne
	 */
	public function dummy(): HasOne {
		return $this->hasOne( static::class, 'user_id', 'id' );
	}
	
	public function getAccessorMeta($value): string {
		return 'accessed_' . $value;
	}
	
	public function setMutatorMeta($value) {
		$this->setMeta( 'mutator', 'mutated_' . $value );
	}
	
	public static function boot() {
		
		static::setEventDispatcher( new Dispatcher() );
		parent::boot();
	}
}