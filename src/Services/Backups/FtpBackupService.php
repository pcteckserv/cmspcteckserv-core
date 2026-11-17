<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Pcteckserv\CmsCore\Models\BackupDestination;
use Pcteckserv\CmsCore\Services\Backups\BackupException as RuntimeException;

class FtpBackupService
{
    public function put(BackupDestination $destination, string $file, string $path): void
    {
        $this->withConnection($destination, function ($connection) use ($file, $path): void {
            $loginDirectory = ftp_pwd($connection);
            if ($loginDirectory === false) {
                throw new RuntimeException('Não foi possível consultar a pasta FTP.');
            }
            $current = str_starts_with($path, '/') ? '/' : '';
            foreach (explode('/', trim(dirname($path), '/')) as $part) {
                if ($part === '' || $part === '.') {
                    continue;
                }
                $current .= $part.'/';
                if (! @ftp_chdir($connection, $current) && ! @ftp_mkdir($connection, $current)) {
                    throw new RuntimeException('Não foi possível criar a pasta FTP.');
                }
                if (! @ftp_chdir($connection, $loginDirectory)) {
                    throw new RuntimeException('Não foi possível aceder à pasta FTP.');
                }
            }
            if (! @ftp_put($connection, $path, $file, FTP_BINARY) || @ftp_size($connection, $path) !== filesize($file)) {
                throw new RuntimeException('O servidor FTP não confirmou o backup completo.');
            }
        });
    }

    public function delete(BackupDestination $destination, string $path): void
    {
        $this->withConnection($destination, function ($connection) use ($path): void {
            if (@ftp_delete($connection, $path)) {
                return;
            }
            $listing = @ftp_nlist($connection, dirname($path));
            if ($listing === false || in_array(basename($path), array_map('basename', $listing), true)) {
                throw new RuntimeException('Não foi possível eliminar o backup FTP.');
            }
        });
    }

    public function download(BackupDestination $destination, string $path, string $target): void
    {
        $this->withConnection($destination, function ($connection) use ($path, $target): void {
            if (! @ftp_get($connection, $target, $path, FTP_BINARY)) {
                throw new RuntimeException('Não foi possível ler o backup FTP.');
            }
        });
    }

    private function withConnection(BackupDestination $destination, callable $callback): void
    {
        if (! extension_loaded('ftp')) {
            throw new RuntimeException('Ative a extensão FTP do PHP no alojamento.');
        }
        $connection = ($destination->protocol === 'ftps' || $destination->ssl)
            ? @ftp_ssl_connect($destination->host, $destination->port ?: 21, $destination->timeout ?: 30)
            : @ftp_connect($destination->host, $destination->port ?: 21, $destination->timeout ?: 30);
        if (! $connection) {
            throw new RuntimeException('Não foi possível ligar ao servidor FTP.');
        }
        try {
            if (! @ftp_login($connection, $destination->username ?: '', $destination->password_plain ?: '') || ! @ftp_pasv($connection, (bool) $destination->passive)) {
                throw new RuntimeException('O servidor FTP recusou a ligação.');
            }
            $callback($connection);
        } finally {
            ftp_close($connection);
        }
    }
}
