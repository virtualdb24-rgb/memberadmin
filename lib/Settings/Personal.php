<?php

declare(strict_types=1);

namespace OCA\MemberAdmin\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\ISettings;
use OCP\Util;

class Personal implements ISettings {
	private IL10N $l10n;

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		Util::addScript('memberadmin', 'main');
		Util::addStyle('memberadmin', 'style');
		return new TemplateResponse('memberadmin', 'personal', [
			'lang' => str_starts_with(strtolower($this->l10n->getLanguageCode()), 'fr') ? 'fr' : 'en',
		], 'blank');
	}

	#[\Override]
	public function getSection(): string {
		return 'memberadmin';
	}

	#[\Override]
	public function getPriority(): int {
		return 50;
	}
}
