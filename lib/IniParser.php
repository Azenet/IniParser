<?php

namespace Azenet\IniParser;

class IniParser {
	private $filename;
	private $sections;

	public function readFromFile($filename, $withSections = true) {
		$this->filename = $filename;
		$content        = @file_get_contents($this->filename);

		if (false === $content) {
			throw new \RuntimeException(sprintf("Failed to read file %s: %s.", $this->filename, error_get_last()['message']));
		}

		return $this->readFromString($content, $withSections);
	}

	public function readFromString($content, $withSections = true) {
		$this->sections = [];

		if (empty($content)) {
			return $this;
		}

		$currentSection = null;
		foreach (explode("\n", $content) as $line) {
			if (preg_match('/^\s*\[([^\]]+)\]\s*$/', $line, $matches)) {
				$currentSection = $matches[1];
				continue;
			}

			if (empty($line) || !preg_match('/^\s*([^=]+)\s*=\s*(.*)\s*$/', $line, $matches)) {
				continue;
			}

			$key = trim($matches[1]);
			$value = trim($matches[2]);

			if ($withSections) {
				if (null === $currentSection) {
					$currentSection = "default";
				}

				if (!isset($this->sections[$currentSection])) {
					$this->sections[$currentSection] = [];
				}

				$this->sections[$currentSection][$key] = $value;
			} else {
				$currentSection = "default";

				if (!isset($this->sections[$currentSection])) {
					$this->sections[$currentSection] = [];
				}

				$this->sections[$currentSection][$key] = $value;
			}
		}

		return $this;
	}

	public function getRawData() {
		return $this->sections;
	}

	public function buildOutput($withSections = true) {
		$out = "";

		if ($withSections) {
			foreach ($this->sections as $sectionName => $data) {
				$out .= sprintf("[%s]\n", $sectionName);

				foreach ($data as $key => $value) {
					$out .= sprintf("%s=%s\n", $key, $value);
				}

				$out .= "\n";
			}
		} else if (isset($this->sections['default'])) {
			foreach ($this->sections['default'] as $key => $value) {
				$out .= sprintf("%s=%s\n", $key, $value);
			}
		}

		return $out;
	}

	public function write($withSections = true, $filenameToUse = null) {
		if (null === $filenameToUse && null === $this->filename) {
			throw new \RuntimeException("Filename is required when the INI was loaded from string.");
		}

		return !!file_put_contents($filenameToUse !== null ? $filenameToUse : $this->filename, $this->buildOutput($withSections));
	}

	public function set($section, $key, $value) {
		if (null === $this->sections) {
			$this->sections = [];
		}

		if (null === $section) {
			$this->sections[$key] = $value;
		} else {
			if (!isset($this->sections[$section])) {
				$this->sections[$section] = [];
			}

			$this->sections[$section][$key] = $value;
		}

		return $this;
	}

	public function has($section = null, $key = null) {
		if (null === $section && null === $key) {
			throw new \InvalidArgumentException("Section and Key cannot be both null.");
		} else if (null !== $section && null === $key) {
			return isset($this->sections[$section]);
		} else if (null === $section && null !== $key && isset($this->sections['default'])) {
			return isset($this->sections['default'][$key]);
		} else {
			return isset($this->sections[$section]) && isset($this->sections[$section][$key]);
		}
	}
}
