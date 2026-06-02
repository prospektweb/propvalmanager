<?php

namespace Prospektweb\PropValManager\Service;

use Bitrix\Main\Context;

final class PublicJsonConfigExtension
{
    public const EVENT_MODULE = 'main';
    public const EVENT_NAME = 'OnEndBufferContent';

    public static function onEndBufferContent(&$content): void
    {
        if (!is_string($content) || (defined('ADMIN_SECTION') && ADMIN_SECTION === true) || !ModuleConfig::isEnabled()) {
            return;
        }

        if (!self::isFullHtmlPage($content) || self::isAjaxRequest()) {
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
            return;
        }

        $content = substr($content, 0, $bodyPos) . $script . substr($content, $bodyPos);
    }

    private static function isFullHtmlPage(string $content): bool
    {
        return stripos($content, '<html') !== false && stripos($content, '</body>') !== false;
    }

    private static function isAjaxRequest(): bool
    {
        $request = Context::getCurrent()->getRequest();
        if ($request->isAjaxRequest()) {
            return true;
        }

        if (defined('BX_AJAX_REQUEST') && BX_AJAX_REQUEST === true) {
            return true;
        }

        return (string)$request->get('mode') === 'ajax';
    }
}
