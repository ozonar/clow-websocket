<?php

namespace App\Model;

class ShellRunner
{
    private $scriptFile;
    private $scriptPath;
    private $maxRunProcesses;

    /**
     * @param $scriptFile
     */
    public function __construct($scriptFile)
    {
        $this->setScriptFile($scriptFile);
    }

    public function countProcessesSameScriptPath()
    {
        $ret = (int)shell_exec($cmd = sprintf("COLUMNS=4096; ps aux | grep '%s %s' | grep -v grep | grep -v 'app:runner' | wc -l", 'php', $this->scriptPath));

        return $ret;
    }

    /**
     * @return bool
     */
    public function run()
    {
        if ($this->isRunning()) {
            return false;
        }

        if ($this->maxRunProcesses > 0 && $this->maxRunProcesses + 1 < $this->countProcessesSameScriptPath())
        {
            return false;
        }

        try {
            $this->nohup($this->scriptFile, array());

            return true;
        } catch (\Exception $e) {

        }

        return false;
    }

    /**
     * @param $pathToScript
     * @param array $args
     */

    private function nohup($pathToScript, array $args = array())
    {
        $end_script = '2>/dev/null >/dev/null';
        $cmd = sprintf("nohup %s %s %s $end_script &",
            'php',
            $pathToScript,
            implode(' ', array_map('escapeshellarg', $args))
        );

        shell_exec($cmd);
    }

    /**
     * @return boolean
     */
    private function isRunning()
    {
        $ret = shell_exec($cmd = sprintf("COLUMNS=4096; ps aux | grep '%s %s' | grep -v grep | grep -v 'app:runner' ", '', $this->scriptFile));

        return strlen(trim($ret)) ? 1 : 0;
    }

    /**
     * @param $scriptFile
     */
    private function setScriptFile($scriptFile)
    {
        $scriptFile = preg_replace('/\s+/', ' ', trim($scriptFile));
        $this->scriptFile = $scriptFile;
        $parts = explode(' ', $scriptFile);
        $this->scriptPath = $parts[0];
    }

    public function see()
    {
        return shell_exec($cmd = sprintf("COLUMNS=4096; ps aux | grep '%s %s' | grep -v grep", '', $this->scriptFile));
    }

    public function kill()
    {
        if ($pid = $this->getPid())
        {
            shell_exec("kill $pid;");
        }
    }

    public function restart()
    {
        $this->kill();
        return $this->run();
    }

    /**
     * @return int
     */
    private function getPid()
    {
        $ps_aux = shell_exec($cmd = sprintf("COLUMNS=4096; ps aux | grep '%s' | grep -v grep", $this->scriptFile));
        if ($ps_aux > '')
        {
            $ps_aux = preg_replace('/\s+/', ' ', $ps_aux);
            $parts  = explode(' ', $ps_aux);

            return (int)$parts[1];
        }

        return 0;
    }
}
