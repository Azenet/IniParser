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

	public function readFromString($content, $withSections = true, $allowRepeatedValues = true) {
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

			$key   = trim($matches[1]);
			$value = trim($matches[2]);

			if (null === $currentSection || !$withSections) {
				$currentSection = "default";
			}

			if (!isset($this->sections[$currentSection])) {
				$this->sections[$currentSection] = [];
			}

			if (isset($this->sections[$currentSection][$key]) && $allowRepeatedValues) { //repeated value
				if (!is_array($this->sections[$currentSection][$key])) {
					$lastValue                             = $this->sections[$currentSection][$key];
					$this->sections[$currentSection][$key] = [$lastValue, $value];
				} else {
					$this->sections[$currentSection][$key][] = $value;
				}
			} else {
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
					if (is_array($value)) {
						foreach ($value as $val) {
							$out .= sprintf("%s=%s\n", $key, $val);
						}
					} else {
						$out .= sprintf("%s=%s\n", $key, $value);
					}
				}

				$out .= "\n";
			}
		} else if (isset($this->sections['default'])) {
			foreach ($this->sections['default'] as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $val) {
						$out .= sprintf("%s=%s\n", $key, $val);
					}
				} else {
					$out .= sprintf("%s=%s\n", $key, $value);
				}
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
			if (!isset($this->sections['default'])) {
				$this->sections['default'] = [];
			}

			$this->sections['default'][$key] = $value;
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

	public function remove($section = null, $key = null) {
		if (null === $section && null === $key) {
			throw new \InvalidArgumentException("Section and Key cannot be both null.");
		} else if (null !== $section && null === $key) {
			if (count($this->sections) === 1 && isset($this->sections['default'])) {
				throw new \InvalidArgumentException("Sections are not being used.");
			}

			if ($this->has($section)) {
				unset($this->sections[$section]);
			}
		} else if (null === $section && null !== $key) {
			if ($this->has(null, $key)) {
				unset($this->sections['default'][$key]);
			}
		} else {
			if (count($this->sections) === 1 && isset($this->sections['default'])) {
				throw new \InvalidArgumentException("Sections are not being used.");
			}

			if ($this->has($section, $key)) {
				unset($this->sections[$section][$key]);
			}
		}
	}
}
