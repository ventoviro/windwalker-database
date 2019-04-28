<?php declare(strict_types=1);
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Database\Driver;

/**
 * The DatabaseDriverInterface class.
 *
 * @since  2.1
 */
interface DatabaseDriverInterface
{
    /**
     * Is this driver supported.
     *
     * @return  boolean
     */
    public static function isSupported();
}
