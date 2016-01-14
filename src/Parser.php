<?php

namespace PhpBounceMailParser;

use PhpBounceMailParser\BounceCode;

/**
 * Parse Bounce E-Mails and write csv result data
 *
 * These extensions are required
 * @see http://php.net/manual/de/book.mailparse.php
 * @see https://github.com/php-mime-mail-parser/php-mime-mail-parser
 */
class Parser
{
    const REASON_AUTORESPONDER = 'auto responder';

    /**
     * @var \PhpMimeMailParser\Parser
     */
    private $parser = NULL;

    /**
     * the line of the resource file
     *
     * @var array
     */
    private $lines = NULL;

    /**
     * @var resource
     */
    private $csv = NULL;

    public function __construct()
    {
        $this->parser = new \PhpMimeMailParser\Parser();

        $tenMegabytes = 10 * 1024 * 1024;
        $this->csv = fopen("php://temp/maxmemory:$tenMegabytes", 'r+');

        if (FALSE === $this->csv)
        {
            throw new \Exception('Unable to open csv output stream');
        }
    }

    /**
     * Parse the given directory with email resources
     *
     * @param  string $directory path/to/directory
     * @return void
     */
    public function parseDirectory($directory)
    {
        if ( ! file_exists($directory))
            throw new Exception("Directory $directory does not exist.");

        $mails = array_diff(scandir($directory), array('.', '..'));

        foreach ($mails as $mail)
        {
            $this->parseFile($directory . '/' . $mail);
        }
    }

    /**
     * Parse the given email resource
     *
     * @param  string $file path/to/file
     * @return void
     */
    public function parseFile($file)
    {
        if ( ! file_exists($file))
            throw new Exception("File $file does not exist.");

        $this->lines = file($file);

        $this->parser->setPath($file);

        $bounceReason = $this->findBounceReason($file);

        fputcsv($this->csv, array(
            $this->findRecipient(),
            key($bounceReason),
            current($bounceReason)
        ));
    }

    /**
     * Output csv data
     *
     * @return void
     */
    public function outputCsv()
    {
        rewind($this->csv);
        $content = stream_get_contents($this->csv);
        fclose($this->csv);

        echo $content;
    }

    /**
     * Output csv data and save as file
     *
     * @return void
     */
    public function saveCsvAs()
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=bounces.csv');

        $this->outputCsv();

    }

    /**
     * Try to find bounce reason within email header
     *
     * @return array including error code as key and the reason as value e.g. array(550 => 'mailbox unavailable')
     */
    private function findBounceReason($file)
    {
        // Check if there is an diagnostic code in email header
        $result = array_filter($this->lines, array($this, 'findDiagnosticCode'));
        if (count($result) === 1)
        {
            $bounceCode = new BounceCode(substr(current($result), 17));
            return $bounceCode->getCode();
        }
        else
        {
            return array(self::REASON_AUTORESPONDER);
        }
    }

    private function findDiagnosticCode($line)
    {
        return preg_match('/^Diagnostic-Code:/', $line);
    }

    private function findRecipient()
    {
        $email = null;
        $headers = array(
            'X-MS-Exchange-Inbox-Rules-Loop',
            'To',
            'From',
            'Delivered-To'
        );

        foreach ($headers as $header)
        {
            if (is_null($email))
            {
                $email = $this->findEmailInHeaderLine($header);
            }
        }

        return $email;
    }

    private function findEmailInHeaderLine($header)
    {
        $matches = array_filter($this->lines, function($line) use ($header)
        {
            return preg_match("/^$header:/", $line);
        });

        foreach ($matches as $key => $match)
        {
            if (strpos($match, 'no-reply@wf-ingbau.de') ||
                strpos($match, 'Mail Delivery System'))
            {
                unset($matches[$key]);
            }
        }

        if (count($matches) > 0)
        {
            $email = end($matches);

            // Append second indented line
            if (array_key_exists(key($matches) + 1, $this->lines) &&
                $this->lines[key($matches) + 1][0] === "\t")
            {
                $email .= $this->lines[key($matches) + 1];
            }

            $email = substr($email, strlen($header) + 2);
            $email = trim($email);

            if (strpos($email, '<') !== FALSE &&
                preg_match('/<(.*?)>/si', $email, $email))
            {
                $email = $email[1];
            }

            return $email;
        }
    }

    /**
     * Debugging helper
     * Prints the given value to viewport
     *
     * @param string $value
     * @param boolean $varDump
     */
    private function pr($value = null, $varDump = false)
    {
        switch (true)
        {
            case is_null($value):
                $value = 'NULL';
                break;

            case is_bool($value):
                $value = $value ? 'TRUE' : 'FALSE';
                break;

            case is_string($value) && empty($value):
                $value = '<i>empty string</i>';
                break;
        }

        if ($varDump === false)
        {
            $message = print_r($value, true);
        }
        else
        {
            $message = var_dump($value);
        }

        $bt = debug_backtrace();
        $btLine = $bt[0];

        echo '
            <div style="margin: 10px; border: 1px solid #333333; font-family: Arial, Verdana;">
                <div style="background: #333333; color: #ffffff; padding: 2px 10px 2px 10px; font-size: 12px;">
                    ' . $btLine['file'] . ' : ' . $btLine['line'] . '
                </div>
                <pre style="padding: 10px; background: #ffffff;">' . $message . '</pre>
            </div>
        ';

        if (count(ob_list_handlers()))
        {
            ob_flush();
        }
    }
}
