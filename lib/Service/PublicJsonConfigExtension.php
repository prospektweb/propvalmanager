<?php

namespace Prospektweb\PropValManager\Service;

final class PublicJsonConfigExtension
{
    public const EVENT_MODULE = 'main';
    public const EVENT_NAME = 'OnEndBufferContent';

    public static function onEndBufferContent(&$content): void
    {
        if (!is_string($content) || (defined('ADMIN_SECTION') && ADMIN_SECTION === true) || !ModuleConfig::isEnabled()) {
            return;
        }

        $path = ModuleConfig::getPropertyDescriptionsJsonPath();
        $version = ModuleConfig::getPropertyDescriptionsJsonVersion();
        if ($path === '' || $version === '') {
            return;
        }

        $config = [
            'jsonUrl' => $path,
            'version' => $version,
        ];
        $script = "\n" . '<script data-prospektweb-propvalmanager="property-descriptions-config">' . "\n" .
            'window.ProspektPropValManagerDescriptions = ' . json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';' . "\n" .
            '</script>' . "\n";

        $bodyPos = stripos($content, '</body>');
        if ($bodyPos === false) {
            $content .= $script;
            return;
        }

        $content = substr($content, 0, $bodyPos) . $script . substr($content, $bodyPos);
    }
}
