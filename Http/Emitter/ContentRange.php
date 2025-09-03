<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Emitter;

use Codefy\Framework\Http\Emitter\Exceptions\EmitterException;

class ContentRange
{
    /**
     * @param int $start     An integer in the given unit indicating the beginning
     *                       of the request range.
     * @param int $end       An integer in the given unit indicating the end of the
     *                       requested range.
     * @param null|int $size The total size of the document.
     * @param string $unit   The unit in which ranges are specified. This is
     *                       usually `bytes`.
     * @return void
     * @throws EmitterException
     */
    public function __construct(
        private int $start,
        private int $end,
        private ?int $size = null,
        private string $unit = 'bytes'
    ) {
        $this->setStart($start)
            ->setEnd($end);
    }

    /**
     * Get the unit in which ranges are specified. This is usually bytes.
     *
     * @return  string
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * Set the unit in which ranges are specified. This is usually bytes.
     *
     * @param string $unit The unit in which ranges are specified. This is
     *                     usually bytes.
     * @return ContentRange
     */
    public function setUnit(string $unit): ContentRange
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * Get the beginning of the request range.
     *
     * @return  int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * Set the beginning of the request range.
     *
     * @param int $start the beginning of the request range.
     * @return ContentRange
     * @throws EmitterException
     */
    public function setStart(int $start): ContentRange
    {
        if ($start < 0) {
            throw new EmitterException("Range start value must be positive integer");
        }

        $this->start = $start;

        return $this;
    }

    /**
     * Get an integer in the given unit indicating
     * the end of the requested range.
     *
     * @return  int
     */
    public function getEnd(): int
    {
        return $this->end;
    }

    /**
     * Set an integer in the given unit indicating
     * the end of the requested range.
     *
     * @param  int  $end  An integer in the given unit indicating the
     *                    end of the requested range.
     * @return  ContentRange
     */
    public function setEnd(int $end): ContentRange
    {
        if ($end < 0) {
            throw new EmitterException(
                "Range end value must be positive integer"
            );
        }

        $this->end = $end;
        return $this;
    }

    /**
     * Get the total size of the document.
     *
     * @return  int|null
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Set the total size of the document.
     *
     * @param  int|null $size The total size of the document.
     * @return  self
     */
    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }
}
