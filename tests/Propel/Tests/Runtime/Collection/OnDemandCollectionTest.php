<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Collection;

use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use Propel\Runtime\Propel;
use Propel\Runtime\Collection\OnDemandCollection;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\PropelQuery;

/**
 * Test class for OnDemandCollection.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class OnDemandCollectionTest extends BookstoreEmptyTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        BookstoreDataPopulator::populate($this->con);
        Propel::disableInstancePooling();
        $this->books = PropelQuery::from('\Propel\Tests\Bookstore\Book')->setFormatter(ModelCriteria::FORMAT_ON_DEMAND)->find();
    }

    protected function tearDown(): void
    {
        $this->books = null;
        parent::tearDown();
        Propel::enableInstancePooling();
    }

    public function testSetFormatter()
    {
        $this->assertTrue($this->books instanceof OnDemandCollection);
        $this->assertEquals(4, count($this->books));
    }

    public function testKeys()
    {
        $i = 0;
        foreach ($this->books as $key => $book) {
            $this->assertEquals($i, $key);
            $i++;
        }
    }

    public function testoffsetExists()
    {
        $this->expectException('Propel\Runtime\Exception\PropelException');
        $this->books->offsetExists(2);
    }

    public function testoffsetGet()
    {
        $this->expectException('Propel\Runtime\Exception\PropelException');
        $this->books->offsetGet(2);
    }

    public function testoffsetSet()
    {
        $this->expectException('Propel\Runtime\Exception\BadMethodCallException');
        $this->books->offsetSet(2, 'foo');
    }

    public function testoffsetUnset()
    {
        $this->expectException('Propel\Runtime\Exception\BadMethodCallException');
        $this->books->offsetUnset(2);
    }

    public function testToArray()
    {
        $this->assertNotEquals([], $this->books->toArray());
        // since the code from toArray comes from ObjectCollection, we'll assume it's good
    }

    public function testFromArray()
    {
        $this->expectException('Propel\Runtime\Exception\BadMethodCallException');
        $this->books->fromArray([]);
    }

}
