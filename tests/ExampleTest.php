<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {

    }

    public function testIndex()
    {
        $this->json('get', 'index')
            ->seeJson([
                "res_code" => 0
            ]);
    }

    public function testLogin()
    {
        $cases = [
            [
                "data" => null,
                "res_code" => 1
            ],
            [
                "data" => ["password" => ""],
                "res_code" => 1
            ],
            [
                "data" => ["id" => ""],
                "res_code" => 1
            ],
            [
                "data" => ["id" => "", "password" => ""],
                "res_code" => 1
            ],
            [
                "data" => ["id" => "10132510349", "password" => "123456"],
                "res_code" => 1
            ],
            [
                "data" => ["id" => "10132510349", "password" => "29137X"],
                "res_code" => 0
            ],
        ];
        foreach ($cases as $case) {
            $this->json('post', 'login', $case["data"])->seeJson([
                "res_code" => $case["res_code"]
            ]);
        }
    }

    public function testFile()
    {
        // set
        $cases = [
            [
                "fileId" => "",
                "section" => "",
                "data" => [],
                "res_code" => 1
            ]
        ];
        foreach ($cases as $case) {
            $fileId = $case["fileId"];
            $section = $case["section"];
            $this->json('post', "file/$fileId/$section", $case["data"])->seeJson([
                "res_code" => $case["res_code"]
            ]);
        }
        
        // get
        
    }
}
