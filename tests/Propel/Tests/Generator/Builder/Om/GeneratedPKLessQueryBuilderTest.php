<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class GeneratedPKLessQueryBuilderTest extends TestCase
{
    public function setUp(): void
    {
        if (class_exists('Stuff')) {
            return;
        }

        $schema = <<<SCHEMA
<database name="primarykey_less_test">
    <table name="stuff">
        <column name="key" type="VARCHAR" />
        <column name="value" type="VARCHAR" />
    </table>
</database>
SCHEMA;

        QuickBuilder::buildSchema($schema);
    }

    public function testFindPkThrowsAnError()
    {
        $this->expectException('Propel\Runtime\Exception\LogicException');
        $this->expectExceptionMessage('The Stuff object has no primary key');
        \StuffQuery::create()->findPk(42);
    }

    public function testBuildPkeyCriteria()
    {
        $this->expectException('Propel\Runtime\Exception\LogicException');
        $this->expectExceptionMessage('The Stuff object has no primary key');
        $stuff = new \Stuff();
        $stuff->buildPkeyCriteria();
    }

    public function testTableMapDoDelete()
    {
        $this->expectException('Propel\Runtime\Exception\LogicException');
        $this->expectExceptionMessage('The Stuff object has no primary key');
        \Map\StuffTableMap::doDelete([]);
    }

    public function testFindPksThrowsAnError()
    {
        $this->expectException('Propel\Runtime\Exception\LogicException');
        $this->expectExceptionMessage('The Stuff object has no primary key');
        \StuffQuery::create()->findPks([42, 24]);
    }

    public function testFilterByPrimaryKeyThrowsAnError()
    {
        $this->expectException('Propel\Runtime\Exception\LogicException');
        $this->expectExceptionMessage('The Stuff object has no primary key');
        \StuffQuery::create()->filterByPrimaryKey(42);
    }

    public function testFilterByPrimaryKeysThrowsAnError()
    {
        $this->expectException('Propel\Runtime\Exception\LogicException');
        $this->expectExceptionMessage('The Stuff object has no primary key');
        \StuffQuery::create()->filterByPrimaryKeys(42);
    }
}
