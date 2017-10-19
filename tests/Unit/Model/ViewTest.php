<?php

namespace App\Tests\Unit\Model;

use App\Model\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    /** @expectedException \DomainException */
    public function testViewCannotBeCreateWithInvalidEvent()
    {
        new View('view-invalid', 1, 'a');
    }

    public function testResourceTypeIsTakenFromEvent()
    {
        $view = new View('view-job', 1, 'a');

        $this->assertEquals('job', $view->toArray()['resourceType']);
    }
}
