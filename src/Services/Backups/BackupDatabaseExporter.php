<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Illuminate\Support\Facades\DB;
use Pcteckserv\CmsCore\Services\Backups\BackupException as RuntimeException;
use Pdo\Mysql;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class BackupDatabaseExporter
{
    public function export(string $directory): string
    {
        $connection = DB::connection();
        if ($connection->getDriverName() === 'sqlite') {
            $file = $directory.'/database.sqlite';
            $connection->getPdo()->exec('VACUUM INTO '.$connection->getPdo()->quote($file));

            return $file;
        }
        if ($connection->getDriverName() !== 'mysql' && $connection->getDriverName() !== 'mariadb') {
            throw new RuntimeException('O backup automático da base de dados suporta MySQL, MariaDB e SQLite.');
        }
        $binary = config('cms-backups.database_dump_binary') ?: (new ExecutableFinder)->find('mysqldump') ?: (new ExecutableFinder)->find('mariadb-dump');
        if (! $binary) {
            throw new RuntimeException('Configure mysqldump ou mariadb-dump no alojamento para criar backups da base de dados.');
        }
        $options = $directory.'/database-client.cnf';
        $quote = static fn ($value): string => '"'.strtr((string) $value, ['\\' => '\\\\', '"' => '\\"', "\n" => '\\n', "\r" => '\\r']).'"';
        $config = $connection->getConfig();
        $lines = ['[client]'];
        foreach (['host' => 'host', 'port' => 'port', 'username' => 'user', 'password' => 'password', 'unix_socket' => 'socket'] as $key => $option) {
            if (isset($config[$key]) && $config[$key] !== '') {
                $lines[] = $option.'='.$quote($config[$key]);
            }
        }
        foreach (($config['options'] ?? []) as $option => $value) {
            $prefix = class_exists(Mysql::class) ? 'Pdo\\Mysql::ATTR_' : 'PDO::MYSQL_ATTR_';
            $mapping = [constant($prefix.'SSL_CA') => 'ssl-ca', constant($prefix.'SSL_CERT') => 'ssl-cert', constant($prefix.'SSL_KEY') => 'ssl-key'];
            if (isset($mapping[$option])) {
                $lines[] = $mapping[$option].'='.$quote($value);
            }
        }
        $file = $directory.'/database.sql';
        try {
            if (file_put_contents($options, implode("\n", $lines)."\n") === false || ! chmod($options, 0600)) {
                throw new RuntimeException('Não foi possível preparar a exportação privada da base de dados.');
            }
            $process = new Process(array_merge([$binary, '--defaults-extra-file='.$options], config('cms-backups.database_dump_options', []), ['--single-transaction', '--quick', '--routines', '--triggers', '--events', '--hex-blob', '--result-file='.$file, '--', $connection->getDatabaseName()]));
            $process->setTimeout(1800);
            $process->disableOutput();
            if ($process->run() !== 0 || ! is_file($file) || filesize($file) === 0) {
                throw new RuntimeException('A exportação da base de dados falhou. Verifique a ferramenta e as permissões de exportação do utilizador da base de dados.');
            }
        } finally {
            @unlink($options);
        }

        return $file;
    }
}
