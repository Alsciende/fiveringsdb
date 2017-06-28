<?php

namespace AppBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Description of AbstractSlotCollectionDecorator
 *
 * @author Alsciende <alsciende@icloud.com>
 */
abstract class AbstractSlotCollectionDecorator extends ArrayCollection
{
    public function __construct (array $elements = [])
    {
        parent::__construct($elements);
    }

    /**
     * @return SlotInterface[]
     */
    public function toArray (): array
    {
        return parent::toArray();
    }

    /**
     * Iterates over elements of the collection, returning the first element $p returns thruthly for.
     * The predicate is invoked with three arguments: ($value, $index|$key, $collection).
     */
    public function find (\Closure $p): ?SlotInterface
    {
        foreach ($this as $key => $element) {
            if (call_user_func($p, $element, $key, $this)) {
                return $element;
            }
        }

        return null;
    }

    public function countElements (): int
    {
        $count = 0;
        foreach ($this->toArray() as $slot) {
            $count += $slot->getQuantity();
        }

        return $count;
    }

    public function getContent (): array
    {
        $content = [];
        foreach ($this->toArray() as $slot) {
            $content[$slot->getElement()->getCode()] = $slot->getQuantity();
        }
        ksort($content);

        return $content;
    }
}
