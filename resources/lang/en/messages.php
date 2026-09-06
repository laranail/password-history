<?php

declare(strict_types=1);

/*
 * Namespaced `laranail/password-history::`, so publishing lands in
 * lang/vendor/laranail-password-history. The reuse message never says
 * WHICH previous password matched — the rule is not an oracle.
 */
return [
    'reused'      => 'The :attribute has been used recently. Choose a password you have not used before.',
    'unavailable' => 'The :attribute could not be verified against your password history. Try again shortly.',
];
