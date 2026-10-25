<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Permissions\Hook\GetUserPermissionsErrorsHook;
use MediaWiki\Permissions\PermissionManager;

class MustachePermissionHooks implements GetUserPermissionsErrorsHook {

	private const array PROTECTED_ACTIONS = [
		'edit',
		'create',
		'move',
		'move-target',
		'delete',
		'undelete',
	];

	public function __construct(
		private readonly PermissionManager $permissionManager
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onGetUserPermissionsErrors( $title, $user, $action, &$result ) {
		// Namespace protection is not enough because the user can unset it in ManageWiki
		if ( !$title->inNamespace( NS_HTML ) || !in_array( $action, self::PROTECTED_ACTIONS, true ) ) {
			return true;
		}

		// Formerly isAllowedAll. MW 1.47 requires recent reauthentication for these rights which cannot be handled
		// properly by this extension for now.
		$rights = $this->permissionManager->getUserPermissions( $user );
		if ( in_array( 'editsitecss', $rights ) && in_array( 'editsitejs', $rights ) ) {
			return true;
		}

		// Use existing i18n message from core
		$result = [ 'siterawhtmlprotected', $action ];
		return false;
	}
}
