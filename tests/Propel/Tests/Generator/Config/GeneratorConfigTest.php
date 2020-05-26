<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Config;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Tests\Common\Config\ConfigTestCase;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Cristiano Cinotti
 * @package	propel.generator.config
 */
class GeneratorConfigTest extends ConfigTestCase
{
    protected $generatorConfig;

    public function setConfig($config)
    {
        $ref = new \ReflectionClass('\\Propel\\Common\\Config\\ConfigurationManager');
        $refProp = $ref->getProperty('config');
        $refProp->setAccessible(true);
        $refProp->setValue($this->generatorConfig, $config);
    }

    public function setUp(): void
    {
        $php = "
<?php
    return array(
        'propel' => array(
            'database' => array(
                'connections' => array(
                    'mysource' => array(
                        'adapter' => 'sqlite',
                        'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                        'dsn' => 'sqlite:" . sys_get_temp_dir() . "/mydb',
                        'user' => 'root',
                        'password' => '',
                        'model_paths' => [
                            'src',
                            'vendor'
                        ]
                    ),
                    'yoursource' => array(
                        'adapter' => 'mysql',
                        'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                        'dsn' => 'mysql:host=localhost;dbname=yourdb',
                        'user' => 'root',
                        'password' => '',
                        'model_paths' => [
                            'src',
                            'vendor'
                        ]
                    )
                )
            ),
            'runtime' => array(
                'defaultConnection' => 'mysource',
                'connections' => array('mysource', 'yoursource')
            ),
            'generator' => array(
                'defaultConnection' => 'mysource',
                'connections' => array('mysource', 'yoursource')
            )
        )
);
";
        $this->dumpTempFile('propel.php.dist', $php);

        $this->generatorConfig = new GeneratorConfig(sys_get_temp_dir() . '/propel.php.dist');
    }

    public function testGetConfiguredPlatformDeafult()
    {
        $actual = $this->generatorConfig->getConfiguredPlatform();

        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\MysqlPlatform', $actual);
    }

    public function testGetConfiguredPlatformGivenDatabaseName()
    {
        $actual = $this->generatorConfig->getConfiguredPlatform(null, 'mysource');

        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\SqlitePlatform', $actual);
    }

    public function testGetConfiguredPlatform()
    {
        $this->setConfig(['generator' => ['platformClass' => '\\Propel\\Generator\\Platform\\PgsqlPlatform']]);
        $actual = $this->generatorConfig->getConfiguredPlatform();
        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\PgsqlPlatform', $actual);
    }

    public function testGetConfiguredPlatformGivenBadDatabaseNameThrowsException()
    {
        $this->expectException('Propel\Generator\Exception\InvalidArgumentException');
        $this->expectExceptionMessage('Invalid database name: no configured connection named `badsource`.');
        $this->generatorConfig->getConfiguredPlatform(null, 'badsource');
    }

    public function testGetConfiguredPlatformGivenPlatform()
    {
        $this->setConfig(['generator' => ['platformClass' => '\\Propel\\Generator\\Platform\\PgsqlPlatform']]);
        $actual = $this->generatorConfig->getConfiguredPlatform();

        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\PgsqlPlatform', $actual);
    }

    public function testGetConfiguredSchemaParserDefaultClass()
    {
        $stubCon = $this->createMock('\\Propel\\Runtime\\Connection\\ConnectionWrapper');

        $actual = $this->generatorConfig->getConfiguredSchemaParser($stubCon);

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\SqliteSchemaParser', $actual);
    }

    public function testGetConfiguredSchemaParserGivenClass()
    {
        $this->setConfig(
            ['migrations' => [
                'tableName' => 'propel_migration',
                'parserClass' => '\\Propel\\Generator\\Reverse\\PgsqlSchemaParser'
            ]]
        );
        $stubCon = $this->createMock('\\Propel\\Runtime\\Connection\\ConnectionWrapper');

        $actual = $this->generatorConfig->getConfiguredSchemaParser($stubCon);

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\PgsqlSchemaParser', $actual);
    }

    public function testGetConfiguredSchemaParserGivenNonSchemaParserClass()
    {
        $this->expectException('Propel\Generator\Exception\BuildException');
        $this->expectExceptionMessage('Specified class (\Propel\Generator\Platform\MysqlPlatform) does not implement \Propel\Generator\Reverse\SchemaParserInterface interface.');
        $this->setConfig(
            ['migrations' => [
                'tableName' => 'propel_migration',
                'parserClass' => '\\Propel\\Generator\\Platform\\MysqlPlatform'
            ]]
        );

        $actual = $this->generatorConfig->getConfiguredSchemaParser();

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\PgsqlSchemaParser', $actual);
    }

    public function testGetConfiguredSchemaParserGivenBadClass()
    {
        $this->expectException('Propel\Generator\Exception\ClassNotFoundException');
        $this->expectExceptionMessage('Reverse SchemaParser class for `\Propel\Generator\Reverse\BadSchemaParser` not found.');
        $this->setConfig(
            ['migrations' => [
                'tableName' => 'propel_migration',
                'parserClass' => '\\Propel\\Generator\\Reverse\\BadSchemaParser'
            ]]
        );

        $actual = $this->generatorConfig->getConfiguredSchemaParser();

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\PgsqlSchemaParser', $actual);
    }

    public function testGetConfiguredBuilder()
    {
        $stubTable = $this->createMock('\\Propel\\Generator\\Model\\Table');
        $actual = $this->generatorConfig->getConfiguredBuilder($stubTable, 'query');

        $this->assertInstanceOf('\\Propel\\Generator\\Builder\\Om\\QueryBuilder', $actual);
    }

    public function testGetConfiguredBuilderWrongTypeThrowsException()
    {
        $this->expectException('Propel\Generator\Exception\ClassNotFoundException');
        $stubTable = $this->createMock('\\Propel\\Generator\\Model\\Table');
        $actual = $this->generatorConfig->getConfiguredBuilder($stubTable, 'bad_type');
    }

    public function testGetConfiguredPluralizer()
    {
        $actual = $this->generatorConfig->getConfiguredPluralizer();
        $this->assertInstanceOf('\\Propel\\Common\\Pluralizer\\StandardEnglishPluralizer', $actual);

        $config['generator']['objectModel']['pluralizerClass'] = '\\Propel\\Common\\Pluralizer\\SimpleEnglishPluralizer';
        $this->setConfig($config);

        $actual = $this->generatorConfig->getConfiguredPluralizer();
        $this->assertInstanceOf('\\Propel\\Common\\Pluralizer\\SimpleEnglishPluralizer', $actual);
    }

    public function testGetConfiguredPluralizerNonExistentClassThrowsException()
    {
        $this->expectException('Propel\Generator\Exception\ClassNotFoundException');
        $this->expectExceptionMessage('Class \Propel\Common\Pluralizer\WrongEnglishPluralizer not found.');
        $config['generator']['objectModel']['pluralizerClass'] = '\\Propel\\Common\\Pluralizer\\WrongEnglishPluralizer';
        $this->setConfig($config);

        $actual = $this->generatorConfig->getConfiguredPluralizer();
    }

    public function testGetConfiguredPluralizerWrongClassThrowsException()
    {
        $this->expectException('Propel\Generator\Exception\BuildException');
        $this->expectExceptionMessage('Specified class (\Propel\Common\Config\PropelConfiguration) does not implement');
        $config['generator']['objectModel']['pluralizerClass'] = '\\Propel\\Common\\Config\\PropelConfiguration';
        $this->setConfig($config);

        $actual = $this->generatorConfig->getConfiguredPluralizer();
    }

    public function testGetBuildConnections()
    {
        $expected = [
            'mysource' => [
                'adapter' => 'sqlite',
                'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                'dsn' => 'sqlite:' . sys_get_temp_dir() . '/mydb',
                'user' => 'root',
                'password' => '',
                'model_paths' => [
                    'src',
                    'vendor'
                ]
            ],
            'yoursource' => [
                'adapter' => 'mysql',
                'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=yourdb',
                'user' => 'root',
                'password' => '',
                'model_paths' => [
                    'src',
                    'vendor'
                ]
            ]
        ];

        $actual = $this->generatorConfig->getBuildConnections();

        $this->assertEquals($expected, $actual);
    }

    public function testGetBuildConnection()
    {
        $expected = [
            'adapter' => 'sqlite',
            'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
            'dsn' => 'sqlite:' . sys_get_temp_dir() . '/mydb',
            'user' => 'root',
            'password' => '',
            'model_paths' => [
                'src',
                'vendor'
            ]
        ];

        $actual = $this->generatorConfig->getBuildConnection();

        $this->assertEquals($expected, $actual);
    }

    public function testGetBuildConnectionGivenDatabase()
    {
        $expected = [
            'adapter' => 'mysql',
            'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
            'dsn' => 'mysql:host=localhost;dbname=yourdb',
            'user' => 'root',
            'password' => '',
            'model_paths' => [
                'src',
                'vendor'
            ]
        ];

        $actual = $this->generatorConfig->getBuildConnection('yoursource');

        $this->assertEquals($expected, $actual);
    }

    public function testGetBuildConnectionGivenWrongDatabaseThrowsException()
    {
        $this->expectException('Propel\Generator\Exception\InvalidArgumentException');
        $this->expectExceptionMessage('Invalid database name: no configured connection named `wrongsource`.');
        $actual = $this->generatorConfig->getBuildConnection('wrongsource');
    }

    public function testGetConnectionDefault()
    {
        $actual = $this->generatorConfig->getConnection();

        $this->assertInstanceOf('\\Propel\\Runtime\\Connection\\ConnectionWrapper', $actual);
    }

    public function testGetConnection()
    {
        $actual = $this->generatorConfig->getConnection('mysource');

        $this->assertInstanceOf('\\Propel\\Runtime\\Connection\\ConnectionWrapper', $actual);
    }

    public function testGetConnectionWrongDatabaseThrowsException()
    {
        $this->expectException('Propel\Generator\Exception\InvalidArgumentException');
        $this->expectExceptionMessage('Invalid database name: no configured connection named `badsource`.');
        $actual = $this->generatorConfig->getConnection('badsource');
    }

    public function testGetBehaviorLocator()
    {
        $actual = $this->generatorConfig->getBehaviorLocator();

        $this->assertInstanceOf('\\Propel\\Generator\\Util\\BehaviorLocator', $actual);
    }
}
