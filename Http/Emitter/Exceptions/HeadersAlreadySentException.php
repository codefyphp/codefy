<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Emitter\Exceptions;

use Throwable;

class HeadersAlreadySentException extends EmitterException
{
    /**
     * @param string $headersSentFile PHP source file name where output started in
     * @param int $headersSentLine Line number in the PHP source file name where
     * output started in
     * @param int $code
     * @param null|Throwable $previous
     * @return void
     */
    public function __construct(
        public private(set) string $headersSentFile {
            get => $this->headersSentFile;
        },
        public private(set) int $headersSentLine {
            get => $this->headersSentLine;
        },
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $msg = sprintf('Headers already sent in file %s on line %s.', $headersSentFile, $headersSentLine);
        parent::__construct($msg, $code, $previous);
    }
}
