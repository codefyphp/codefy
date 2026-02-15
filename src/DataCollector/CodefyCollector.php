<?php

declare(strict_types=1);

namespace Codefy\Framework\DataCollector;

use Codefy\Framework\Proxy\Codefy;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Qubus\Exception\Exception;

use function phpversion;
use function rtrim;
use function str_replace;

class CodefyCollector extends DataCollector implements Renderable
{
    /**
     * @return array<array-key, mixed>
     * @throws Exception
     */
    public function collect(): array
    {
        return [
            "version" => Codefy::$PHP::APP_VERSION,
            'tooltip' => [
                'Codefy Version' => Codefy::$PHP::APP_VERSION,
                'PHP Version' => phpversion(),
                'Environment' => Codefy::$PHP->getEnvironment(),
                'Debug Mode' => Codefy::$PHP->configContainer->getConfigKey(key: 'app.debug') === true
                        ? 'Enabled' : 'Disabled',
                'URL' => rtrim(
                    string: str_replace(
                        search: ['http://', 'https://'],
                        replace: '',
                        subject: Codefy::$PHP->configContainer->getConfigKey(key: 'app.url')
                    ),
                    characters: '/'
                ),
                'Timezone' => Codefy::$PHP->configContainer->getConfigKey(key: 'app.timezone'),
                'Locale' => Codefy::$PHP->configContainer->getConfigKey(key: 'app.locale'),
            ],
        ];
    }

    public function getName(): string
    {
        return 'codefy';
    }

    /**
     * @return array<array-key, array<array-key, string>>
     */
    public function getWidgets(): array
    {
        return [
            "version" => [
                "icon" => "laptop-code",
                "map" => "codefy.version",
                "default" => "",
            ],
            "version:tooltip" => [
                "map" => "codefy.tooltip",
                "default" => "{}",
            ],
        ];
    }
}
