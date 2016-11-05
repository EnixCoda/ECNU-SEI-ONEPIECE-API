<?php

class FileTest extends TestCase {
	/**
	 * A basic test example.
	 *
	 * @return void
	 */
	public function test() {
		// set
		$cases = [
			[
				"fileId" => "a",
				"section" => "b",
				"data" => [],
				"res_code" => 1,
			],
		];
		foreach ($cases as $case) {
			$fileId = $case["fileId"];
			$section = $case["section"];
			$this->json('post', "file/$fileId/$section", $case["data"])->seeJson([
				"res_code" => $case["res_code"],
			]);
		}

		// get

	}
}