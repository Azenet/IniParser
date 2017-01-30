<?php

use Azenet\IniParser\IniParser;
use PHPUnit\Framework\TestCase;

/**
 * Class IniParserTest
 *
 * @covers Azenet\IniParser\IniParser
 */
class IniParserTest extends TestCase {
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
		$c->readFromString(<<<EOF
[Section1]
key1=value1
key2=

[Section2]
key1=value2
key2=value1
EOF
		);

		$this->assertEquals(['Section1' => ['key1' => 'value1', 'key2' => ''], 'Section2' => ['key1' => 'value2', 'key2' => 'value1']], $c->getRawData());
		$this->assertEquals(<<<EOF
[Section1]
key1=value1
key2=

[Section2]
key1=value2
key2=value1
EOF
			, trim($c->buildOutput()));
	}

	public function testIniWithoutSections() {
		$c = new IniParser();
		$c->readFromString(<<<EOF
[Section1]
key1=value1
key2=

[Section2]
key1=value2
key2=value1
EOF
			, false);

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
	public function testWriteFile() {
		$c = new IniParser();
		$c->readFromString(<<<EOF
[Section1]
key1=value1
key2=

[Section2]
key1=value2
key2=value1
EOF
		);

		$this->assertTrue($c->write(true, "tempfile"), "File was not written properly");
	}

	/**
	 * @requires testWriteFile
	 */
	public function testReadFile() {
		$c = new IniParser();
		$c->readFromFile("tempfile", true);
		$this->assertEquals(['Section1' => ['key1' => 'value1', 'key2' => ''], 'Section2' => ['key1' => 'value2', 'key2' => 'value1']], $c->getRawData());
	}
}
