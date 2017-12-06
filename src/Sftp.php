<?php
/**
 * Created by PhpStorm.
 * User: irelance
 * Date: 17-12-6
 * Time: 上午10:26
 */

namespace Irelance\Library;


class Sftp
{
    protected $conn;
    protected $sub;

    protected $host;
    protected $port;
    protected $user;
    protected $pass;

    protected $location = '/';

    public function __construct($host, $user, $pass, $port = 21, $location = '/')
    {
        if (!$this->conn && !$this->conn = ssh2_connect($host, $port)) {
            throw new \Exception("Could not connect to $host on port $port.");
        }
        if (!ssh2_auth_password($this->conn, $user, $pass)) {
            throw new \Exception(
                "Could not authenticate with username $user and password $pass."
            );
        }
        if (!$this->sub = ssh2_sftp($this->conn)) {
            throw new \Exception("Could not initialize SFTP subsystem.");
        }
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
        $this->changeLocation($location);
    }

    protected function makeRemoteUri($remote)
    {
        return "ssh2.sftp://" . $this->sub . $this->location . $remote;
    }

    public function changeLocation($remote, $relate = false)
    {
        $location = ($relate ? rtrim($this->location, '/') : '') . '/' . ltrim($remote);
        if (!is_dir("ssh2.sftp://" . $this->sub . $location)) {
            return false;
        }
        $this->location = $location;
        return true;
    }

    public function listDir($remote = null)
    {
        if (is_null($remote)) {
            $remote = $this->location;
        }
        $remote = $this->makeRemoteUri($remote);
        if (!is_dir($remote)) {
            return false;
        }
        $results = [];
        $files = scandir($remote);
        foreach ($files as $file) {
            if ($file != '.' || $file != '..') {
                $results[] = $file;
            }
        }
        return $results;
    }

    public function mkdir($remote, $mode = 0777, $recursive = false)
    {
        $remote = $this->makeRemoteUri($remote);
        if (!is_dir($remote)) {
            return false;
        }
        return mkdir($remote, $mode, $recursive);
    }

    public function rmdir($remote)
    {
        $remote = $this->makeRemoteUri($remote);
        if (!is_dir($remote)) {
            return false;
        }
        return rmdir($remote);
    }

    public function upload($local, $remote)
    {
        $remote = $this->makeRemoteUri($remote);
        if (!is_file($local)) {
            return false;
        }
        if (is_dir($remote)) {
            $remote = rtrim($remote) . '/' . basename($local);
        }
        $content = @file_get_contents($local);

        $stream = @fopen($remote, 'w');
        $result = @fwrite($stream, $content) !== false;
        @fclose($stream);
        return $result;
    }

    public function download($remote, $local)
    {
        $remote = $this->makeRemoteUri($remote);
        if (!is_file($remote)) {
            return false;
        }
        if (is_dir($local)) {
            $local = rtrim($local) . '/' . basename($remote);
        }
        $content = @file_get_contents($remote);

        $stream = @fopen($local, 'w');
        $result = @fwrite($stream, $content) !== false;
        @fclose($stream);
        return $result;
    }

    public function openStream($remote, $mode)
    {
        $remote = $this->makeRemoteUri($remote);
        return @fopen($remote, $mode);
    }

    public function closeStream($handle)
    {
        return @fclose($handle);
    }

    public function unlink($remote)
    {
        $remote = $this->makeRemoteUri($remote);
        if (!is_file($remote)) {
            return false;
        }
        return unlink($remote);
    }
}