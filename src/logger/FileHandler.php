<?php declare(strict_types=1);

namespace dvikan\SimpleParts;

final class FileHandler
{
    private $file;

    public function __construct(TextFile $file)
    {
        $this->file = $file;
    }

    public function handle(array $record): void
    {
        try {
            $context = Json::encode($record['context']);
        } catch (SimpleException $e) {
            $context = 'Unable to encode context as json';
        }

        $item = sprintf(
            "[%s] %s.%s %s %s\n",
            $record['datetime']->format('Y-m-d H:i:s'),
            $record['channel'],
            $record['level_name'],
            $record['message'],
            $context,
        );

        $this->file->append($item);
    }
}
