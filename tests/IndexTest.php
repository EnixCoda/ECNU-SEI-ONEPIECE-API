<?php

class IndexTest extends TestCase {
	/**
	 * A basic test example.
	 *
	 * @return void
	 */

	public function test() {
		$this->json('get', 'index')
			->seeJson([
				"res_code" => 0,
			]);
	}
}
