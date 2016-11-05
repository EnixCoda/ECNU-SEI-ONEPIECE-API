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

	public function testRefresh() {
	    $this->json('get', 'index?refresh')
            ->seeJson([
                'res_code' => 0
            ]);
    }
}
