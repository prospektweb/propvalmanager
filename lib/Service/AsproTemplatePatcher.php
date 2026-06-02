<?php

namespace Prospektweb\PropValManager\Service;

use Exception;

final class AsproTemplatePatcher
{
    private const TRACKED_FILES_OPTION = 'ASPRO_PATCHED_FILES';

    /**
     * @var array<int, array{target:string,source:string}>
     */
    private const REPLACEMENTS = [
        [
            'target' => '/bitrix/templates/aspro-premier/components/bitrix/catalog.element/main/component_epilog.php',
            'source' => '/samples-aspro/bitrix/templates/aspro-premier/components/bitrix/catalog.element/main/component_epilog.php',
        ],
        [
            'target' => '/bitrix/templates/aspro-premier/include/blocks/catalog/props/list.php',
            'source' => '/samples-aspro/include/blocks/catalog/props/list.php',
        ],
    ];

    public function apply(): void
    {
        $tracked = $this->getTrackedFiles();

        foreach (self::REPLACEMENTS as $replacement) {
            $targetPath = $this->getDocumentRootPath($replacement['target']);
            $sourcePath = $this->getModuleRootPath($replacement['source']);

            if (!is_file($sourcePath) || !is_readable($sourcePath)) {
                throw new Exception('Подготовленный файл Aspro недоступен: ' . $sourcePath);
            }

            if (!is_file($targetPath)) {
                $this->log('Файл Aspro не найден, замена пропущена: ' . $targetPath);
                continue;
            }

            if (!is_readable($targetPath) || !is_writable($targetPath)) {
                throw new Exception('Файл Aspro недоступен для замены: ' . $targetPath);
            }

            $currentContent = (string)file_get_contents($targetPath);
            $currentHash = $this->hash($currentContent);
            $sourceContent = (string)file_get_contents($sourcePath);
            $sourceHash = $this->hash($sourceContent);
            $backupPath = $this->getBackupPath($targetPath);
            $trackedKey = $this->getTrackedKey($targetPath);
            $previous = $tracked[$trackedKey] ?? null;

            if (is_array($previous) && $currentHash === (string)($previous['installed_hash'] ?? '')) {
                if (file_put_contents($targetPath, $sourceContent) === false) {
                    throw new Exception('Не удалось обновить файл Aspro: ' . $targetPath);
                }

                $tracked[$trackedKey]['installed_hash'] = $sourceHash;
                $tracked[$trackedKey]['source'] = $sourcePath;
                continue;
            }

            if (is_array($previous) && $currentHash !== (string)($previous['installed_hash'] ?? '')) {
                $this->log('Файл Aspro был изменён после установки модуля, повторная замена пропущена: ' . $targetPath);
                continue;
            }

            $this->ensureBackupDirectory($backupPath);
            if (file_put_contents($backupPath, $currentContent) === false) {
                throw new Exception('Не удалось создать backup для: ' . $targetPath);
            }

            if (file_put_contents($targetPath, $sourceContent) === false) {
                throw new Exception('Не удалось заменить файл Aspro: ' . $targetPath);
            }

            $tracked[$trackedKey] = [
                'path' => $targetPath,
                'source' => $sourcePath,
                'backup' => $backupPath,
                'original_hash' => $currentHash,
                'installed_hash' => $sourceHash,
            ];
        }

        $this->saveTrackedFiles($tracked);
    }

    public function restore(): void
    {
        $tracked = $this->getTrackedFiles();

        foreach ($tracked as $key => $item) {
            if (!is_array($item)) {
                unset($tracked[$key]);
                continue;
            }

            $path = (string)($item['path'] ?? '');
            $backup = (string)($item['backup'] ?? '');
            $installedHash = (string)($item['installed_hash'] ?? '');
            if ($path === '' || $backup === '' || $installedHash === '' || !is_file($backup)) {
                unset($tracked[$key]);
                continue;
            }

            if (!is_file($path)) {
                unset($tracked[$key]);
                continue;
            }

            $currentContent = (string)file_get_contents($path);
            $currentHash = $this->hash($currentContent);
            if ($currentHash !== $installedHash) {
                $this->log('Файл Aspro был изменён после установки модуля, восстановление оригинала пропущено: ' . $path);
                continue;
            }

            $backupContent = (string)file_get_contents($backup);
            if (file_put_contents($path, $backupContent) === false) {
                throw new Exception('Не удалось восстановить файл Aspro: ' . $path);
            }

            unset($tracked[$key]);
        }

        if ($tracked) {
            $this->saveTrackedFiles($tracked);
            return;
        }

        \Bitrix\Main\Config\Option::delete(ModuleConfig::MODULE_ID, ['name' => self::TRACKED_FILES_OPTION]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getTrackedFiles(): array
    {
        $raw = \Bitrix\Main\Config\Option::get(ModuleConfig::MODULE_ID, self::TRACKED_FILES_OPTION, '{}');
        $tracked = json_decode($raw, true);

        return is_array($tracked) ? $tracked : [];
    }

    /**
     * @param array<string, mixed> $tracked
     */
    private function saveTrackedFiles(array $tracked): void
    {
        \Bitrix\Main\Config\Option::set(
            ModuleConfig::MODULE_ID,
            self::TRACKED_FILES_OPTION,
            json_encode($tracked, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    private function getDocumentRootPath(string $relativePath): string
    {
        return rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($relativePath, '/');
    }

    private function getModuleRootPath(string $relativePath): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($relativePath, '/');
    }

    private function getBackupPath(string $filePath): string
    {
        return $this->getDocumentRootPath('/upload/' . ModuleConfig::MODULE_ID . '/backup/aspro/' . md5($filePath) . '.bak');
    }

    private function getTrackedKey(string $filePath): string
    {
        return md5($filePath);
    }

    private function ensureBackupDirectory(string $backupPath): void
    {
        $backupDirectory = dirname($backupPath);
        if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0775, true) && !is_dir($backupDirectory)) {
            throw new Exception('Не удалось создать директорию backup: ' . $backupDirectory);
        }
    }

    private function hash(string $content): string
    {
        return hash('sha256', $content);
    }

    private function log(string $message): void
    {
        if (function_exists('AddMessage2Log')) {
            AddMessage2Log($message, ModuleConfig::MODULE_ID);
        }
    }
}
