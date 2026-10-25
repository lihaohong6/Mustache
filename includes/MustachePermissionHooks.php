<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Permissions\Hook\GetUserPermissionsErrorsHook;

class MustachePermissionHooks implements GetUserPermissionsErrorsHook {

	private const array PROTECTED_ACTIONS = [
		'edit',
		'create',
		'move',
		'move-target',
		'delete',
		'undelete',
	];

	/**
	 * @inheritDoc
	 */
	public function onGetUserPermissionsErrors( $title, $user, $action, &$result ) {
		// Namespace protection is not enough because the user can unset it in ManageWiki
		if ( !$title->inNamespace( NS_HTML ) || !in_array( $action, self::PROTECTED_ACTIONS, true ) ) {
			return true;
		}

		if ( $user->isAllowedAll( 'editsitecss', 'editsitejs' ) ) {
			return true;
		}

		// Use existing i18n message from core
		$result = [ 'siterawhtmlprotected', $action ];
		return false;
	}
}
