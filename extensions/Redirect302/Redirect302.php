<?php
$wgHooks['InitializeArticleMaybeRedirect'][] = 'redirectHook';

/**
 * Copied from:
 * https://meta.wikimedia.org/wiki/Help:Setting_up_client-side_redirects
 */
function redirectHook($title, $request, &$ignoreRedirect, &$target, &$article)
{
	if (!$ignoreRedirect && $article->isRedirect()) {
		if (($target = $article->followRedirect()) instanceof Title) {
			$target = $target->getFullURL();
		}
	}
	return true;
}
