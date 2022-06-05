<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFieldInspection */

namespace Kodeine\Metable\Tests;

use PHPUnit\Framework\TestCase;
use Kodeine\Metable\Tests\Models\Event;
use Kodeine\Metable\Tests\Events\MetaSaving;
use Illuminate\Database\Capsule\Manager as Capsule;
use Kodeine\Metable\Tests\Observers\EventObserver;
use Kodeine\Metable\Tests\Listeners\HandleMetaSaving;

class HasMetaEventsTest extends TestCase
{
	protected function setUp(): void {
		$capsule = new Capsule;
		$capsule->addConnection( [
			'driver' => 'sqlite',
			'database' => ':memory:',
			'charset' => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix' => '',
			'foreign_key_constraints' => true,
		] );
		$capsule->setAsGlobal();
		$capsule->bootEloquent();
		Capsule::schema()->enableForeignKeyConstraints();
		Capsule::schema()->create( 'events', function ($table) {
			$table->id();
			$table->string( 'name' )->default( 'john' );
			$table->timestamps();
		} );
		Capsule::schema()->create( 'events_meta', function ($table) {
			$table->id();
			$table->integer( 'event_id' )->unsigned();
			$table->foreign( 'event_id' )->references( 'id' )->on( 'events' )->onDelete( 'cascade' );
			$table->string( 'type' )->default( 'null' );
			$table->string( 'key' )->index();
			$table->text( 'value' )->nullable();
			
			$table->timestamps();
		} );
		
		Event::observe( EventObserver::class );
		
		$listen = [
			MetaSaving::class => [
				HandleMetaSaving::class,
			],
		];
		foreach ($listen as $event => $listeners) {
			foreach ($listeners as $listener) {
				Event::getEventDispatcher()->listen( $event, $listener );
			}
		}
	}
	
	public function testMetaSavingEvent() {
		$eventName = 'metaSaving';
		$event = new Event;
		
		$this->assertContains( 'metaSaving', $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		$event->save();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->bar = 'bar';
		$event->listenerShouldReturnFalse = true;
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->save();
		
		$metaData = Capsule::table( 'events_meta' )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
		$this->assertContains( 'bar', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		$this->assertNull( $metaData->first(), "Meta should not be saved" );
		
		$event->listenerShouldReturnFalse = false;
		$event->observersShouldReturnFalse = true;
		$event->foo = 'baz';
		$event->save();
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->observersShouldReturnFalse = false;
		$event->classListenersShouldReturnFalse = true;
		$event->save();
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->classListenersShouldReturnFalse = false;
		$event->save();
		
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
	}
}