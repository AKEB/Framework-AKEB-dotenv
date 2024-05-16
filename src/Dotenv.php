<?php

namespace AKEB;

class Dotenv {

	static public function load($path, $filename='.env'): bool {
		if (!file_exists($path . '/' . $filename)) return false;
		$env = parse_ini_file($path . '/' . $filename);
		foreach ($env as $key => $value) {
			putenv(sprintf('%s=%s', $key, $value));
			$_ENV[$key] = $value;
		}
		return true;
	}

}