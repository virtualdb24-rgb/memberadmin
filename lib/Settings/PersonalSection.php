<?php

declare(strict_types=1);

namespace OCA\MemberAdmin\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class PersonalSection implements IIconSection {
	private IL10N $l;
	private IURLGenerator $url;

	public function __construct(IURLGenerator $url, IL10N $l) {
		$this->url = $url;
		$this->l = $l;
	}

	#[\Override]
	public function getID(): string {
		return 'memberadmin';
	}

	#[\Override]
	public function getName(): string {
		return str_starts_with(strtolower($this->l->getLanguageCode()), 'fr')
			? 'Gestion des membres'
			: 'Member management';
	}

	#[\Override]
	public function getPriority(): int {
		return 40;
	}

	#[\Override]
	public function getIcon(): string {
		return $this->url->imagePath('memberadmin', 'app.svg');
	}
}
