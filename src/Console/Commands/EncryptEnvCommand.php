<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Qubus\Http\Encryption\Env\File;
use Symfony\Component\Console\Input\InputOption;

use function Codefy\Framework\Helpers\base_path;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function preg_match;
use function preg_replace_callback;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

class EncryptEnvCommand extends ConsoleCommand
{
    protected string $name = 'encrypt:env';

    protected string $description = 'Encrypts .env data and saves the encrypted data to a new file.';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption(
                name: 'env',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'Set the environment suffix to encrypt. Example: --env=production encrypts .env.production.'
            )
            ->addOption(
                name: 'file',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'Set the exact environment file path to encrypt.'
            );
    }

    public function handle(): int
    {
        $this->terminalRaw(string: 'Encrypting data and creating file . . .');

        $tempFile = null;

        try {
            $savedKeyString = file_get_contents(filename: base_path(path: '.enc.key'));
            $key = Key::loadFromAsciiSafeString(saved_key_string: $savedKeyString);

            $file = $this->resolveEnvFile();

            if (! file_exists($file)) {
                $this->output->writeln(sprintf('<error>File %s does not exist.</error>', $file));
                return self::FAILURE;
            }

            $contents = file_get_contents($file);
            $expanded = $this->expandEnvironmentVariables($contents);

            $tempFile = tempnam(sys_get_temp_dir(), 'codefy-env-');

            if ($tempFile === false) {
                $this->output->writeln('<error>Unable to create temporary env file.</error>');
                return self::FAILURE;
            }

            file_put_contents($tempFile, $expanded);

            File::encrypt($tempFile, base_path(path: '.env.enc'), $key);
        } catch (BadFormatException | EnvironmentIsBrokenException $e) {
            $this->output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return self::FAILURE;
        } finally {
            if ($tempFile !== null && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        $this->terminalRaw(string: '<comment>.env.enc</comment> file created.');

        return self::SUCCESS;
    }

    private function resolveEnvFile(): string
    {
        $customFile = $this->getOptions('file');

        if ($customFile) {
            return $this->normalizeEnvFilePath($customFile);
        }

        $env = $this->getOptions('env');

        if ($env) {
            return base_path(path: sprintf('.env.%s', $env));
        }

        return base_path(path: '.env');
    }

    private function normalizeEnvFilePath(string $file): string
    {
        if ($this->isAbsolutePath($file)) {
            return $file;
        }

        return base_path(path: $file);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
        || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    }

    private function expandEnvironmentVariables(string $contents): string
    {
        $variables = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (! preg_match('/^\s*(?:export\s+)?([A-Z0-9_]+)\s*=\s*(.*)\s*$/i', $line, $matches)) {
                continue;
            }

            $key = $matches[1];
            $value = trim($matches[2]);

            $value = $this->stripInlineComment($value);
            $value = $this->stripQuotes($value);
            $value = $this->expandValue($value, $variables);

            $variables[$key] = $value;
        }

        return preg_replace_callback(
            '/(^\s*(?:export\s+)?[A-Z0-9_]+\s*=\s*)(.*)$/im',
            function (array $matches) use ($variables): string {
                $prefix = $matches[1];
                $value = trim($matches[2]);

                $quote = '';

                if (
                        (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $quote = $value[0];
                    $value = substr($value, 1, -1);
                }

                $expanded = $this->expandValue($value, $variables);

                return $prefix . ($quote ? $quote . $expanded . $quote : $expanded);
            },
            $contents
        );
    }

    private function expandValue(string $value, array $variables): string
    {
        return preg_replace_callback(
            '/\$\{([A-Z0-9_]+)\}|\$([A-Z0-9_]+)/i',
            function (array $matches) use ($variables): string {
                $key = $matches[1] ?: $matches[2];

                return $variables[$key]
                    ?? getenv($key)
                    ?: $matches[0];
            },
            $value
        );
    }

    private function stripQuotes(string $value): string
    {
        if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    private function stripInlineComment(string $value): string
    {
        if (str_starts_with($value, '"') || str_starts_with($value, "'")) {
            return $value;
        }

        return preg_replace('/\s+#.*$/', '', $value) ?? $value;
    }
}
