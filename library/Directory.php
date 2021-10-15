<?php

namespace packages\hash_directory;

use packages\base\Exception;
use packages\base\IO\Directory as DirectoryAbstract;
use packages\base\IO\File;
use packages\base\IO\WriteException;

class Directory extends DirectoryAbstract
{
    protected DirectoryAbstract $root;
    protected int $levels;

    public function __construct(DirectoryAbstract $root, int $levels)
    {
        $this->root = $root;
        $this->levels = $levels;
    }

    public function getRoot(): DirectoryAbstract
    {
        return $this->root;
    }

    public function getLevels(): int
    {
        return $this->levels;
    }

    public function file(string $name): File
    {
        if (!preg_match("/^([0-9a-f]+)(?:\.[a-zA-Z0-9]+)?$/", $name, $matches)) {
            throw new Exception('your file name is not a valid hash');
        }
        if (0 != strlen($matches[1]) % 2) {
            throw new Exception('your file name is not a valid hash');
        }
        $parts = [];
        for ($x = 0; $x < $this->levels; ++$x) {
            $parts[] = substr($matches[1], $x * 2, 2);
        }

        return $this->root->directory(implode('/', $parts))->file($name);
    }

    public function findPlace(File $file): File
    {
        $extension = $file->getExtension();
        if (!method_exists($file, 'md5')) {
            $tmp = new File\Tmp();
            $file->copyTo($tmp);
            $file = $tmp;
        }
        $md5 = $file->md5();

        return $this->file("{$md5}".($extension ? ".{$extension}" : ''));
    }

    public function putFile(File $file): File
    {
        $newFile = $this->findPlace($file);
        $needToCopy = true;

        if ($newFile->exists()) {
            $needToCopy = $newFile->size() != $file->size();
        } else {
            $dir = $newFile->getDirectory();
            if (!$dir->exists()) {
                $dir->make(true);
            }
        }
        if ($needToCopy) {
            if (!$file->copyTo($newFile)) {
                throw new WriteException($file, $newFile);
            }
        }

        return $newFile;
    }

    /**
     * @param File[] $files
     *
     * @return File[]
     */
    public function putFiles(array $files): array
    {
        return array_map([$this, 'putFile'], $files);
    }

    /**
     * @return never
     */
    public function directory(string $name): DirectoryAbstract
    {
        throw new Exception('Cannot access hash directories');
    }

    public function serialize(): string
    {
        return serialize([
            'root' => $this->root,
            'levels' => $this->levels,
        ]);
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->root = $data['root'];
        $this->levels = $data['levels'];
    }

    public function size(): int
    {
        return $this->root->size();
    }

    public function move(DirectoryAbstract $dest): bool
    {
        return $this->root->move($dest);
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        return $this->root->delete();
    }

    public function make(): bool
    {
        return $this->root->make();
    }

    /**
     * @return File[]
     */
    public function files(bool $recursively = true): array
    {
        return $this->root->files($recursively);
    }

    /**
     * @return DirectoryAbstract[]
     */
    public function directories(bool $recursively = true): array
    {
        return $this->root->directories($recursively);
    }

    /**
     * @return array<File|DirectoryAbstract>
     */
    public function items(bool $recursively = true): array
    {
        return $this->root->items($recursively);
    }

    public function copyTo(DirectoryAbstract $dest): bool
    {
        return $this->root->copyTo($dest);
    }

    public function copyFrom(DirectoryAbstract $source): bool
    {
        return $this->root->copyFrom($source);
    }

    public function isEmpty(): bool
    {
        return $this->root->isEmpty();
    }

    public function getPath(): string
    {
        return $this->root->getPath();
    }

    public function rename(string $newName): bool
    {
        return $this->root->rename($newName);
    }

    public function getDirectory(): DirectoryAbstract
    {
        return $this->root->getDirectory();
    }

    public function isIn(DirectoryAbstract $parent): bool
    {
        return $this->root->isIn($parent);
    }

    public function getRelativePath(DirectoryAbstract $parent): string
    {
        return $this->root->getRelativePath($parent);
    }

    public function exists(): bool
    {
        return $this->root->exists();
    }
}
