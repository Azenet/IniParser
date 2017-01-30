<?php

use Azenet\IniParser\IniParser;
use PHPUnit\Framework\TestCase;

/**
 * Class IniParserTest
 *
 * @covers Azenet\IniParser\IniParser
 */
class IniParserTest extends TestCase {
	private $baseDataWithSections = <<<EOF
[Section1]
key1=value1
key2=

[Section2]
key1=value2
key2=value1
EOF;
	private $baseDataWithSectionsResult = ['Section1' => ['key1' => 'value1', 'key2' => ''], 'Section2' => ['key1' => 'value2', 'key2' => 'value1']];

	protected function setUp() {
		register_shutdown_function(function () {
			if (file_exists("tempfile")) {
				unlink("tempfile");
			}
		});

		parent::setUp();
	}

	public function testIniWithSections() {
		$c = new IniParser();
		$c->readFromString($this->baseDataWithSections
		);

		$this->assertEquals($this->baseDataWithSectionsResult, $c->getRawData());
		$this->assertEquals($this->baseDataWithSections, trim($c->buildOutput()));
	}

	public function testIniWithoutSections() {
		$c = new IniParser();
		$c->readFromString($this->baseDataWithSections, false);

		$this->assertEquals(['default' => ['key1' => 'value2', 'key2' => 'value1']], $c->getRawData());
		$this->assertEquals(<<<EOF
key1=value2
key2=value1
EOF
			, trim($c->buildOutput(false)));
	}

	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Failed to read file /does-not-exist: file_get_contents(/does-not-exist): failed to open stream: No such file or directory.
	 */
	public function testExceptionOnUnreadableFile() {
		$c = new IniParser();
		$c->readFromFile("/does-not-exist");
	}

	/**
	 * @requires testIniWithSections
	 */
	public function testWriteAndReadFile() {
		$c = new IniParser();
		$c->readFromString($this->baseDataWithSections);

		$this->assertTrue($c->write(true, "tempfile"), "File was not written properly");

		$c->readFromFile("tempfile", true);
		$this->assertEquals($this->baseDataWithSectionsResult, $c->getRawData());

		unlink("tempfile");
	}

	public function testHas() {
		$c = new IniParser();
		$c->readFromString($this->baseDataWithSections);

		$this->assertEquals($this->baseDataWithSectionsResult, $c->getRawData());
		$this->assertTrue($c->has("Section1"));
		$this->assertFalse($c->has("Section3"));
		$this->assertTrue($c->has("Section1", "key1"));
		$this->assertFalse($c->has("Section1", "key3"));
		$this->assertFalse($c->has(null, "key1"));

		$c->readFromString(<<<EOF
key1=value2
key2=value1
EOF
		);

		$this->assertFalse($c->has("Section1"));
		$this->assertTrue($c->has(null, "key1"));
	}

	public function testReadValuesWithSpaces() {
		$c = new IniParser();
		$c->readFromString(<<<EOF
key1 = value2
key2 = value1
EOF
			, false);

		$this->assertEquals(['default' => ['key1' => 'value2', 'key2' => 'value1']], $c->getRawData());
	}

	public function testEmpty() {
		$this->assertEquals([], (new IniParser())->readFromString("")->getRawData());
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testExceptionOnWrite() {
		(new IniParser())->write();
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionOnHas() {
		(new IniParser())->has();
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionOnRemove() {
		(new IniParser())->remove();
	}

	public function testSet() {
		$c = (new IniParser())
			->set("Section1", "key1", "value1")
			->set("Section1", "key2", "")
			->set("Section2", "key1", "value2")
			->set("Section2", "key2", "value1");

		$this->assertEquals($this->baseDataWithSectionsResult, $c->getRawData());

		$c = (new IniParser())
			->set(null, "key1", "value1")
			->set(null, "key2", "value2");

		$this->assertEquals(['default' => ['key1' => 'value1', 'key2' => 'value2']], $c->getRawData());
	}

	public function testRemove() {
		$c = (new IniParser())->readFromString($this->baseDataWithSections);

		$c->remove("Section1", "key1");
		$this->assertEquals(['Section1' => ['key2' => ''], 'Section2' => ['key1' => 'value2', 'key2' => 'value1']], $c->getRawData());

		$c->remove("Section2");
		$this->assertEquals(['Section1' => ['key2' => '']], $c->getRawData());

		$c->readFromString(<<<EOF
key1=value1
key2=value2
EOF
		);
		$this->assertEquals(['default' => ['key1' => 'value1', 'key2' => 'value2']], $c->getRawData());

		$c->remove(null, "key1");
		$this->assertEquals(['default' => ['key2' => 'value2']], $c->getRawData());
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionOnRemoveWhenNoSectionsAreBeingUsed() {
		$c = (new IniParser())->readFromString("key1=value1");

		$c->remove("Section1", "key1");
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionOnRemovingWholeSectionWhenNoSectionsAreBeingUsed() {
		$c = (new IniParser())->readFromString("key1=value1");

		$c->remove("Section1");
	}
}
