<?php

/**
 * CodefyPHP
 *
 * @link       https://github.com/codefyphp/codefy
 * @copyright  2022
 * @author     Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      0.1.0
 */

declare(strict_types=1);

namespace Codefy\Traits;

use Codefy\Domain\EventSourcing\DomainEvent;

trait SubscriberAware
{
    /** {@inheritDoc} */
    public function isSubscribedTo(DomainEvent $event): bool
    {
        return in_array(get_class($event), $this->eventType);
    }
}
