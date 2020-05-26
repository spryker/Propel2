<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config;

use Propel\Common\Config\XmlToArrayConverter;

class XmlToArrayConverterTest extends ConfigTestCase
{
    use DataProviderTrait;

    /**
     * @dataProvider providerForXmlToArrayConverter
     */
    public function testConvertFromString($xml, $expected)
    {
        $actual = XmlToArrayConverter::convert($xml);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForXmlToArrayConverter
     */
    public function testConvertFromFile($xml, $expected)
    {
        $this->dumpTempFile('testconvert.xml', $xml);
        $actual = XmlToArrayConverter::convert(sys_get_temp_dir() . '/testconvert.xml');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForXmlToArrayConverterXmlInclusions
     */
    public function testConvertFromFileWithXmlInclusion($xmlLoad, $xmlInclude, $expected)
    {
        $this->dumpTempFile('testconvert.xml', $xmlLoad);
        $this->dumpTempFile('testconvert_include.xml', $xmlInclude);
        $actual = XmlToArrayConverter::convert(sys_get_temp_dir() . '/testconvert.xml');
        $this->assertEquals($expected, $actual);
    }

    public function testInvalidFileNameThrowsException()
    {
        $this->expectException('Propel\Common\Config\Exception\InvalidArgumentException');
        $this->expectExceptionMessage('XmlToArrayConverter::convert method expects an xml file to parse, or a string containing valid xml');
        XmlToArrayConverter::convert(1);
    }

    public function testInexistentFileThrowsException()
    {
        $this->expectException('Propel\Common\Config\Exception\InvalidArgumentException');
        $this->expectExceptionMessage('Invalid xml content');
        XmlToArrayConverter::convert('nonexistent.xml');
    }

    public function testInvalidXmlThrowsException()
    {
        $this->expectException('Propel\Common\Config\Exception\InvalidArgumentException');
        $this->expectExceptionMessage('Invalid xml content');
        $invalidXml = <<< XML
No xml
only plain text
---------
XML;
        XmlToArrayConverter::convert($invalidXml);
    }

    public function testErrorInXmlThrowsException()
    {
        $this->expectException('Propel\Common\Config\Exception\XmlParseException');
        $this->expectExceptionMessage('An error occurred while parsing XML configuration file:');
        $xmlWithError = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <titles>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</movies>
XML;
        XmlToArrayConverter::convert($xmlWithError);
    }

    /**
        - Fatal Error 76: Opening and ending tag mismatch: titles line 4 and title
        - Fatal Error 73: expected '>'
        - Fatal Error 5: Extra content at the end of the document
    */
    public function testMultipleErrorsInXmlThrowsException()
    {
        $this->expectException('Propel\Common\Config\Exception\XmlParseException');
        $this->expectExceptionMessage('Some errors occurred while parsing XML configuration file:');
        $xmlWithErrors = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <titles>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</moviess>
XML;
        XmlToArrayConverter::convert($xmlWithErrors);
    }

    public function testEmptyFileReturnsEmptyArray()
    {
        $this->dumpTempFile('empty.xml', '');
        $actual = XmlToArrayConverter::convert(sys_get_temp_dir() . '/empty.xml');

        $this->assertEquals([], $actual);
    }
}
