<?php
declare(strict_types=1);

/**
 * archive.php — shared helpers for the soft-delete Archive.
 *
 * Deleting a lander / checkout / flow no longer destroys it. Its folder is moved
 * into  htdocs/.archive/<entry-id>/payload/  with an  archive-meta.json  sidecar
 * recording where it came from, so it can be restored to its original collection
 * later — or purged for good from the Archive view.
 *
 * The archive lives under the document root but is fenced off from the web by an
 * .htaccess (Require all denied) written on first use, so archived pages are NOT
 * live: they can't be crawled, indexed, or hit a real checkout while shelved.
 */

const ARCHIVE_DIRNAME = '.archive';

/**
 * Allow-listed collection parents keyed by collection id. This is the single
 * source of truth for which collections can be archived from / restored to —
 * mirrors the editable collections shown in the previews UI.
 */
function archive_collection_dirs(string $docRoot): array
{
    return [
        'sales-pages'         => $docRoot . '/sales-pages',
        'checkout-pages'      => $docRoot . '/checkout-pages',
        'aipu-landers'        => $docRoot . '/aipu-landers',
        'omnirogue-landers'   => $docRoot . '/omnirogue-landers',
        'aipu-checkouts'      => $docRoot . '/aipu-checkouts',
        'omnirogue-checkouts' => $docRoot . '/omnirogue-checkouts',
    ];
}

function archive_flows_dir(string $docRoot): string
{
    return $docRoot . '/flows';
}

/**
 * Resolve the archive root, creating it (and its web-deny guard) on first use.
 */
function archive_root(string $docRoot): string
{
    $root = $docRoot . '/' . ARCHIVE_DIRNAME;
    if (!is_dir($root)) {
        @mkdir($root, 0775, true);
    }
    $ht = $root . '/.htaccess';
    if (is_dir($root) && !is_file($ht)) {
        @file_put_contents($ht,
            "# Archived pages are offline by design — never serve them.\n" .
            "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
        );
    }
    return $root;
}

function archive_rrmdir(string $dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        if ($file->isDir()) {
            @rmdir($file->getPathname());
        } else {
            @unlink($file->getPathname());
        }
    }
    return @rmdir($dir);
}

/**
 * Move a directory. Prefers an atomic rename; falls back to recursive copy +
 * delete if rename fails (e.g. across devices).
 */
function archive_move_dir(string $src, string $dst): bool
{
    if (@rename($src, $dst)) {
        return true;
    }
    if (!is_dir($dst) && !@mkdir($dst, 0775, true)) {
        return false;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $rel = substr($item->getPathname(), strlen($src) + 1);
        $target = $dst . '/' . $rel;
        if ($item->isDir()) {
            if (!is_dir($target)) {
                @mkdir($target, 0775, true);
            }
        } else {
            @copy($item->getPathname(), $target);
        }
    }
    return archive_rrmdir($src);
}

/** Slugs allowed as a lander / checkout / flow folder name. */
function archive_valid_name(string $name): bool
{
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $name) || $name === '.' || $name === '..') {
        return false;
    }
    // Never touch chrome/asset/system folders.
    if ($name[0] === '_' || $name[0] === '.' || $name === 'assets') {
        return false;
    }
    return true;
}

/**
 * Resolve the live origin directory for an item/flow.
 * Returns [absPath, null] or [null, errorString]. Guarantees the result is a
 * direct child of an allow-listed parent inside the doc root.
 */
function archive_resolve_origin(string $type, string $collection, string $name, string $docRoot): array
{
    if (!archive_valid_name($name)) {
        return [null, 'invalid name'];
    }
    if ($type === 'flow') {
        $parent = realpath(archive_flows_dir($docRoot));
        if ($parent === false) {
            return [null, 'flows dir missing'];
        }
    } elseif ($type === 'item') {
        $dirs = archive_collection_dirs($docRoot);
        if (!isset($dirs[$collection])) {
            return [null, 'invalid collection'];
        }
        $parent = realpath($dirs[$collection]);
        if ($parent === false) {
            return [null, 'collection dir missing'];
        }
    } else {
        return [null, 'invalid type'];
    }

    $target = realpath($parent . '/' . $name);
    if ($target === false || !is_dir($target)) {
        return [null, 'not found'];
    }
    if (dirname($target) !== $parent) {
        return [null, 'outside allowed directory'];
    }
    if (strpos($target, $docRoot) !== 0) {
        return [null, 'outside document root'];
    }
    return [$target, null];
}

/** Build a collision-free archive entry id from the origin descriptor. */
function archive_entry_id(string $type, string $collection, string $name, string $root): string
{
    $origin = $type === 'flow' ? 'flow' : ($collection ?: 'item');
    $slug = preg_replace('/[^A-Za-z0-9._-]+/', '-', $origin . '__' . $name);
    $base = date('Ymd-His') . '__' . $slug;
    $id = $base;
    $n = 1;
    while (is_dir($root . '/' . $id)) {
        $id = $base . '-' . (++$n);
    }
    return $id;
}

/**
 * Archive a live item/flow. Validates, moves the folder into the archive, and
 * writes its metadata sidecar.
 * Returns [id, null] on success or [null, errorString].
 */
function archive_store(string $type, string $collection, string $name, string $docRoot): array
{
    [$origin, $err] = archive_resolve_origin($type, $collection, $name, $docRoot);
    if ($origin === null) {
        return [null, $err];
    }

    $root = archive_root($docRoot);
    if (!is_dir($root)) {
        return [null, 'cannot create archive'];
    }

    $id = archive_entry_id($type, $collection, $name, $root);
    $entry = $root . '/' . $id;
    if (!@mkdir($entry, 0775, true)) {
        return [null, 'cannot create archive entry'];
    }

    if (!archive_move_dir($origin, $entry . '/payload')) {
        archive_rrmdir($entry);
        return [null, 'move failed (permissions?)'];
    }

    $meta = [
        'id'          => $id,
        'type'        => $type,
        'collection'  => $type === 'item' ? $collection : null,
        'name'        => $name,
        'origin_path' => $origin,
        'archived_at' => time(),
    ];
    @file_put_contents(
        $entry . '/archive-meta.json',
        json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
    return [$id, null];
}

/** List archive entries, newest first. */
function archive_list(string $docRoot): array
{
    $root = $docRoot . '/' . ARCHIVE_DIRNAME;
    $entries = [];
    if (!is_dir($root)) {
        return $entries;
    }
    foreach (scandir($root) as $e) {
        if ($e === '.' || $e === '..' || $e[0] === '.') {
            continue;
        }
        $dir = $root . '/' . $e;
        if (!is_dir($dir)) {
            continue;
        }
        $meta = [];
        if (is_file($dir . '/archive-meta.json')) {
            $meta = json_decode((string)file_get_contents($dir . '/archive-meta.json'), true) ?: [];
        }
        $meta['id'] = $e; // the folder name is the canonical id
        $meta += [
            'type'        => 'item',
            'collection'  => null,
            'name'        => $e,
            'archived_at' => filemtime($dir),
        ];
        $entries[] = $meta;
    }
    usort($entries, static fn(array $a, array $b): int => ($b['archived_at'] ?? 0) <=> ($a['archived_at'] ?? 0));
    return $entries;
}

/** Resolve + validate an archive entry directory from an id. */
function archive_resolve_entry(string $id, string $docRoot): ?string
{
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $id) || strpos($id, '..') !== false) {
        return null;
    }
    $root = realpath($docRoot . '/' . ARCHIVE_DIRNAME);
    if ($root === false) {
        return null;
    }
    $entry = realpath($root . '/' . $id);
    if ($entry === false || dirname($entry) !== $root || !is_dir($entry)) {
        return null;
    }
    return $entry;
}

/**
 * Restore an archived entry to its original collection/flow location.
 * Returns ['ok' => bool, 'error' => ?string, 'restored' => ?array].
 */
function archive_restore(string $id, string $docRoot): array
{
    $entry = archive_resolve_entry($id, $docRoot);
    if ($entry === null) {
        return ['ok' => false, 'error' => 'archive entry not found'];
    }
    if (!is_file($entry . '/archive-meta.json')) {
        return ['ok' => false, 'error' => 'archive metadata missing'];
    }
    $meta = json_decode((string)file_get_contents($entry . '/archive-meta.json'), true) ?: [];
    $type = (string)($meta['type'] ?? '');
    $name = (string)($meta['name'] ?? '');
    $collection = (string)($meta['collection'] ?? '');

    if (!archive_valid_name($name)) {
        return ['ok' => false, 'error' => 'invalid archived name'];
    }
    if ($type === 'flow') {
        $parent = realpath(archive_flows_dir($docRoot));
    } elseif ($type === 'item') {
        $dirs = archive_collection_dirs($docRoot);
        if (!isset($dirs[$collection])) {
            return ['ok' => false, 'error' => 'unknown collection'];
        }
        $parent = realpath($dirs[$collection]);
    } else {
        return ['ok' => false, 'error' => 'unknown type'];
    }
    if ($parent === false) {
        return ['ok' => false, 'error' => 'destination directory missing'];
    }

    $dest = $parent . '/' . $name;
    if (file_exists($dest)) {
        return ['ok' => false, 'error' => 'an item named "' . $name . '" already exists there — rename or remove it first'];
    }
    $payload = $entry . '/payload';
    if (!is_dir($payload)) {
        return ['ok' => false, 'error' => 'archived payload missing'];
    }
    if (!archive_move_dir($payload, $dest)) {
        return ['ok' => false, 'error' => 'restore move failed (permissions?)'];
    }
    archive_rrmdir($entry);
    return ['ok' => true, 'error' => null, 'restored' => ['type' => $type, 'collection' => $collection, 'name' => $name]];
}

/** Permanently remove an archive entry. Returns ['ok' => bool, 'error' => ?string]. */
function archive_purge(string $id, string $docRoot): array
{
    $entry = archive_resolve_entry($id, $docRoot);
    if ($entry === null) {
        return ['ok' => false, 'error' => 'archive entry not found'];
    }
    return ['ok' => archive_rrmdir($entry), 'error' => null];
}
