<?php
/**
 * Legacy URL: customer login is SSO-only. Redirect to SSO start.
 */
header('Location: sso/start.php', true, 302);
exit;
