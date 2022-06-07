<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFieldInspection */

namespace Kodeine\Metable\Tests;

use PHPUnit\Framework\TestCase;
use Kodeine\Metable\Tests\Models\EventTest;
use Kodeine\Metable\Tests\Events\MetaSavingTestEvent;
use Kodeine\Metable\Tests\Events\MetaUpdatingTestEvent;
use Illuminate\Database\Capsule\Manager as Capsule;
use Kodeine\Metable\Tests\Observers\EventObserver;
use Kodeine\Metable\Tests\Events\MetaDeletingTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaSavingTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaUpdatingTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaDeletingTestEvent;

class HasMetaEventsTest extends TestCase
{
	public static function setUpBeforeClass(): void {
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
		Capsule::schema()->create( 'event_tests', function ($table) {
			$table->id();
			$table->string( 'name' )->default( 'john' );
			$table->timestamps();
		} );
		Capsule::schema()->create( 'event_tests_meta', function ($table) {
			$table->id();
			$table->integer( 'event_test_id' )->unsigned();
			$table->foreign( 'event_test_id' )->references( 'id' )->on( 'event_tests' )->onDelete( 'cascade' );
			$table->string( 'type' )->default( 'null' );
			$table->string( 'key' )->index();
			$table->text( 'value' )->nullable();
			
			$table->timestamps();
		} );
		
		// create new model so that it's boot method registers the event dispatcher
		/** @noinspection PhpUnusedLocalVariableInspection */
		$event = new EventTest;
		EventTest::observe( EventObserver::class );
		
		$listen = [
			MetaSavingTestEvent::class => [
				HandleMetaSavingTestEvent::class,
			],
			MetaUpdatingTestEvent::class => [
				HandleMetaUpdatingTestEvent::class,
			],
			MetaDeletingTestEvent::class => [
				HandleMetaDeletingTestEvent::class,
			],
		];
		foreach ($listen as $event => $listeners) {
			foreach ($listeners as $listener) {
				EventTest::getEventDispatcher()->listen( $event, $listener );
			}
		}
	}
	
	public function testMetaSavingEvent() {
		$eventName = 'metaSaving';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
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
		
		$metaData = Capsule::table( $event->getMetaTable() )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
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
		
		$event->delete();
	}
	
	public function testMetaUpdatingEvent() {
		$eventName = 'metaUpdating';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		$event->save();
		
		$this->assertNotContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired when meta didn't exist before" );
		$this->assertNotContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should not be fired when meta didn't exist before" );
		$this->assertNotContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should not be fired when meta didn't exist before" );
		
		$event->foo = 'foobar';
		$event->save();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->bar = 'bar';
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->delete();
	}
	
	public function testMetaDeletingEvent() {
		$eventName = 'metaDeleting';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		
		$event->unsetMeta( 'foo' );
		$event->save();
		
		$this->assertNotContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertNotContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertNotContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertFalse( $event->hasMeta( 'foo' ), "Meta should not exist" );
		
		$event->foo = 'bar';
		$event->saveMeta();
		$event->unsetMeta( 'foo' );
		$event->save();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->hasMeta( 'foo' ), "Meta should not exist" );
		
		$event->bar = 'bar';
		$event->save();
		$event->listenerShouldReturnFalse = true;
		$event->unsetMeta( 'bar' );
		$event->save();
		
		$metaData = Capsule::table( $event->getMetaTable() )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
		$this->assertContains( 'bar', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertTrue( $event->getMetaData()->has( 'bar' ), "Meta should exist" );
		$this->assertFalse( $event->hasMeta( 'bar' ), "Meta should not exist because it is marked for deletion" );
		$this->assertNotNull( $metaData->first(), "Meta should not be removed" );
		
		$event->listenerShouldReturnFalse = false;
		$event->observersShouldReturnFalse = true;
		$event->save();
		
		$this->assertTrue( $event->getMetaData()->has( 'bar' ), "Meta should exist" );
		$this->assertFalse( $event->hasMeta( 'bar' ), "Meta should not exist because it is marked for deletion" );
		$this->assertNotNull( $metaData->first(), "Meta should not be removed" );
		
		$event->observersShouldReturnFalse = false;
		$event->classListenersShouldReturnFalse = true;
		$event->save();
		
		$this->assertTrue( $event->getMetaData()->has( 'bar' ), "Meta should exist" );
		$this->assertFalse( $event->hasMeta( 'bar' ), "Meta should not exist because it is marked for deletion" );
		$this->assertNotNull( $metaData->first(), "Meta should not be removed" );
		
		$event->classListenersShouldReturnFalse = false;
		$event->save();
		
		$this->assertFalse( $event->getMetaData()->has( 'bar' ), "Meta should not exist" );
		$this->assertNull( $metaData->first(), "Meta should be removed" );
		
		$event->delete();
	}
}