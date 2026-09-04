<?php

declare(strict_types=1);

namespace OCA\MemberAdmin\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Util;

class PageController extends Controller {
	private IUserSession $session;
	private IL10N $l10n;

	public function __construct(
		string $appName,
		IRequest $request,
		IUserSession $session,
		IL10N $l10n,
	) {
		parent::__construct($appName, $request);
		$this->session = $session;
		$this->l10n = $l10n;
	}

	/**
	 * Page "Gestion des membres".
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index(): TemplateResponse {
		Util::addScript('memberadmin', 'main');
		Util::addStyle('memberadmin', 'style');
		return new TemplateResponse('memberadmin', 'index', [
			'lang' => $this->userLang(),
		], 'user');
	}

	private function userLang(): string {
		$user = $this->session->getUser();
		if ($user !== null) {
			$lang = $this->l10n->getLanguageCode();
			if (str_starts_with(strtolower($lang), 'fr')) {
				return 'fr';
			}
		}
		return 'en';
	}
}
