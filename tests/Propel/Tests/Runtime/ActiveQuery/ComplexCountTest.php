<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Runtime\Propel;

/**
 * Test class for ComplexCountTest.
 *
 * @author Fredrik Wollsén
 *
 * @group database
 */
class ComplexCountTest extends BookstoreTestBase
{
    /**
     * @expectedException \Propel\Runtime\Exception\LogicException
     */
    public function testCountQueryWhenUsingHavingAndDuplicateColumnNamesInTheSelectPart()
    {
        $c = new AuthorQuery();
        $c->leftJoinWithBook();
        $c->addHaving('COUNT(Book.id) > 1');

        $this->assertTrue($c->needsSelectAliases(), 'query needs select aliases');

        $this->assertTrue((bool) $c->getHaving(), 'query has a having clause');

        $nbAuthorsWithAtLeastOneBook = $c->count();
    }
}
