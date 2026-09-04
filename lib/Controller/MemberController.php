<?php

declare(strict_types=1);

namespace OCA\MemberAdmin\Controller;

use OCA\MemberAdmin\Service\Allowlist;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * API restreinte : un utilisateur autorise ne peut AJOUTER ou RETIRER des
 * membres que dans les groupes qui lui ont ete attribues par l'admin.
 * Aucune autre action sur les comptes n'est exposee.
 */
class MemberController extends Controller {
	private Allowlist $allowlist;
	private IGroupManager $groupManager;
	private IUserManager $userManager;
	private IUserSession $session;
	private LoggerInterface $logger;

	public function __construct(
		string $appName,
		IRequest $request,
		Allowlist $allowlist,
		IGroupManager $groupManager,
		IUserManager $userManager,
		IUserSession $session,
		LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
		$this->allowlist = $allowlist;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->session = $session;
		$this->logger = $logger;
	}

	private function currentUid(): ?string {
		$user = $this->session->getUser();
		return $user ? $user->getUID() : null;
	}

	/** Groupes dont l'utilisateur courant est "admin de groupe" (sub-admin Nextcloud). */
	private function subAdminGroups(): array {
		$user = $this->session->getUser();
		if ($user === null) {
			return [];
		}
		$ids = [];
		try {
			$sub = $this->groupManager->getSubAdmin();
			foreach ($sub->getSubAdminsGroups($user) as $group) {
				if ($group->getGID() !== 'admin') {
					$ids[] = $group->getGID();
				}
			}
		} catch (\Throwable $e) {
			$this->logger->warning('memberadmin: lecture sub-admin groupes impossible: ' . $e->getMessage());
		}
		return $ids;
	}

	/**
	 * Groupes gerables pour l'utilisateur courant :
	 *  = groupes delegues (allowlist occ memberadmin:grant)
	 *    UNION groupes dont il est admin de groupe (sub-admin)
	 */
	private function allowedGroupsFor(string $uid): array {
		return array_values(array_unique(array_merge(
			$this->allowlist->groupsFor($uid),
			$this->subAdminGroups()
		)));
	}

	private function isAllowedFor(string $uid, string $gid): bool {
		return in_array($gid, $this->allowedGroupsFor($uid), true);
	}

	/**
	 * Liste les groupes gerables pour l'utilisateur courant, avec leurs membres.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index(): DataResponse {
		$uid = $this->currentUid();
		if ($uid === null) {
			return new DataResponse(['error' => 'unauthorized'], 401);
		}
		$out = [];
		foreach ($this->allowedGroupsFor($uid) as $gid) {
			$group = $this->groupManager->get($gid);
			if ($group === null) {
				continue;
			}
			$members = array_values(array_map(static fn ($u) => $u->getUID(), $group->getUsers()));
			$out[] = ['gid' => $gid, 'members' => $members];
		}
		return new DataResponse($out);
	}

	/**
	 * Recherche d'utilisateurs via l'API native de Nextcloud (deleguee au
	 * backend : local, LDAP/AD, OIDC) : paginee et limitee, sans enumeration
	 * de tout l'annuaire.
	 *
	 * @NoAdminRequired
	 * 
	 */
	public function search(string $term = ''): DataResponse {
		if ($this->currentUid() === null) {
			return new DataResponse(['error' => 'unauthorized'], 401);
		}
		$term = trim($term);
		if ($term === '') {
			return new DataResponse([]);
		}
		$res = [];
		$users = $this->userManager->search($term, 20);
		foreach ($users as $user) {
			if (count($res) >= 20) {
				break;
			}
			if ($user->getUID() === 'admin') {
				continue;
			}
			$res[] = $user->getUID();
		}
		return new DataResponse($res);
	}

	/**
	 * Ajoute un membre dans un groupe autorise.
	 *
	 * @NoAdminRequired
	 * 
	 */
	public function add(string $gid): DataResponse {
		$uid = $this->currentUid();
		if ($uid === null) {
			return new DataResponse(['error' => 'unauthorized'], 401);
		}
		if ($gid === '' || $gid === 'admin' || !$this->isAllowedFor($uid, $gid)) {
			return new DataResponse(['error' => 'forbidden'], 403);
		}
		$group = $this->groupManager->get($gid);
		if ($group === null) {
			return new DataResponse(['error' => 'group not found'], 404);
		}
		$target = (string)$this->request->getParam('userId', '');
		$target = trim($target);
		if ($target === '') {
			return new DataResponse(['error' => 'missing userId'], 400);
		}
		$user = $this->userManager->get($target);
		if ($user === null) {
			return new DataResponse(['error' => 'user not found'], 404);
		}
		if (!$group->inGroup($user)) {
			$group->addUser($user);
		}
		$this->logger->info("memberadmin: {owner} a ajoute {user} au groupe {group}", [
			'owner' => $uid, 'user' => $target, 'group' => $gid,
		]);
		return new DataResponse(['ok' => true]);
	}

	/**
	 * Retire un membre d'un groupe autorise.
	 *
	 * @NoAdminRequired
	 */
	public function remove(string $gid, string $userId): DataResponse {
		$uid = $this->currentUid();
		if ($uid === null) {
			return new DataResponse(['error' => 'unauthorized'], 401);
		}
		if ($gid === '' || $gid === 'admin' || !$this->isAllowedFor($uid, $gid)) {
			return new DataResponse(['error' => 'forbidden'], 403);
		}
		$group = $this->groupManager->get($gid);
		if ($group === null) {
			return new DataResponse(['error' => 'group not found'], 404);
		}
		if ($userId === $uid) {
			return new DataResponse(['error' => 'self-removal'], 400);
		}
		$user = $this->userManager->get($userId);
		if ($user === null) {
			return new DataResponse(['error' => 'user not found'], 404);
		}
		if ($group->inGroup($user)) {
			$group->removeUser($user);
		}
		$this->logger->info("memberadmin: {owner} a retire {user} du groupe {group}", [
			'owner' => $uid, 'user' => $userId, 'group' => $gid,
		]);
		return new DataResponse(['ok' => true]);
	}
}
