<?php

class LoginTest extends TestCase {
	/**
	 * A basic test example.
	 *
	 * @return void
	 */

	public function test() {
		$cases = [
			[
				"data" => [],
				"res_code" => 1,
			],
			[
				"data" => ["password" => ""],
				"res_code" => 1,
			],
			[
				"data" => ["id" => ""],
				"res_code" => 1,
			],
			[
				"data" => ["id" => "", "password" => ""],
				"res_code" => 1,
			],
			[
				"data" => ["id" => "10132510349", "password" => "123456"],
				"res_code" => 1,
			],
			[
				"data" => ["id" => "10132510349", "password" => "29137X"],
				"res_code" => 0,
			],
		];
		foreach ($cases as $case) {
			$this->json('post', 'login', $case["data"])->seeJson([
				"res_code" => $case["res_code"],
			]);
		}
	}

}