<?php declare(strict_types=1);
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 $Asikart.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Database\Monitor;

/**
 * The NullMonitor class.
 *
 * @since  3.5
 */
class NullMonitor implements QueryMonitorInterface
{
    /**
     * Start a query monitor.
     *
     * @param string $query The SQL to be executed.
     *
     * @return  void
     *
     * @since  3.5
     */
    public function start(string $query): void
    {
        //
    }

    /**
     * Stop query monitor.
     *
     * @return  void
     *
     * @since  3.5
     */
    public function stop(): void
    {
        //
    }
}
