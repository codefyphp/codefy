<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Gettext\Loader\MoLoader;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Qubus\Exception\Exception;

use function file_exists;
use function sprintf;

class LocalizationServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws Exception
     */
    public function register(): void
    {
        if ($this->codefy->isRunningInConsole()) {
            return;
        }

        // Instantiate MoLoader for loading .mo files.
        $loader = new MoLoader();
        // Retrieve the current locale.
        $locale = $this->codefy->configContainer->getConfigKey(key: 'app.locale');
        // Retrieve the current locale domain.
        $domain = $this->codefy->configContainer->getConfigKey(key: 'app.locale_domain');
        // Set translation array for push.
        $translations = [];
        // Relative path to domain file.
        $domainFilename = sprintf('%s/%s-%s.mo', $locale, $domain, $locale);
        // Absolute path to the .mo file.
        $mofile = $this->codefy->localePath() . $this->codefy::DS . $domainFilename;

        if (file_exists($mofile)) {
            $translations[] = $loader->loadFile(filename: $mofile)->setDomain(domain: $domain);
        }

        $gettext = Translator::createFromTranslations(...$translations);

        TranslatorFunctions::register($gettext);
    }
}
