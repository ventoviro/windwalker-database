<?php declare(strict_types=1);
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Database\Test;

use Windwalker\Database\Driver\Pdo\PdoHelper;

/**
 * The DsnResolver class.
 *
 * @since  2.0
 */
abstract class TestDsnResolver
{
    /**
     * getDsn
     *
     * @param string $driver
     *
     * @return  array|bool
     */
    public static function getDsn($driver)
    {
        $const = 'WINDWALKER_TEST_DB_DSN_' . strtoupper($driver);

        // First let's look to see if we have a DSN defined or in the environment variables.
        if (defined($const) || getenv($const)) {
            $dsn = defined($const) ? constant($const) : getenv($const);
        } else {
            return false;
        }

        return PdoHelper::extractDsn($dsn);
    }
}
