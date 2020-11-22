<?php declare(strict_types=1);

namespace dvikan\SimpleParts;

use function error_get_last;
use function register_shutdown_function;
use function set_error_handler;
use function set_exception_handler;

final class ErrorHandler
{
    private const ERROR_STRINGS = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_RECOVERABLE_ERROR => 'Recoverable error',
        E_DEPRECATED => 'Deprecated',
    ];

    private const LEVEL_MAP = [
        E_NOTICE => Logger::INFO,
        E_WARNING => Logger::WARNING,
    ];

    private const EXIT_MAP = [
        E_NOTICE => true,
        E_WARNING => true,
    ];

    /** @var Logger */
    private $logger;

    private function __construct() {}

    public static function create(Logger $logger = null)
    {
        $handler = new self();

        $handler->logger = $logger ?: new SimpleLogger();

        set_error_handler([$handler, 'handleError']);
        set_exception_handler([$handler, 'handleException']);
        register_shutdown_function([$handler, 'handleShutdown']);

        return $handler;
    }

    public function handleError($code, $message, $file, $line)
    {
        $this->logger->log(
            self::LEVEL_MAP[$code] ?? Logger::ERROR,
            sprintf(
                '%s: %s in %s:%s',
                self::ERROR_STRINGS[$code] ?? (string) $code,
                $message,
                $file,
                $line
            )
        );

        if (self::EXIT_MAP[$code]) {
            exit(1);
        }
    }

    public function handleException($e)
    {
        $this->logger->log(
            Logger::ERROR, // An exception is always an error
            sprintf(
                'Uncaught Exception %s: "%s" at %s line %s',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        exit(1);
    }

    public function handleShutdown()
    {
        $err = error_get_last();

        if ($err) {
            $this->logger->log(
                self::LEVEL_MAP[$err['type']] ?? Logger::ERROR,
                sprintf(
                    '%s: %s in %s:%s',
                    self::ERROR_STRINGS[$err['type']] ?? (string)($err['type']),
                    $err['message'],
                    $err['file'],
                    $err['line']
                )
            );
        }
    }
}