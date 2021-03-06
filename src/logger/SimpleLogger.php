<?php
declare(strict_types=1);

namespace dvikan\SimpleParts;

final class SimpleLogger implements Logger
{
    private $name;
    private $handlers;
    private $clock;

    /**
     * @param Handler[] $handlers
     */
    public function __construct(string $name, array $handlers, Clock $clock = null)
    {
        $this->name = $name;
        $this->handlers = $handlers;
        $this->clock = $clock ?? new SystemClock();
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function log(int $level, string $message, array $context = []): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle([
                'name'          => $this->name,
                'created_at'    => $this->clock->now(),
                'level'         => $level,
                'level_name'    => self::LEVEL_NAMES[$level],
                'message'       => $message,
                'context'       => $context,
            ]);
        }
    }
}
