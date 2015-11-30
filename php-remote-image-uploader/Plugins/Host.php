<?php
/**
 * This plugin is temporary, i will rewrite this library when i have time.
 */
class ChipVN_ImageUploader_Plugins_Host extends ChipVN_ImageUploader_Plugins_Abstract
{
    private $stream;

    private $hostLink;

    private $host;

    private $port;

    private $path;

    private $loginStatus;

    public function __destruct()
    {
        if (is_resource($this->stream)) {
            ftp_close($this->stream);
        }
    }

    public function setHost($host, $port = 21, $path = '/', $hostLink = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->path = '/'.trim($path, '/').'/';

        if ($hostLink) {
            $this->hostLink = rtrim($hostLink, '/');
        }
    }

    /**
     * Execute login action and return results.
     *
     * @return boolean
     *
     * @throws Exception If login failed.
     */
    protected function doLogin()
    {
        if (empty($this->loginStatus)) {
            $this->loginStatus = ftp_login($this->getStream(), $this->username, $this->password);
        }

        return $this->loginStatus;
    }

    /**
     * Execute upload action and return URL.
     *
     * @return string|false
     *
     * @throws Exception If have an error occurred
     */
    protected function doUpload()
    {
        $level = error_reporting(E_ALL &~ E_WARNING);

        $this->getStream();

        $subfolder = date('Y/m/d');

        $this->preparePath($subfolder);

        $fpfile = fopen($this->file, 'r');
        $filename = $this->getRemoteFileName($this->file);

        $result = ftp_fput($this->stream, $filename, $fpfile, FTP_BINARY);

        fclose($fpfile);
        if (!$result) {
            $this->throwException('Cannot upload to this host "%s:%s", path: "%s"', $this->host, $this->port, $path);
        }
        $link = $this->getHostLink().'/'.$subfolder.'/'.$filename;

        error_reporting($level);

        return $link;
    }

    private function preparePath($subfolder)
    {
        if (!ftp_chdir($this->stream, $this->path)) {
            $this->throwException('Cannot change directory to path "%s"', $this->path);
        }
        $p = $path = $this->path.$subfolder;
        $folders = array();
        while ($p != $this->path) {
            if (!ftp_chdir($this->stream, $p)) {
                $folders[] = basename($p);
                $p = dirname($p);
            } else {
                krsort($folders);
                foreach($folders as $folder) {
                    $p .= '/'.$folder;
                    if (!ftp_mkdir($this->stream, $p)) {
                        $this->throwException('Cannot create path "%s"', $p);
                    }
                    ftp_chmod($this->stream, 0777, $p);
                }
                break;
            }
        }
        ftp_chdir($this->stream, $path);
    }

    private function getHostLink()
    {
        if ($this->hostLink) {
            return $this->hostLink;
        }

        return sprintf('http://%s', $this->host);
    }

    private function getStream()
    {
        if ($this->stream === null) {
            if (empty($this->host) || empty($this->port)) {
                $this->throwException('You must define a host.');
            }
            $this->stream = ftp_connect($this->host, $this->port, 10);
        }

        return $this->stream;
    }

    private function getRemoteFileName($filename)
    {
        $filename = basename($this->file);
        $ext = strstr($filename, '.', false);
        $filename = sprintf('%s_%s%s', substr($filename, 0, -strlen($ext)), substr(md5(microtime(true)), -3), strtolower($ext));

        return $filename;
    }

    /**
     * Execute transload action and return URL.
     *
     * @return string|false
     *
     * @throws Exception If have an error occurred
     */
    protected function doTransload()
    {
        $this->throwException('This plugin does not suport transload');
    }
}
