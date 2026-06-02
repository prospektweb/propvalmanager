<?php

namespace Prospektweb\PropValManager\Service;

final class PublicJsonConfigExtension
{
    public const EVENT_MODULE = 'main';
    public const EVENT_NAME = 'OnEndBufferContent';

    public static function onEndBufferContent(&$content): void
    {
        // Intentionally disabled: automatic public buffer injection is unsafe for Aspro endpoints
        // that include Bitrix prolog/epilog but return JavaScript or AJAX fragments.
        // The generated JSON path/version remain available in ModuleConfig and admin settings;
        // frontend integration should include the config explicitly in the needed template.
    }
}
