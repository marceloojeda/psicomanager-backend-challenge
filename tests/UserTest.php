<?php

namespace Tests;

class UserTest extends TestCase
{
    /**
     * Setup do ambiente do teste
     */
    public function test_that_endpoint_users_returns_a_successful_response(): void
    {
        $this->get("users", []);
        $this->seeStatusCode(200);
        $this->seeJsonStructure([
            'data' => ['*' =>
                [
                    'id',
                    'name',
                    'email',
                    'created_at'
                ]
            ],
        ]);
    }
}
