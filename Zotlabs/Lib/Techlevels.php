<?php

namespace Zotlabs\Lib;


class Techlevels {

	static public function levels() {
		$techlevels = [
			'0' => t('0. Beginner/Basic'),
			'1' => t('1. Novice - not skilled but willing to learn'),
			'2' => t('2. Intermediate - somewhat comfortable'),
			'3' => t('3. Advanced - very comfortable'),
			'4' => t('4. Expert - I can write computer code'),			
			'5' => t('5. Wizard - I probably know more than you do')
		];
		return $techlevels;
	}

}

