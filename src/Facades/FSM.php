<?php namespace Gecche\FSM\Facades;

use Illuminate\Support\Facades\Facade;

class FSM extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
            protected static function getFacadeAccessor() { return 'fsm'; }

}
